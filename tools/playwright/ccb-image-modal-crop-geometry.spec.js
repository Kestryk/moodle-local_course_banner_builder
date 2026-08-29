const {test, expect, chromium} = require('@playwright/test');
const fs = require('fs');
const path = require('path');

const ensure = (condition, message) => {
    if (!condition) {
        throw new Error(message);
    }
};

const requireEnvironment = () => {
    const required = [
        'EASYEDU_MOODLE_URL',
        'EASYEDU_MOODLE_USERNAME',
        'EASYEDU_MOODLE_PASSWORD',
        'EASYEDU_CCB_IMG08_SOURCE_CATEGORY_ID',
        'EASYEDU_CCB_IMG08_ARTIFACT_ROOT',
        'EASYEDU_CCB_IMG08_IMAGE_FIXTURE',
    ];
    const missing = required.filter(name => !process.env[name]);
    ensure(!missing.length, 'Missing IMG-08 environment values: ' + missing.join(', '));
    const artifactRoot = path.resolve(process.env.EASYEDU_CCB_IMG08_ARTIFACT_ROOT);
    ensure(path.isAbsolute(artifactRoot), 'IMG-08 artifacts must use an absolute external path.');
    ensure(/^\d+$/.test(process.env.EASYEDU_CCB_IMG08_SOURCE_CATEGORY_ID),
        'IMG-08 fixture category must be numeric.');
    ensure(fs.existsSync(process.env.EASYEDU_CCB_IMG08_IMAGE_FIXTURE),
        'IMG-08 image fixture is unavailable.');
    return {
        artifactRoot,
        baseUrl: process.env.EASYEDU_MOODLE_URL.replace(/\/$/, ''),
        categoryId: process.env.EASYEDU_CCB_IMG08_SOURCE_CATEGORY_ID,
        imageFixture: process.env.EASYEDU_CCB_IMG08_IMAGE_FIXTURE,
        password: process.env.EASYEDU_MOODLE_PASSWORD,
        username: process.env.EASYEDU_MOODLE_USERNAME,
    };
};

const waitForFrames = page => page.evaluate(() => new Promise(resolve => {
    requestAnimationFrame(() => requestAnimationFrame(() => requestAnimationFrame(resolve)));
}));

const readGeometry = form => form.evaluate(currentForm => {
    const activeIndex = String(currentForm.dataset.activeDraftIndex || '');
    const current = currentForm.querySelector(
        '[data-preview-current-image="1"][data-preview-draft-layer="1"][data-draft-index="' + activeIndex + '"]'
    );
    const visual = currentForm.querySelector(
        '[data-preview-draft-visual-layer="1"][data-draft-index="' + activeIndex + '"]'
    );
    const rect = node => {
        if (!node) {
            return null;
        }
        const value = node.getBoundingClientRect();
        return {height: value.height, left: value.left, top: value.top, width: value.width};
    };
    const value = selector => currentForm.querySelector(selector)?.value ?? null;
    const placement = layer => layer ? {
        anchor: layer.getAttribute('data-preview-anchor'),
        fitmode: layer.getAttribute('data-preview-fitmode'),
        height: layer.getAttribute('data-preview-custom-height'),
        left: layer.getAttribute('data-preview-offset-left'),
        top: layer.getAttribute('data-preview-offset-top'),
        width: layer.getAttribute('data-preview-custom-width'),
    } : null;
    let drafts = {};
    try {
        drafts = JSON.parse(value('#id_multilayerdraftsettings') || '{}');
    } catch (error) {
        drafts = {parseError: String(error)};
    }
    return {
        activeIndex,
        crop: {
            enabled: value('#id_imagecropenabled'),
            height: value('#id_imagecropheightpercent'),
            left: value('#id_imagecropleftpercent'),
            top: value('#id_imagecroptoppercent'),
            width: value('#id_imagecropwidthpercent'),
        },
        current: {placement: placement(current), rect: rect(current)},
        draft: drafts[activeIndex] || null,
        visual: {placement: placement(visual), rect: rect(visual)},
    };
});

