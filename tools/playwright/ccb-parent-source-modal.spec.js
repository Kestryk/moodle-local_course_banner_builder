const {test, expect} = require('@playwright/test');
const fs = require('fs');
const path = require('path');

const isWithinPath = (target, parent) => {
    const relative = path.relative(parent, target);
    return relative === '' || (!relative.startsWith(`..${path.sep}`) && relative !== '..' && !path.isAbsolute(relative));
};

const requiredEnvironment = () => {
    const names = [
        'EASYEDU_MOODLE_URL',
        'EASYEDU_MOODLE_USERNAME',
        'EASYEDU_MOODLE_PASSWORD',
        'EASYEDU_CCB_PARENT_SOURCE_CHILD_KEY',
        'EASYEDU_CCB_PARENT_SOURCE_VALID_KEY',
        'EASYEDU_CCB_PARENT_SOURCE_DESCENDANT_KEY',
        'EASYEDU_CCB_PARENT_SOURCE_ARTIFACT_ROOT',
    ];
    const missing = names.filter(name => !process.env[name]);
    if (missing.length) {
        throw new Error('Missing CCB parent-source scenario values: ' + missing.join(', '));
    }
    const artifactRoot = path.resolve(process.env.EASYEDU_CCB_PARENT_SOURCE_ARTIFACT_ROOT);
    const repositoryRoot = path.resolve(__dirname, '../..');
    if (!path.isAbsolute(process.env.EASYEDU_CCB_PARENT_SOURCE_ARTIFACT_ROOT) ||
            isWithinPath(artifactRoot, repositoryRoot)) {
        throw new Error('EASYEDU_CCB_PARENT_SOURCE_ARTIFACT_ROOT must be an absolute path outside the repository.');
    }
    return {
        baseUrl: String(process.env.EASYEDU_MOODLE_URL).replace(/\/$/, ''),
        username: process.env.EASYEDU_MOODLE_USERNAME,
        password: process.env.EASYEDU_MOODLE_PASSWORD,
        childKey: process.env.EASYEDU_CCB_PARENT_SOURCE_CHILD_KEY,
        validKey: process.env.EASYEDU_CCB_PARENT_SOURCE_VALID_KEY,
        descendantKey: process.env.EASYEDU_CCB_PARENT_SOURCE_DESCENDANT_KEY,
        artifactRoot,
    };
};

const artifact = (environment, name) => {
    fs.mkdirSync(environment.artifactRoot, {recursive: true});
    return path.join(environment.artifactRoot, name);
};

