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

const parseRgb = (colour) => {
    const channels = colour.match(/[\d.]+/g)?.slice(0, 3).map(Number);
    if (!channels || channels.length !== 3) {
        throw new Error(`Unsupported computed colour: ${colour}`);
    }
    return channels;
};

const relativeLuminance = (colour) => {
    const channels = parseRgb(colour).map((channel) => {
        const value = channel / 255;
        return value <= 0.03928 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4;
    });
    return (0.2126 * channels[0]) + (0.7152 * channels[1]) + (0.0722 * channels[2]);
};

const contrastRatio = (foreground, background) => {
    const values = [relativeLuminance(foreground), relativeLuminance(background)].sort((a, b) => b - a);
    return (values[0] + 0.05) / (values[1] + 0.05);
};

test('ccb-settings-transfer-parity', async ({page}) => {
    test.skip(
        !moodleUrl || !username || !password,
        'Set process-local CCB Moodle credentials before running this leased scenario.'
    );

    await page.setViewportSize({width: 1600, height: 900});
    await page.goto(moodlePath('/login/index.php'));
    if (await page.locator('#loginbtn').isVisible()) {
        await page.locator('#username').fill(username);
        await page.locator('#password').fill(password);
        await Promise.all([
            page.waitForURL((url) => !url.pathname.endsWith('/login/index.php')),
            page.locator('#loginbtn').click(),
        ]);
    }

    const writeRequests = [];
    const protectedPaths = new Set([
        '/admin/settings.php',
        '/local/course_banner_builder/admin_reset.php',
        '/local/course_banner_builder/admin_transfer.php',
    ]);
    page.on('request', (request) => {
        const method = request.method().toUpperCase();
        const pathname = new URL(request.url()).pathname;
        if (protectedPaths.has(pathname) && ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
            writeRequests.push(`${method} ${pathname}`);
        }
    });

    await page.goto(moodlePath('/admin/settings.php?section=local_course_banner_builder_settings'));
    const settingsPanel = page.locator('#adminsettings fieldset:has(.local-course-banner-builder-settings-hero)');
    const settingsHero = settingsPanel.locator('.local-course-banner-builder-settings-hero');
    const settingsRows = settingsPanel.locator('#admin-enabled, #admin-enabledcustomfields');

    await expect(settingsPanel).toBeVisible();
    await expect(settingsHero).toBeVisible();
    await expect(settingsRows).toHaveCount(2);

    const settingsGeometry = await settingsPanel.evaluate((panel) => {
        const hero = panel.querySelector('.local-course-banner-builder-settings-hero');
        const rows = [...panel.querySelectorAll('#admin-enabled, #admin-enabledcustomfields')];
        const panelRect = panel.getBoundingClientRect();
        const heroRect = hero.getBoundingClientRect();
        const panelStyle = window.getComputedStyle(panel);
        return {
            heroHeight: heroRect.height,
            heroInsidePanel: heroRect.left >= panelRect.left && heroRect.right <= panelRect.right,
            panelMaxWidth: parseFloat(panelStyle.maxWidth),
            panelRadius: parseFloat(panelStyle.borderRadius),
            panelWidth: panelRect.width,
            rowsInsidePanel: rows.every((row) => {
                const rect = row.getBoundingClientRect();
                return rect.left >= panelRect.left && rect.right <= panelRect.right;
            }),
            rowsAreFlat: rows.every((row) => {
                const style = window.getComputedStyle(row);
                return style.borderRadius === '0px' && style.boxShadow === 'none';
            }),
        };
    });
    expect(settingsGeometry.heroHeight).toBeLessThanOrEqual(72);
    expect(settingsGeometry.heroInsidePanel).toBe(true);
    expect(settingsGeometry.panelRadius).toBeGreaterThan(0);
    expect(settingsGeometry.panelRadius).toBeLessThanOrEqual(16);
    expect(settingsGeometry.panelWidth).toBeLessThanOrEqual(settingsGeometry.panelMaxWidth + 2);
    expect(settingsGeometry.rowsInsidePanel).toBe(true);
    expect(settingsGeometry.rowsAreFlat).toBe(true);

    const deleteButton = settingsPanel.locator('.local-course-banner-builder-settings-reset .btn-danger');
    await expect(deleteButton).toBeVisible();
    await expect(deleteButton).toBeEnabled();
    await expect(deleteButton).toHaveAttribute('href', /\/local\/course_banner_builder\/admin_reset\.php$/);

    const deleteColours = await deleteButton.evaluate((button) => {
        const style = window.getComputedStyle(button);
        return {background: style.backgroundColor, foreground: style.color};
    });
    expect(contrastRatio(deleteColours.foreground, deleteColours.background)).toBeGreaterThanOrEqual(4.5);
    const settingsUrl = page.url();
    await deleteButton.focus();
    await expect(deleteButton).toBeFocused();
    expect(page.url()).toBe(settingsUrl);

    await page.goto(moodlePath('/local/course_banner_builder/admin_transfer.php'));
    const transferGrid = page.locator('.local-course-banner-builder-transfer-grid');
    const transferPanels = transferGrid.locator('.local-course-banner-builder-transfer-panel');
    await expect(transferGrid).toBeVisible();
    await expect(transferPanels).toHaveCount(2);

    const transferGeometry = await transferPanels.evaluateAll((panels) => panels.map((panel) => {
        const rect = panel.getBoundingClientRect();
        return {left: rect.left, top: rect.top, width: rect.width};
    }));
    expect(Math.abs(transferGeometry[0].top - transferGeometry[1].top)).toBeLessThanOrEqual(2);
    expect(transferGeometry[1].left).toBeGreaterThan(transferGeometry[0].left + transferGeometry[0].width);
    expect(Math.abs(transferGeometry[0].width - transferGeometry[1].width)).toBeLessThanOrEqual(2);

    for (const panel of await transferPanels.all()) {
        const checkboxes = panel.locator('.form-check input[type="checkbox"]:visible');
        expect(await checkboxes.count()).toBeGreaterThan(0);
        for (const checkbox of await checkboxes.all()) {
            await expect(checkbox).toHaveClass(/\bform-check-input\b/);
        }
    }

    const destructiveCheckbox = page.locator('input[name="replaceall"][type="checkbox"]');
    const destructiveOption = page.locator(
        '.local-course-banner-builder-transfer-panel .mform > .fitem' +
        ':has(input[name="replaceall"][type="checkbox"])'
    );
    await expect(destructiveOption).toBeVisible();
    await expect(destructiveCheckbox).not.toBeChecked();
    const destructiveAlignment = await destructiveOption.evaluate((option) => {
        const formCheck = option.querySelector('.form-check');
        const checkbox = option.querySelector('input[name="replaceall"][type="checkbox"]');
        const checkboxRect = checkbox.getBoundingClientRect();
        const description = option.querySelector(
            `label[for="${checkbox.id}"], #${checkbox.id}_description`
        );
        const descriptionRect = description.getBoundingClientRect();
        return {
            alignItems: window.getComputedStyle(formCheck).alignItems,
            centreDelta: Math.abs(
                (checkboxRect.top + (checkboxRect.height / 2)) -
                (descriptionRect.top + (descriptionRect.height / 2))
            ),
            checkboxPosition: window.getComputedStyle(checkbox).position,
        };
    });
    expect(destructiveAlignment.alignItems).toBe('center');
    expect(destructiveAlignment.centreDelta).toBeLessThanOrEqual(3);
    expect(destructiveAlignment.checkboxPosition).toBe('static');

    const chooseFileButton = page.locator('#fitem_id_configarchive .fp-btn-choose');
    await expect(chooseFileButton).toBeVisible();
    const chooseFileMarginBottom = await chooseFileButton.evaluate((button) => (
        parseFloat(window.getComputedStyle(button).marginBottom)
    ));
    expect(chooseFileMarginBottom).toBeGreaterThanOrEqual(12);

    expect(writeRequests).toEqual([]);
});
