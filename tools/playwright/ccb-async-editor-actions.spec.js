const {test, expect} = require('@playwright/test');
const fs = require('fs');
const path = require('path');

const ensure = (condition, message) => {
    if (!condition) {
        throw new Error(message);
    }
};

const environment = () => {
    const required = [
        'EASYEDU_MOODLE_URL', 'EASYEDU_MOODLE_USERNAME', 'EASYEDU_MOODLE_PASSWORD',
        'EASYEDU_CCB_ASYNC_EDITOR_CATEGORY_ID', 'EASYEDU_CCB_ASYNC_EDITOR_ARTIFACT_ROOT',
    ];
    const missing = required.filter(name => !process.env[name]);
    ensure(!missing.length, 'Missing Async editor validation environment values: ' + missing.join(', '));
    const artifactRoot = path.resolve(process.env.EASYEDU_CCB_ASYNC_EDITOR_ARTIFACT_ROOT);
    ensure(path.isAbsolute(artifactRoot), 'Async editor artifacts must use an absolute external path.');
    ensure(!artifactRoot.toLowerCase().includes(path.sep + 'local' + path.sep + 'course_banner_builder'),
        'Async editor artifacts must remain outside the CCB repository.');
    ensure(/^\d+$/.test(process.env.EASYEDU_CCB_ASYNC_EDITOR_CATEGORY_ID),
        'Async editor fixture category must be numeric.');
    return {
        artifactRoot,
        baseUrl: String(process.env.EASYEDU_MOODLE_URL).replace(/\/$/, ''),
        categoryId: process.env.EASYEDU_CCB_ASYNC_EDITOR_CATEGORY_ID,
        password: process.env.EASYEDU_MOODLE_PASSWORD,
        username: process.env.EASYEDU_MOODLE_USERNAME,
    };
};

const login = async(page, env) => {
    await page.goto(env.baseUrl + '/login/index.php', {waitUntil: 'domcontentloaded', timeout: 60000});
    await page.locator('#username').fill(env.username);
    await page.locator('#password').fill(env.password);
    await page.locator('#loginbtn').click({noWaitAfter: true});
    await expect(page).not.toHaveURL(/\/login\//, {timeout: 60000});
};

const capture = async(page, target) => {
    fs.mkdirSync(path.dirname(target), {recursive: true});
    await page.screenshot({path: target, fullPage: false});
};

const isAsyncDelete = request => request.method() === 'POST' &&
    (request.postData() || '').includes('deleteselectedlayersajax');
const isAsyncDeleteAll = request => request.method() === 'POST' &&
    (request.postData() || '').includes('deletealllayersajax');

test('CCB async editor deletes layers locally with confirmation, feedback and focus return', async({browser}) => {
    const env = environment();
    const context = await browser.newContext({viewport: {width: 1280, height: 900}});
    const page = await context.newPage();
    const evidence = path.join(env.artifactRoot, 'async-editor-actions.png');
    try {
        await login(page, env);
        await page.goto(
            env.baseUrl + '/local/course_banner_builder/admin_manage.php?categoryid=' + encodeURIComponent(env.categoryId),
            {waitUntil: 'networkidle', timeout: 60000}
        );
        const root = page.locator('.local-course-banner-builder-admin').first();
        const selectedSource = page.locator('[data-selected-source-content="1"]').first();
        const selectedDelete = page.locator(
            '[data-action="local-course-banner-builder-delete-selected-layers"]'
        ).first();
        const allDelete = page.locator('[data-action="local-course-banner-builder-delete-all-layers"]').first();
        const choices = page.locator(
            'input[name="selectedelements[]"][form="local-course-banner-builder-bulk-delete"]'
        );
        await expect(root).toHaveAttribute('aria-busy', 'false');
        await expect(selectedSource).toBeVisible();
        await expect(choices).toHaveCount(5);

        await choices.first().check();
        await selectedDelete.click();
        const confirmation = page.locator('#local-course-banner-builder-confirm-action-modal');
        await expect(confirmation).toBeVisible();
        await expect(confirmation.locator('.btn-danger')).toBeFocused();
        await confirmation.locator('.btn-outline-secondary').click();
        await expect(confirmation).toBeHidden();
        await expect(selectedDelete).toBeFocused();
        await expect(choices).toHaveCount(5);

        await page.route('**/local/course_banner_builder/admin_manage.php*', async route => {
            if (isAsyncDelete(route.request())) {
                await new Promise(resolve => setTimeout(resolve, 250));
            }
            await route.continue();
        });
        const selectedResponse = page.waitForResponse(response => isAsyncDelete(response.request()) && response.status() === 200);
        await selectedDelete.click();
        await confirmation.locator('.btn-danger').click();
        await expect(root).toHaveAttribute('aria-busy', 'true');
        const selectedPayload = await (await selectedResponse).json();
        expect(selectedPayload.success).toBe(true);
        await expect(root).toHaveAttribute('aria-busy', 'false');
        await expect(choices).toHaveCount(4);
        await expect(selectedDelete).toBeFocused();
        await expect(page.locator('.toast, [role="alert"]').filter({hasText: /deleted/i}).first()).toBeVisible();

        const pageUrl = page.url();
        const allResponse = page.waitForResponse(response => isAsyncDeleteAll(response.request()) && response.status() === 200);
        await allDelete.click();
        await confirmation.locator('.btn-danger').click();
        const allPayload = await (await allResponse).json();
        expect(allPayload.success).toBe(true);
        await expect(root).toHaveAttribute('aria-busy', 'false');
        await expect(choices).toHaveCount(0);
        await expect(selectedSource.locator('.local-course-banner-builder-empty-layer-list')).toBeVisible();
        expect(page.url()).toBe(pageUrl);
        await expect(selectedSource).toBeFocused();
        await expect(page.locator('.toast, [role="alert"]').filter({hasText: /deleted/i}).first()).toBeVisible();
        await capture(page, evidence);
    } finally {
        await context.close();
    }
});
