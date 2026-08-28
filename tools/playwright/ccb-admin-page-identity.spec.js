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

const moodlePath = (path) => {
    const base = moodleUrl.endsWith('/') ? moodleUrl : `${moodleUrl}/`;
    return new URL(path.replace(/^\//, ''), base).toString();
};

test('ccb-admin-page-identity', async ({page}) => {
    test.setTimeout(90_000);
    test.skip(
        !moodleUrl || !username || !password,
        'Set process-local CCB Moodle credentials before running this leased scenario.'
    );

    const writeRequests = [];
    const protectedPaths = new Set([
        '/local/course_banner_builder/admin_manage.php',
        '/local/course_banner_builder/admin_site.php',
        '/local/course_banner_builder/admin_transfer.php',
    ]);
    page.on('request', (request) => {
        const method = request.method().toUpperCase();
        const pathname = new URL(request.url()).pathname;
        if (protectedPaths.has(pathname) && ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
            writeRequests.push(`${method} ${pathname}`);
        }
    });

    await page.setViewportSize({width: 390, height: 844});
    await page.goto(moodlePath('/login/index.php'));
    if (await page.locator('#loginbtn').isVisible()) {
        await page.locator('#username').fill(username);
        await page.locator('#password').fill(password);
        await Promise.all([
            page.waitForURL((url) => !url.pathname.endsWith('/login/index.php')),
            page.locator('#loginbtn').click(),
        ]);
    }

    const pages = [
        {path: '/local/course_banner_builder/admin_manage.php', variant: 'course'},
        {path: '/local/course_banner_builder/admin_site.php', variant: 'site'},
        {path: '/local/course_banner_builder/admin_transfer.php', variant: 'transfer'},
    ];

    for (const target of pages) {
        await page.goto(moodlePath(target.path));
        const identity = page.locator('[data-region="local-course-banner-builder-page-identity"]');
        const navigation = page.locator('[data-easyedu-navigation]');

        await expect(identity).toBeVisible();
        await expect(identity).toHaveAttribute('data-page-identity-variant', target.variant);
        await expect(identity.locator('.local-course-banner-builder-page-identity__brand')).not.toBeEmpty();
        await expect(identity.locator('.local-course-banner-builder-page-identity__title')).not.toBeEmpty();
        await expect(identity.locator('.local-course-banner-builder-page-identity__description')).not.toBeEmpty();

        const geometry = await identity.evaluate((header) => {
            const navigationElement = document.querySelector('[data-easyedu-navigation]');
            const headerRect = header.getBoundingClientRect();
            return {
                identityBeforeNavigation: Boolean(navigationElement) && Boolean(
                    header.compareDocumentPosition(navigationElement) & Node.DOCUMENT_POSITION_FOLLOWING
                ),
                overflowsViewport: headerRect.left < 0 || headerRect.right > window.innerWidth,
            };
        });

        expect(geometry.identityBeforeNavigation).toBe(true);
        expect(geometry.overflowsViewport).toBe(false);
        await expect(navigation).toBeVisible();
    }

    expect(writeRequests).toEqual([]);
});
