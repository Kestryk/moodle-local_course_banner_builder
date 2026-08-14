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

const isAdminPost = request => request.method() === 'POST' &&
    request.url().includes('/local/course_banner_builder/admin_manage.php');

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

        const confirmation = page.locator('#local-course-banner-builder-confirm-action-modal');
        await test.step('cancellation preserves layers and focus', async() => {
            await choices.first().check();
            await selectedDelete.click();
            await expect(confirmation).toBeVisible({timeout: 10000});
            await expect(confirmation.locator('.btn-danger')).toBeFocused({timeout: 5000});
            await confirmation.locator('.btn-outline-secondary').click();
            await expect(confirmation).toBeHidden({timeout: 5000});
            await expect(selectedDelete).toBeFocused({timeout: 5000});
            await expect(choices).toHaveCount(5);
        });

        await page.route('**/local/course_banner_builder/admin_manage.php*', async route => {
            if (isAdminPost(route.request())) {
                await new Promise(resolve => setTimeout(resolve, 250));
            }
            await route.continue();
        });
        await test.step('confirmed selected deletion refreshes only the source region', async() => {
            const selectedResponse = page.waitForResponse(response => isAdminPost(response.request()) && response.status() === 200,
                {timeout: 10000});
            await selectedDelete.click();
            await expect(confirmation).toBeVisible({timeout: 10000});
            await confirmation.locator('.btn-danger').click();
            await expect(root).toHaveAttribute('aria-busy', 'true', {timeout: 5000});
            const selectedPayload = await (await selectedResponse).json();
            expect(selectedPayload.success).toBe(true);
            await expect(root).toHaveAttribute('aria-busy', 'false', {timeout: 10000});
            await expect(choices).toHaveCount(4);
            await expect(selectedDelete).toBeFocused({timeout: 10000});
            await expect(page.locator('.toast, [role="alert"]').filter({hasText: /deleted/i}).first()).toBeVisible({timeout: 10000});
        });

        await test.step('confirmed all deletion keeps the page and returns focus', async() => {
            const pageUrl = page.url();
            const allResponse = page.waitForResponse(response => isAdminPost(response.request()) && response.status() === 200,
                {timeout: 10000});
            await allDelete.click();
            await expect(confirmation).toBeVisible({timeout: 10000});
            await confirmation.locator('.btn-danger').click();
            const allPayload = await (await allResponse).json();
            expect(allPayload.success).toBe(true);
            await expect(root).toHaveAttribute('aria-busy', 'false', {timeout: 10000});
            await expect(choices).toHaveCount(0);
            await expect(selectedSource.locator('.local-course-banner-builder-empty-layer-list')).toBeVisible({timeout: 10000});
            expect(page.url()).toBe(pageUrl);
            await expect(selectedSource).toBeFocused({timeout: 10000});
            await expect(page.locator('.toast, [role="alert"]').filter({hasText: /deleted/i}).first()).toBeVisible({timeout: 10000});
        });
        await capture(page, evidence);
    } finally {
        await context.close();
    }
});