const placementMatches = (before, after, message) => {
    expect(after.current.placement, message + ': current placement').toEqual(before.current.placement);
    expect(after.visual.placement, message + ': visual placement').toEqual(before.visual.placement);
    ['height', 'left', 'top', 'width'].forEach(key => {
        expect(Math.abs(after.current.rect[key] - before.current.rect[key]), message + ': current rect ' + key)
            .toBeLessThanOrEqual(1);
        expect(Math.abs(after.visual.rect[key] - before.visual.rect[key]), message + ': visual rect ' + key)
            .toBeLessThanOrEqual(1);
    });
};

const uploadImage = async(page, form, imageFixture) => {
    const addFile = form.locator(
        '#fitem_id_bannerimage_filemanager .fp-btn-add a, ' +
        '#fitem_id_bannerimage_filemanager input.fp-btn-choose'
    ).first();
    await expect(addFile, 'Moodle file-manager add action must be visible').toBeVisible({timeout: 60000});
    await addFile.click();
    const picker = page.locator('.file-picker:visible').last();
    await expect(picker, 'File picker must open').toBeVisible({timeout: 30000});
    let upload = picker.locator('input[name="repo_upload_file"]').first();
    if (await upload.count() === 0) {
        await picker.locator('.fp-repo-name', {hasText: /upload/i}).first().click();
        upload = picker.locator('input[name="repo_upload_file"]').first();
    }
    await expect(upload, 'Upload input must attach').toBeAttached({timeout: 30000});
    await upload.setInputFiles(imageFixture);
    await picker.locator('.fp-upload-btn').first().click();
    await expect(picker, 'File picker must close after upload').toBeHidden({timeout: 45000});
};

