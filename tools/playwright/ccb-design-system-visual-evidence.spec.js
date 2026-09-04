const fs = require('fs');
const {test, expect} = require('@playwright/test');

const environment = () => {
    const required = ['EASYEDU_MOODLE_URL', 'EASYEDU_MOODLE_USERNAME', 'EASYEDU_MOODLE_PASSWORD'];
    const missing = required.filter(name => !process.env[name]);
    if (missing.length) {
        throw new Error(`Missing visual-evidence environment values: ${missing.join(', ')}.`);
    }
    return {
        baseUrl: String(process.env.EASYEDU_MOODLE_URL).replace(/\/$/, ''),
        username: process.env.EASYEDU_MOODLE_USERNAME,
        password: process.env.EASYEDU_MOODLE_PASSWORD,
        categoryId: String(process.env.EASYEDU_CCB_LARGE_AUTHORING_SOURCE_CATEGORY_ID || '4'),
    };
};

const login = async(page, env) => {
    await page.goto(`${env.baseUrl}/login/index.php`, {waitUntil: 'domcontentloaded'});
    if (!/\/login\//.test(page.url())) {
        return;
    }
    await page.locator('#username').fill(env.username);
    await page.locator('#password').fill(env.password);
    await Promise.all([
        page.waitForURL(url => !/\/login\//.test(url.pathname), {waitUntil: 'domcontentloaded'}),
        page.locator('#loginbtn').click(),
    ]);
};

const geometry = async(page, selector) => page.locator(selector).evaluate(node => {
    const rect = node.getBoundingClientRect();
    const style = getComputedStyle(node);
    return {
        width: Math.round(rect.width),
        height: Math.round(rect.height),
        display: style.display,
        visibility: style.visibility,
    };
});

test.use({viewport: {width: 1600, height: 1000}});

test('captures non-mutating CCB visual evidence for the source and large editors', async({page}, testInfo) => {
    test.setTimeout(120000);
    const env = environment();
    await login(page, env);
    await page.goto(
        `${env.baseUrl}/local/course_banner_builder/admin_manage.php?sourcekey=category:${encodeURIComponent(env.categoryId)}`,
        {waitUntil: 'domcontentloaded'}
    );

    const editor = page.locator('[data-source-visual-editor="1"]').first();
    await expect(editor).toBeVisible({timeout: 60000});
    await editor.scrollIntoViewIfNeeded();
    await editor.screenshot({path: testInfo.outputPath('ccb-source-editor-desktop.png')});

    await editor.locator('[data-action="local-course-banner-builder-show-large-source-preview"]').click();
    const modal = page.locator('.local-course-banner-builder-source-chain-preview-modal--authoring');
    await expect(modal).toBeVisible({timeout: 30000});
    await page.evaluate(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))));

    const workspace = modal.locator('[data-large-workspace="1"]');
    const frame = modal.locator('[data-large-workspace-published-frame="1"]');
    await expect(workspace).toBeVisible();
    await expect(frame).toBeVisible();
    const fittedWorkspace = await geometry(page, '[data-large-workspace="1"]');
    const fittedFrame = await geometry(page, '[data-large-workspace-published-frame="1"]');
    await page.screenshot({path: testInfo.outputPath('ccb-large-editor-desktop.png'), fullPage: false});

    await modal.locator('[data-large-workspace-action="actual"]').click();
    await expect(modal.locator('[data-source-preview-large-workspace="1"]')).toHaveAttribute(
        'data-large-workspace-zoom', '100'
    );
    await page.screenshot({path: testInfo.outputPath('ccb-large-editor-actual-size.png'), fullPage: false});

    fs.writeFileSync(testInfo.outputPath('ccb-visual-evidence.json'), JSON.stringify({
        sourceEditor: await geometry(page, '[data-source-visual-editor="1"]'),
        fittedWorkspace,
        fittedFrame,
        actualWorkspace: await geometry(page, '[data-large-workspace="1"]'),
        actualFrame: await geometry(page, '[data-large-workspace-published-frame="1"]'),
        source: `category:${env.categoryId}`,
        nonMutating: true,
    }, null, 2));
});
