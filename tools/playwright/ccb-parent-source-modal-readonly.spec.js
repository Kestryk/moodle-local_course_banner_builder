// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

const {test, expect} = require('@playwright/test');

const moodleUrl = process.env.CCB_MOODLE_URL;
const username = process.env.CCB_MOODLE_USERNAME;
const password = process.env.CCB_MOODLE_PASSWORD;

test('ccb-parent-source-modal-readonly', async({page}, testInfo) => {
    test.setTimeout(90_000);
    test.skip(!moodleUrl || !username || !password, 'Process-local CCB credentials are required.');

    await page.goto(`${moodleUrl.replace(/\/$/, '')}/login/index.php`, {waitUntil: 'domcontentloaded'});
    if (await page.locator('#loginbtn').isVisible()) {
        await page.locator('#username').fill(username);
        await page.locator('#password').fill(password);
        await Promise.all([
            page.waitForURL(url => !url.pathname.endsWith('/login/index.php'), {
                timeout: 30_000,
                waitUntil: 'domcontentloaded',
            }),
            page.locator('#loginbtn').click(),
        ]);
    }

    const writes = [];
    page.on('request', request => {
        if (request.method() === 'POST' && request.url().includes('/local/course_banner_builder/admin_manage.php')) {
            writes.push(request.url());
        }
    });
    await page.setViewportSize({width: 1440, height: 1000});
    await page.goto(`${moodleUrl.replace(/\/$/, '')}/local/course_banner_builder/admin_manage.php`, {
        waitUntil: 'domcontentloaded',
    });

    const action = page.locator(
        '[data-action="local-course-banner-builder-change-source-parent"]'
    ).first();
    await expect(action).toBeVisible();
    await page.screenshot({path: testInfo.outputPath('parent-source-table-readonly.png'), fullPage: true});

    const modal = page.locator('#local-course-banner-builder-change-source-parent-modal');
    const originalParent = await modal.evaluateHandle(node => node.parentElement);
    await action.click();
    await expect(modal).toBeVisible();
    expect(await modal.evaluate(node => node.parentElement === document.body)).toBe(true);
    expect(await modal.evaluate(node => !node.closest('[aria-hidden="true"]'))).toBe(true);
    await page.screenshot({path: testInfo.outputPath('parent-source-modal-readonly.png'), fullPage: true});
    await expect(modal.locator('[data-source-dropdown-label]')).toBeFocused();

    await page.keyboard.press('Escape');
    await expect(modal).toBeHidden();
    expect(await modal.evaluate((node, parent) => node.parentElement === parent, originalParent)).toBe(true);
    await expect(action).toBeFocused();
    expect(writes).toEqual([]);
});