test('IMG-08 Crop and Recrop preserve add-image modal placement and draft state', async() => {
    test.setTimeout(240000);
    const env = requireEnvironment();
    const evidence = {};
    const evidenceFile = path.join(env.artifactRoot, 'img-08-evidence.json');
    const browser = await chromium.launch({headless: true});
    const context = await browser.newContext({ignoreHTTPSErrors: true, viewport: {width: 1440, height: 900}});
    const page = await context.newPage();
    page.setDefaultTimeout(30000);
    try {
        await page.goto(env.baseUrl + '/login/index.php', {waitUntil: 'domcontentloaded', timeout: 60000});
        await page.locator('#username').fill(env.username);
        await page.locator('#password').fill(env.password);
        await page.locator('#loginbtn').click({noWaitAfter: true});
        await expect(page).not.toHaveURL(/\/login\//, {timeout: 60000});
        const url = new URL('/local/course_banner_builder/admin_manage.php', env.baseUrl);
        url.searchParams.set('sourcekey', 'category:' + env.categoryId);
        await page.goto(url.toString(), {waitUntil: 'domcontentloaded', timeout: 60000});

        await page.locator(
            '[data-target="#local-course-banner-builder-add-layer-modal"], ' +
            '[data-bs-target="#local-course-banner-builder-add-layer-modal"]'
        ).first().click();
        const modal = page.locator('#local-course-banner-builder-add-layer-modal').first();
        await expect(modal).toBeVisible({timeout: 30000});
        const form = modal.locator('form.mform').first();
        await uploadImage(page, form, env.imageFixture);
        await uploadImage(page, form, env.imageFixture);
        const currentLayer = form.locator('[data-preview-current-image="1"][data-preview-draft-layer="1"]').first();
        await expect.poll(() => currentLayer.locator('[data-preview-image-tag="1"]').evaluate(image =>
            image.complete ? image.naturalWidth : 0
        ), {timeout: 45000}).toBeGreaterThan(0);
        await waitForFrames(page);
        const before = await readGeometry(form);
        evidence.before = before;

        await form.locator('[data-action="local-course-banner-builder-toggle-modal-preview-crop"]').first().click();
        const cropBox = form.locator('[data-preview-crop-box="1"]').first();
        await expect(cropBox).toBeVisible();
        const handleBox = await cropBox.locator('[data-preview-crop-handle="se"]').first().boundingBox();
        ensure(handleBox, 'Crop southeast handle has no layout box.');
        await page.mouse.move(handleBox.x + handleBox.width / 2, handleBox.y + handleBox.height / 2);
        await page.mouse.down();
        await page.mouse.move(handleBox.x - 70, handleBox.y - 40, {steps: 8});
        await page.mouse.up();
        await form.locator('[data-action="local-course-banner-builder-apply-preview-crop"]').first().click();
        await waitForFrames(page);
        const afterApply = await readGeometry(form);
        evidence.afterApply = afterApply;
        placementMatches(before, afterApply, 'Apply changed Crop');
        expect(afterApply.crop, 'Crop payload must change after the gesture').not.toEqual(before.crop);

        await form.locator('[data-action="local-course-banner-builder-toggle-modal-preview-crop"]').first().click();
        await form.locator('[data-action="local-course-banner-builder-apply-preview-crop"]').first().click();
        await waitForFrames(page);
        const afterUntouchedApply = await readGeometry(form);
        evidence.afterUntouchedApply = afterUntouchedApply;
        placementMatches(afterApply, afterUntouchedApply, 'Untouched Recrop Apply');
        expect(afterUntouchedApply.crop, 'Untouched Recrop Apply must retain the crop payload').toEqual(afterApply.crop);

        await form.locator('[data-action="local-course-banner-builder-undo-modal-preview-change"]').first().click();
        await waitForFrames(page);
        const afterUndo = await readGeometry(form);
        evidence.afterUndo = afterUndo;
        placementMatches(before, afterUndo, 'Undo Crop');
        expect(afterUndo.crop, 'Undo must restore the original crop payload').toEqual(before.crop);

        await form.locator('[data-action="local-course-banner-builder-redo-modal-preview-change"]').first().click();
        await waitForFrames(page);
        const afterRedo = await readGeometry(form);
        evidence.afterRedo = afterRedo;
        placementMatches(afterApply, afterRedo, 'Redo Crop');
        expect(afterRedo.crop, 'Redo must restore the changed crop payload').toEqual(afterApply.crop);

        await form.locator('[data-action="local-course-banner-builder-toggle-modal-preview-crop"]').first().click();
        const recropBox = await form.locator('[data-preview-crop-handle="se"]').first().boundingBox();
        ensure(recropBox, 'Recrop southeast handle has no layout box.');
        await page.mouse.move(recropBox.x + recropBox.width / 2, recropBox.y + recropBox.height / 2);
        await page.mouse.down();
        await page.mouse.move(recropBox.x - 30, recropBox.y - 20, {steps: 6});
        await page.mouse.up();
        await form.locator('[data-preview-draft-visual-layer="1"]').nth(1).click();
        await waitForFrames(page);
        await form.locator('[data-preview-draft-visual-layer="1"]').nth(0).click();
        await waitForFrames(page);
        const afterDraftSwitch = await readGeometry(form);
        evidence.afterDraftSwitch = afterDraftSwitch;
        placementMatches(before, afterDraftSwitch, 'Draft-switch crop commit');
        expect(afterDraftSwitch.crop, 'Draft-switch crop commit must retain changed crop payload').not.toEqual(before.crop);
    } catch (error) {
        evidence.error = String(error && error.stack || error);
        throw error;
    } finally {
        fs.mkdirSync(env.artifactRoot, {recursive: true});
        fs.writeFileSync(evidenceFile, JSON.stringify(evidence, null, 2));
        await context.close().catch(() => {});
        await browser.close().catch(() => {});
    }
});
