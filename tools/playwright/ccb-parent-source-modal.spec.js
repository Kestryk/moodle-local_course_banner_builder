const {test, expect} = require('@playwright/test');

const requiredEnvironment = () => {
    const names = [
        'EASYEDU_MOODLE_URL',
        'EASYEDU_MOODLE_USERNAME',
        'EASYEDU_MOODLE_PASSWORD',
        'EASYEDU_CCB_PARENT_SOURCE_CHILD_KEY',
        'EASYEDU_CCB_PARENT_SOURCE_VALID_KEY',
        'EASYEDU_CCB_PARENT_SOURCE_DESCENDANT_KEY',
    ];
    const missing = names.filter(name => !process.env[name]);
    if (missing.length) {
        throw new Error('Missing CCB parent-source scenario values: ' + missing.join(', '));
    }
    return {
        baseUrl: String(process.env.EASYEDU_MOODLE_URL).replace(/\/$/, ''),
        username: process.env.EASYEDU_MOODLE_USERNAME,
        password: process.env.EASYEDU_MOODLE_PASSWORD,
        childKey: process.env.EASYEDU_CCB_PARENT_SOURCE_CHILD_KEY,
        validKey: process.env.EASYEDU_CCB_PARENT_SOURCE_VALID_KEY,
        descendantKey: process.env.EASYEDU_CCB_PARENT_SOURCE_DESCENDANT_KEY,
    };
};

const login = async(page, environment) => {
    await page.goto(environment.baseUrl + '/login/index.php', {waitUntil: 'domcontentloaded'});
    await page.locator('#username').fill(environment.username);
    await page.locator('#password').fill(environment.password);
    await page.locator('#loginbtn').click({noWaitAfter: true});
    await expect(page).not.toHaveURL(/\/login\//, {timeout: 30000});
};

test('Change parent modal keeps the table informative and rejects a descendant', async({page}) => {
    const environment = requiredEnvironment();
    await login(page, environment);
    await page.goto(environment.baseUrl + '/local/course_banner_builder/admin_manage.php', {
        waitUntil: 'networkidle',
    });

    const childAction = page.locator(
        '[data-action="local-course-banner-builder-change-source-parent"][data-source-key="' + environment.childKey + '"]'
    );
    const parentCell = childAction.locator('xpath=ancestor::tr[1]').locator(
        '.local-course-banner-builder-source-parent-cell'
    );
    await expect(childAction).toBeVisible();
    await expect(parentCell.locator('form')).toHaveCount(0);

    await childAction.click();
    const modal = page.locator('#local-course-banner-builder-change-source-parent-modal');
    await expect(modal).toBeVisible();
    await expect(modal.locator('[data-source-option][data-value="' + environment.validKey + '"]')).toHaveCount(1);
    await expect(modal.locator('[data-source-option][data-value="' + environment.descendantKey + '"]')).toHaveCount(0);

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

    await modal.locator('[data-action="local-course-banner-builder-cancel-source-parent-change"]').last().click();
    await expect(modal).toBeHidden();
    await expect(childAction).toBeFocused();

    await childAction.click();
    await modal.locator('[data-source-option][data-value="' + environment.validKey + '"]').click();
    const response = page.waitForResponse(response =>
        response.url().includes('/local/course_banner_builder/admin_manage.php') && response.request().method() === 'POST'
    );
    await modal.locator('[data-parent-source-change-submit="1"]').click();
    await expect((await response).status()).toBe(200);
    await expect(modal).toBeHidden();
    await expect(childAction).toBeFocused();
});