const login = async(page, environment) => {
    await page.goto(environment.baseUrl + '/login/index.php', {waitUntil: 'domcontentloaded'});
    await page.locator('#username').fill(environment.username);
    await page.locator('#password').fill(environment.password);
    await page.locator('#loginbtn').click({noWaitAfter: true});
    await expect(page).not.toHaveURL(/\/login\//, {timeout: 30000});
};

test('Change parent modal keeps the table informative and rejects a descendant', async({page}) => {
    test.setTimeout(120000);
    const environment = requiredEnvironment();
    await login(page, environment);
    await page.goto(environment.baseUrl + '/local/course_banner_builder/admin_manage.php', {
        waitUntil: 'domcontentloaded',
        timeout: 60000,
    });

    const childAction = page.locator(
        '[data-action="local-course-banner-builder-change-source-parent"][data-source-key="' + environment.childKey + '"]'
    );
    const parentCell = childAction.locator('xpath=ancestor::tr[1]').locator(
        '.local-course-banner-builder-source-parent-cell'
    );
    await expect(childAction).toBeVisible();
    await expect(childAction.locator('.fa-pen')).toHaveCount(1);
    await expect(parentCell.locator('form')).toHaveCount(0);
    await page.screenshot({
        path: artifact(environment, 'configured-sources-before.png'),
        fullPage: true,
    });

    const modal = page.locator('#local-course-banner-builder-change-source-parent-modal');
    const originParent = await modal.evaluateHandle(node => node.parentElement);
    await childAction.click();
    await expect(modal).toBeVisible();
    await expect(page.locator('.modal-backdrop')).toBeVisible();
    expect(await modal.evaluate(node => node.parentElement === document.body)).toBe(true);
    expect(await modal.evaluate(node => !node.closest('[aria-hidden="true"]'))).toBe(true);
    await expect(modal.locator('[data-source-dropdown-label]')).toBeFocused();
    const saveButton = modal.locator('[data-parent-source-change-submit="1"]');
    await expect(saveButton).toHaveClass(/btn-sm/);
    expect(await saveButton.evaluate(button => button.getBoundingClientRect().height)).toBeLessThanOrEqual(38);
    await expect(modal.locator('[data-source-option][data-value="' + environment.validKey + '"]')).toHaveCount(1);
    await expect(modal.locator('[data-source-option][data-value="' + environment.descendantKey + '"]')).toHaveCount(0);
    await page.screenshot({
        path: artifact(environment, 'parent-source-modal-open.png'),
        fullPage: true,
    });

    await page.keyboard.press('Escape');
    await expect(modal).toBeHidden();
    await expect(page.locator('.modal-backdrop')).toHaveCount(0);
    expect(await modal.evaluate((node, parent) => node.parentElement === parent, originParent)).toBe(true);
    await expect(childAction).toBeFocused();

    await childAction.click();
    await expect(modal).toBeVisible();
    await modal.click({position: {x: 4, y: 4}});
    await expect(modal).toBeHidden();
    await expect(page.locator('.modal-backdrop')).toHaveCount(0);
    expect(await modal.evaluate((node, parent) => node.parentElement === parent, originParent)).toBe(true);
    await expect(childAction).toBeFocused();

    await childAction.click();
    await expect(modal).toBeVisible();

    const forged = await page.evaluate(async({childKey, descendantKey}) => {
        const action = document.querySelector(
            '[data-action="local-course-banner-builder-change-source-parent"][data-source-key="' + childKey + '"]'
        );
        const body = new URLSearchParams({
            updatesourceparentfield: '1',
            sourcekey: childKey,
            fieldvalue: descendantKey,
            sesskey: action?.getAttribute('data-source-sesskey') || '',
        });
        const response = await fetch(window.location.href, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body,
        });
        return {status: response.status, body: await response.json()};
    }, {childKey: environment.childKey, descendantKey: environment.descendantKey});
    expect(forged.status).toBe(422);
    expect(forged.body.ok).toBe(false);
    await expect(modal).toBeVisible();

    let cancelRequests = 0;
    const countCancelRequest = request => {
        if (request.method() === 'POST' && request.url().includes('/local/course_banner_builder/admin_manage.php')) {
            cancelRequests += 1;
        }
    };
    page.on('request', countCancelRequest);
    await modal.locator('[data-action="local-course-banner-builder-cancel-source-parent-change"]').last().click();
    await expect(modal).toBeHidden();
    await expect(page.locator('.modal-backdrop')).toHaveCount(0);
    expect(await modal.evaluate((node, parent) => node.parentElement === parent, originParent)).toBe(true);
    await expect(childAction).toBeFocused();
    expect(cancelRequests).toBe(0);
    page.off('request', countCancelRequest);

    await childAction.click();
    await modal.locator('[data-source-option][data-value="' + environment.validKey + '"]').click();
    await modal.locator('[data-source-dropdown-label]').click();
    await expect(modal.locator('.local-course-banner-builder-source-dropdown-menu')).toBeVisible();
    await page.screenshot({
        path: artifact(environment, 'parent-source-modal-options.png'),
        fullPage: true,
    });
    await modal.locator('[data-source-dropdown-label]').click();
    const response = page.waitForResponse(response =>
        response.url().includes('/local/course_banner_builder/admin_manage.php') && response.request().method() === 'POST'
    );
    await modal.locator('[data-parent-source-change-submit="1"]').click();
    await expect((await response).status()).toBe(200);
    await expect(modal).toBeHidden();
    await expect(page.locator('.modal-backdrop')).toHaveCount(0);
    expect(await modal.evaluate((node, parent) => node.parentElement === parent, originParent)).toBe(true);
    await expect(childAction).toBeFocused();
    await page.screenshot({
        path: artifact(environment, 'configured-sources-after.png'),
        fullPage: true,
    });

    await page.goto(
        environment.baseUrl + '/local/course_banner_builder/admin_manage.php?sourcekey=' +
            encodeURIComponent(environment.childKey),
        {waitUntil: 'domcontentloaded', timeout: 60000}
    );
    const selectedTrigger = page.locator(
        '[data-selected-source-content="1"] ' +
        '[data-action="local-course-banner-builder-change-source-parent"][data-source-key="' +
            environment.childKey + '"]'
    );
    await expect(selectedTrigger).toBeVisible();
    await expect(selectedTrigger.locator('.fa-pen')).toHaveCount(1);
    await selectedTrigger.click();
    await expect(modal).toBeVisible();
    await page.screenshot({
        path: artifact(environment, 'selected-source-parent-modal-open.png'),
        fullPage: true,
    });
    await page.keyboard.press('Escape');
    await expect(modal).toBeHidden();
    await expect(selectedTrigger).toBeFocused();
});
