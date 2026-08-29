const {test, expect} = require('@playwright/test');

const required = [
    'EASYEDU_MOODLE_URL',
    'EASYEDU_MOODLE_USERNAME',
    'EASYEDU_MOODLE_PASSWORD',
    'EASYEDU_CCB_LAYER_SOURCE_CATEGORY_ID',
];

const requireEnvironment = () => {
    const missing = required.filter(name => !process.env[name]);
    if (missing.length) {
        throw new Error('Missing motion/drag scenario environment: ' + missing.join(', '));
    }
};

test.describe('EED-CCB-2026-0044 motion and draggable layer parity', () => {
    test('desktop/mobile simulation, reduced motion, disclosure and drag retain final state', async({page}) => {
        requireEnvironment();
        test.skip(true, 'Source-ready scenario: run only through the approved leased Moodle wrapper.');

        await page.emulateMedia({reducedMotion: 'reduce'});
        const root = page.locator('[data-source-visual-editor="1"]').first();
        const mobile = root.locator('[data-source-preview-mode-value="mobile"]');
        const desktop = root.locator('[data-source-preview-mode-value="desktop"]');
        await mobile.click();
        await expect(root).toHaveAttribute('data-source-preview-mode', 'mobile');
        await desktop.click();
        await expect(root).toHaveAttribute('data-source-preview-mode', 'desktop');

        const disclosure = page.locator('.local-course-banner-builder-layer-details-accordion').first();
        await disclosure.locator('summary').click();
        await expect(disclosure).toHaveAttribute('open', '');
        await disclosure.locator('summary').click();
        await expect(disclosure).not.toHaveAttribute('open', '');

        const rows = page.locator('.local-course-banner-builder-layer-row[draggable="true"]');
        await expect(rows).toHaveCount(2);
        await rows.nth(0).dragTo(rows.nth(1));
        await expect(page.locator('.local-course-banner-builder-layer-drag-preview')).toHaveCount(0);
        await expect(page.locator('.local-course-banner-builder-layer-row-dragging')).toHaveCount(0);
    });
});
