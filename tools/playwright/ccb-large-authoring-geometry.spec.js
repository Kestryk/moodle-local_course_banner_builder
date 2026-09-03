const {test, expect} = require('@playwright/test');

const environment = () => {
    const required = ['EASYEDU_MOODLE_URL', 'EASYEDU_MOODLE_USERNAME', 'EASYEDU_MOODLE_PASSWORD'];
    const missing = required.filter(name => !process.env[name]);
    if (missing.length) {
        throw new Error(`Missing large-authoring environment values: ${missing.join(', ')}.`);
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
    await page.waitForLoadState('load');
};

const geometry = async page => page.evaluate(() => {
    const modal = document.querySelector('.local-course-banner-builder-source-chain-preview-modal--authoring');
    const workspace = modal?.querySelector('[data-large-workspace="1"]');
    const viewport = modal?.querySelector('[data-large-workspace-viewport="1"]');
    const stage = modal?.querySelector('[data-large-workspace-stage="1"]');
    const plane = modal?.querySelector('[data-large-workspace-plane="1"]');
    const frame = modal?.querySelector('[data-large-workspace-published-frame="1"]');
    const rect = element => {
        const value = element?.getBoundingClientRect();
        return value ? {left: value.left, top: value.top, width: value.width, height: value.height} : null;
    };
    return {
        mode: modal?.querySelector('[data-source-preview-large-workspace="1"]')?.getAttribute('data-source-preview-mode'),
        zoom: modal?.querySelector('[data-source-preview-large-workspace="1"]')?.getAttribute('data-large-workspace-zoom'),
        format: frame?.getAttribute('data-banner-format'),
        workspaceVariables: workspace ? {
            frameWidth: workspace.style.getPropertyValue('--local-course-banner-builder-large-workspace-frame-width'),
            frameHeight: workspace.style.getPropertyValue('--local-course-banner-builder-large-workspace-frame-height'),
            frameX: workspace.style.getPropertyValue('--local-course-banner-builder-large-workspace-frame-x'),
            frameY: workspace.style.getPropertyValue('--local-course-banner-builder-large-workspace-frame-y'),
            planeWidth: workspace.style.getPropertyValue('--local-course-banner-builder-large-workspace-plane-width'),
            planeHeight: workspace.style.getPropertyValue('--local-course-banner-builder-large-workspace-plane-height'),
        } : null,
        viewportClient: viewport ? {
            left: viewport.clientLeft,
            top: viewport.clientTop,
            width: viewport.clientWidth,
            height: viewport.clientHeight,
        } : null,
        viewport: rect(viewport),
        stage: rect(stage),
        plane: rect(plane),
        frame: rect(frame),
        frameInline: frame ? {width: frame.style.width, height: frame.style.height, cssText: frame.style.cssText} : null,
        frameComputed: frame ? {
            width: getComputedStyle(frame).width,
            height: getComputedStyle(frame).height,
            position: getComputedStyle(frame).position,
            transform: getComputedStyle(frame).transform,
        } : null,
    };
});

test.use({viewport: {width: 1920, height: 900}});

test('large authoring keeps a usable published frame at 100 percent', async({page}) => {
    test.setTimeout(120000);
    const env = environment();
    await login(page, env);
    await page.goto(
        `${env.baseUrl}/local/course_banner_builder/admin_manage.php?sourcekey=category:${encodeURIComponent(env.categoryId)}`,
        {waitUntil: 'commit', timeout: 45000}
    );
    const editor = page.locator('[data-source-visual-editor="1"]').first();
    await expect(editor).toBeVisible({timeout: 30000});
    await expect(editor).toHaveAttribute('data-source-preview-bound', '1', {timeout: 90000});
    await editor.locator('[data-action="local-course-banner-builder-show-large-source-preview"]').click();
    const modal = page.locator('.local-course-banner-builder-source-chain-preview-modal--authoring');
    await expect(modal).toBeVisible();
    await page.evaluate(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))));
    await expect.poll(async() => Number((await geometry(page)).zoom)).toBeGreaterThan(25);
    const fitted = await geometry(page);
    expect(fitted.frameInline?.width, JSON.stringify(fitted, null, 2)).toBe('1600px');
    await modal.locator('[data-large-workspace-action="actual"]').click();
    const value = await geometry(page);
    expect(value, JSON.stringify(value, null, 2)).toMatchObject({mode: 'desktop', zoom: '100'});
    expect(value.frameInline?.width, JSON.stringify(value, null, 2)).toBe('1600px');
    expect(value.frame?.width, JSON.stringify(value, null, 2)).toBeGreaterThanOrEqual(1600);
    expect(value.frame?.height, JSON.stringify(value, null, 2)).toBeGreaterThanOrEqual(200);
    const viewportCentre = value.viewport.left + (value.viewport.width / 2);
    const frameCentre = value.frame.left + (value.frame.width / 2);
    expect(Math.abs(viewportCentre - frameCentre), JSON.stringify(value, null, 2)).toBeLessThanOrEqual(3);
});
