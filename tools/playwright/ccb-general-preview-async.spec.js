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
        'EASYEDU_CCB_GENERAL_PREVIEW_CATEGORY_ID', 'EASYEDU_CCB_GENERAL_PREVIEW_ARTIFACT_ROOT',
    ];
    const missing = required.filter(name => !process.env[name]);
    ensure(!missing.length, 'Missing general preview validation environment values: ' + missing.join(', '));
    const artifactRoot = path.resolve(process.env.EASYEDU_CCB_GENERAL_PREVIEW_ARTIFACT_ROOT);
    ensure(path.isAbsolute(artifactRoot), 'General preview artifacts must use an absolute external path.');
    ensure(!artifactRoot.toLowerCase().includes(path.sep + 'local' + path.sep + 'course_banner_builder'),
        'General preview artifacts must remain outside the CCB repository.');
    ensure(/^\d+$/.test(process.env.EASYEDU_CCB_GENERAL_PREVIEW_CATEGORY_ID),
        'General preview fixture category must be numeric.');
    return {
        artifactRoot,
        baseUrl: String(process.env.EASYEDU_MOODLE_URL).replace(/\/$/, ''),
        categoryId: process.env.EASYEDU_CCB_GENERAL_PREVIEW_CATEGORY_ID,
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

const isAdminPost = request => request.method() === 'POST' &&
    request.url().includes('/local/course_banner_builder/admin_manage.php');

const hasPostField = (request, field) => {
    const body = request.postDataBuffer();
    return !!body && body.toString('utf8').includes(field);
};

test('CCB general preview saves and deletes locally with one shared action lock', async({browser}) => {
    const env = environment();
    const context = await browser.newContext({viewport: {width: 1280, height: 900}});
    const page = await context.newPage();
    const evidence = path.join(env.artifactRoot, 'general-preview-async.png');
    try {
        await login(page, env);
        await page.goto(
            env.baseUrl + '/local/course_banner_builder/admin_manage.php?categoryid=' + encodeURIComponent(env.categoryId),
            {waitUntil: 'domcontentloaded', timeout: 60000}
        );
        const root = page.locator('.local-course-banner-builder-admin').first();
        const selectedSource = page.locator('[data-selected-source-content="1"]').first();
        const save = page.locator('[data-source-preview-save-control="1"]').first();
        const deleteSelected = page.locator(
            '[data-action="local-course-banner-builder-delete-selected-preview-layer"]'
        ).first();
        const deleteAll = page.locator('[data-action="local-course-banner-builder-delete-all-layers"]').first();
        const selectLayerThumbnail = page.locator(
            '[data-action="local-course-banner-builder-select-source-preview-thumbnail"]:not(.is-disabled)'
        ).first();
        const confirmation = page.locator('#local-course-banner-builder-confirm-action-modal');
        await expect(root).toBeVisible({timeout: 60000});
        await expect(root).toHaveAttribute('aria-busy', 'false');
        await expect(selectedSource).toBeVisible();
        await expect(save).toBeVisible();
        await expect(deleteAll).toBeVisible();
        await expect(selectLayerThumbnail).toBeVisible();

        await page.route('**/local/course_banner_builder/admin_manage.php*', async route => {
            if (isAdminPost(route.request())) {
                await new Promise(resolve => setTimeout(resolve, 250));
            }
            await route.continue();
        });

        await test.step('save refreshes only the selected source and announces success', async() => {
            const pageUrl = page.url();
            const saveResponse = page.waitForResponse(response =>
                isAdminPost(response.request()) && hasPostField(response.request(), 'updatepreviewlayersajax') &&
                response.status() === 200,
            {timeout: 10000});
            await save.click();
            await expect(root).toHaveAttribute('aria-busy', 'true', {timeout: 5000});
            await expect(deleteAll).toBeDisabled();
            const payload = await (await saveResponse).json();
            expect(payload.success).toBe(true);
            await expect(root).toHaveAttribute('aria-busy', 'false', {timeout: 10000});
            expect(page.url()).toBe(pageUrl);
            await expect(save).toBeFocused({timeout: 10000});
            await expect(page.locator('.toast, [role="alert"]').filter({hasText: /saved/i}).first()).toBeVisible({timeout: 10000});
        });

        await test.step('selected-layer deletion uses the shared confirmation and locks Save', async() => {
            await selectLayerThumbnail.click();
            await expect(deleteSelected).toBeVisible({timeout: 10000});
            await deleteSelected.click();
            await expect(confirmation).toBeVisible({timeout: 10000});
            await confirmation.locator('.btn-outline-secondary').click();
            await expect(deleteSelected).toBeFocused({timeout: 5000});

            const deleteResponse = page.waitForResponse(response =>
                isAdminPost(response.request()) && hasPostField(response.request(), 'deletepreviewlayerajax') &&
                response.status() === 200,
            {timeout: 10000});
            await deleteSelected.click();
            await confirmation.locator('.btn-danger').click();
            await expect(root).toHaveAttribute('aria-busy', 'true', {timeout: 5000});
            await expect(save).toBeDisabled();
            await capture(page, path.join(env.artifactRoot, 'general-preview-async-busy.png'));
            const payload = await (await deleteResponse).json();
            expect(payload.success).toBe(true);
            await expect(root).toHaveAttribute('aria-busy', 'false', {timeout: 10000});
            await expect(page.locator('.toast, [role="alert"]').filter({hasText: /deleted/i}).first()).toBeVisible({timeout: 10000});
        });

        await test.step('all-layer deletion keeps the page and returns a useful focus', async() => {
            const pageUrl = page.url();
            const deleteResponse = page.waitForResponse(response =>
                isAdminPost(response.request()) && hasPostField(response.request(), 'deletealllayersajax') &&
                response.status() === 200,
            {timeout: 10000});
            await deleteAll.click();
            await expect(confirmation).toBeVisible({timeout: 10000});
            await confirmation.locator('.btn-danger').click();
            const payload = await (await deleteResponse).json();
            expect(payload.success).toBe(true);
            await expect(root).toHaveAttribute('aria-busy', 'false', {timeout: 10000});
            expect(page.url()).toBe(pageUrl);
            await expect(selectedSource.locator('.local-course-banner-builder-empty-layer-list')).toBeVisible({timeout: 10000});
            await expect(selectedSource).toBeFocused({timeout: 10000});
            await expect(page.locator('.toast, [role="alert"]').filter({hasText: /deleted/i}).first()).toBeVisible({timeout: 10000});
        });
        await capture(page, evidence);
    } finally {
        await context.close();
    }
});
