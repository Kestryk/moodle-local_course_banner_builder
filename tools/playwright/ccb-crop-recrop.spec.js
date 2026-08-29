const {test, expect, chromium} = require('@playwright/test');
const fs = require('fs');
const path = require('path');

const ensure = (value, message) => {
    if (!value) {
        throw new Error(message);
    }
};

const writeEvidence = (file, evidence) => {
    fs.mkdirSync(path.dirname(file), {recursive: true});
    fs.writeFileSync(file, JSON.stringify(evidence, null, 2));
};

const requireEnvironment = () => {
    const required = [
        'EASYEDU_MOODLE_URL', 'EASYEDU_MOODLE_USERNAME', 'EASYEDU_MOODLE_PASSWORD',
        'EASYEDU_CCB_CROP_SOURCE_CATEGORY_ID', 'EASYEDU_CCB_CROP_ARTIFACT_ROOT',
        'EASYEDU_CCB_CROP_MANIFEST', 'EASYEDU_CCB_CROP_IMAGE_FIXTURE',
    ];
    const missing = required.filter(name => !process.env[name]);
    ensure(!missing.length, 'Missing Crop/Recrop environment values: ' + missing.join(', '));
    const artifactRoot = path.resolve(process.env.EASYEDU_CCB_CROP_ARTIFACT_ROOT);
    ensure(path.isAbsolute(artifactRoot), 'Crop/Recrop artifacts must use an absolute external path.');
    ensure(/^\d+$/.test(process.env.EASYEDU_CCB_CROP_SOURCE_CATEGORY_ID), 'Fixture category must be numeric.');
    ensure(fs.existsSync(process.env.EASYEDU_CCB_CROP_IMAGE_FIXTURE), 'Image fixture is unavailable.');
    return {
        artifactRoot,
        baseUrl: process.env.EASYEDU_MOODLE_URL.replace(/\/$/, ''),
        categoryId: process.env.EASYEDU_CCB_CROP_SOURCE_CATEGORY_ID,
        imageFixture: process.env.EASYEDU_CCB_CROP_IMAGE_FIXTURE,
        manifest: process.env.EASYEDU_CCB_CROP_MANIFEST,
        password: process.env.EASYEDU_MOODLE_PASSWORD,
        username: process.env.EASYEDU_MOODLE_USERNAME,
    };
};

const waitForFrames = page => page.evaluate(() => new Promise(resolve => {
    requestAnimationFrame(() => requestAnimationFrame(() => requestAnimationFrame(resolve)));
}));

const cropSnapshot = form => form.evaluate(currentForm => {
    const activeIndex = String(currentForm.dataset.activeDraftIndex || '');
    const active = currentForm.querySelector(
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
    const state = node => node ? {
        anchor: node.getAttribute('data-preview-anchor'),
        fitmode: node.getAttribute('data-preview-fitmode'),
        height: node.getAttribute('data-preview-custom-height'),
        left: node.getAttribute('data-preview-offset-left'),
        top: node.getAttribute('data-preview-offset-top'),
        width: node.getAttribute('data-preview-custom-width'),
    } : null;
    const value = selector => currentForm.querySelector(selector)?.value ?? null;
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
        current: {placement: state(active), rect: rect(active)},
        draft: drafts[activeIndex] || null,
        visual: {placement: state(visual), rect: rect(visual)},
    };
});

const assertPlacement = (before, after, name) => {
    expect(after.current.placement, name + ': active placement').toEqual(before.current.placement);
    expect(after.visual.placement, name + ': visual placement').toEqual(before.visual.placement);
    for (const key of ['height', 'left', 'top', 'width']) {
        expect(Math.abs(after.current.rect[key] - before.current.rect[key]), name + ': active ' + key).toBeLessThanOrEqual(1);
        expect(Math.abs(after.visual.rect[key] - before.visual.rect[key]), name + ': visual ' + key).toBeLessThanOrEqual(1);
    }
};

const login = async(page, env) => {
    await page.goto(env.baseUrl + '/login/index.php', {waitUntil: 'domcontentloaded', timeout: 60000});
    await page.locator('#username').fill(env.username);
    await page.locator('#password').fill(env.password);
    await page.locator('#loginbtn').click({noWaitAfter: true});
    await expect(page).not.toHaveURL(/\/login\//, {timeout: 60000});
};

const completeUpload = async(page, picker) => {
    await picker.locator('.fp-upload-btn').first().click();
    const rename = page.getByRole('button', {name: /^Rename to /i}).last();
    const outcome = await Promise.race([
        picker.waitFor({state: 'hidden', timeout: 45000}).then(() => 'closed'),
        rename.waitFor({state: 'visible', timeout: 45000}).then(() => 'rename'),
    ]);
    if (outcome === 'rename') {
        await rename.click();
        await expect(picker, 'Moodle file picker must close after naming the second draft').toBeHidden({timeout: 45000});
    }
};

const uploadImage = async(page, form, imageFixture, manifest) => {
    const draftItemId = await form.locator('#id_bannerimage_filemanager').inputValue();
    ensure(/^\d+$/.test(draftItemId), 'Draft item id must be numeric before upload.');
    const saved = JSON.parse(fs.readFileSync(manifest, 'utf8').replace(/^\uFEFF/, ''));
    saved.draftitemid = Number(draftItemId);
    fs.writeFileSync(manifest, JSON.stringify(saved, null, 2));
    const addFile = form.locator('#fitem_id_bannerimage_filemanager .fp-btn-add a, #fitem_id_bannerimage_filemanager input.fp-btn-choose').first();
    await expect(addFile, 'Moodle file-manager add action').toBeVisible({timeout: 60000});
    await addFile.click();
    const picker = page.locator('.file-picker:visible').last();
    await expect(picker, 'Moodle file picker').toBeVisible({timeout: 30000});
    let upload = picker.locator('input[name="repo_upload_file"]').first();
    if (await upload.count() === 0) {
        await picker.locator('.fp-repo-name', {hasText: /upload/i}).first().click();
        upload = picker.locator('input[name="repo_upload_file"]').first();
    }
    await expect(upload).toBeAttached({timeout: 30000});
    await upload.setInputFiles(imageFixture);
    await completeUpload(page, picker);
};

const changeCrop = async(page, form, direction) => {
    await form.locator('[data-action="local-course-banner-builder-toggle-modal-preview-crop"]').first().click();
    const handle = form.locator('[data-preview-crop-handle="se"]').first();
    await expect(handle, 'Crop southeast handle').toBeVisible();
    const box = await handle.boundingBox();
    ensure(box, 'Crop southeast handle has no layout box.');
    await page.mouse.move(box.x + box.width / 2, box.y + box.height / 2);
    await page.mouse.down();
    await page.mouse.move(box.x + box.width / 2 + direction.x, box.y + box.height / 2 + direction.y, {steps: 8});
    await page.mouse.up();
};

test('EED-CCB-2026-0043-QA1 Crop and Recrop preserve image placement across widths', async() => {
    test.setTimeout(300000);
    const env = requireEnvironment();
    const evidence = {scenario: 'EED-CCB-2026-0043-QA1', widths: {}};
    const evidenceFile = path.join(env.artifactRoot, 'ccb-crop-recrop-evidence.json');
    const browser = await chromium.launch({headless: true});
    const context = await browser.newContext({ignoreHTTPSErrors: true, viewport: {width: 1440, height: 960}});
    const page = await context.newPage();
    page.setDefaultTimeout(30000);
    try {
        await login(page, env);
        const url = new URL('/local/course_banner_builder/admin_manage.php', env.baseUrl);
        url.searchParams.set('sourcekey', 'category:' + env.categoryId);
        await page.goto(url.toString(), {waitUntil: 'domcontentloaded', timeout: 60000});
        await page.locator('[data-target="#local-course-banner-builder-add-layer-modal"], [data-bs-target="#local-course-banner-builder-add-layer-modal"]').first().click();
        const modal = page.locator('#local-course-banner-builder-add-layer-modal').first();
        await expect(modal).toBeVisible({timeout: 30000});
        const form = modal.locator('form.mform').first();
        await uploadImage(page, form, env.imageFixture, env.manifest);
        await uploadImage(page, form, env.imageFixture, env.manifest);
        const image = form.locator('[data-preview-current-image="1"][data-preview-draft-layer="1"] [data-preview-image-tag="1"]').first();
        await expect.poll(() => image.evaluate(node => node.complete ? node.naturalWidth : 0), {timeout: 45000}).toBeGreaterThan(0);

        for (const width of [1440, 760]) {
            await page.setViewportSize({width, height: 960});
            await waitForFrames(page);
            const key = String(width);
            evidence.widths[key] = {};
            evidence.widths[key].before = await cropSnapshot(form);
            await modal.screenshot({path: path.join(env.artifactRoot, 'crop-recrop-' + width + '-before.png')});

            await changeCrop(page, form, {x: -64, y: -36});
            await form.locator('[data-action="local-course-banner-builder-apply-preview-crop"]').first().click();
            await waitForFrames(page);
            evidence.widths[key].afterInitialCrop = await cropSnapshot(form);
            assertPlacement(evidence.widths[key].before, evidence.widths[key].afterInitialCrop, width + ' initial Crop');
            expect(evidence.widths[key].afterInitialCrop.crop).not.toEqual(evidence.widths[key].before.crop);
            await modal.screenshot({path: path.join(env.artifactRoot, 'crop-recrop-' + width + '-after-initial.png')});

            await changeCrop(page, form, {x: -26, y: -18});
            await form.locator('[data-action="local-course-banner-builder-cancel-preview-crop"]').first().click();
            await waitForFrames(page);
            evidence.widths[key].afterCancel = await cropSnapshot(form);
            assertPlacement(evidence.widths[key].afterInitialCrop, evidence.widths[key].afterCancel, width + ' Cancel Recrop');
            expect(evidence.widths[key].afterCancel.crop).toEqual(evidence.widths[key].afterInitialCrop.crop);
            await modal.screenshot({path: path.join(env.artifactRoot, 'crop-recrop-' + width + '-after-cancel.png')});

            await form.locator('[data-action="local-course-banner-builder-undo-modal-preview-change"]').first().click();
            await waitForFrames(page);
            evidence.widths[key].afterUndo = await cropSnapshot(form);
            assertPlacement(evidence.widths[key].before, evidence.widths[key].afterUndo, width + ' Undo');
            expect(evidence.widths[key].afterUndo.crop).toEqual(evidence.widths[key].before.crop);
            await form.locator('[data-action="local-course-banner-builder-redo-modal-preview-change"]').first().click();
            await waitForFrames(page);
            evidence.widths[key].afterRedo = await cropSnapshot(form);
            assertPlacement(evidence.widths[key].afterInitialCrop, evidence.widths[key].afterRedo, width + ' Redo');
            expect(evidence.widths[key].afterRedo.crop).toEqual(evidence.widths[key].afterInitialCrop.crop);

            await changeCrop(page, form, {x: -18, y: -12});
            await form.locator('[data-preview-draft-visual-layer="1"]').nth(1).click();
            await waitForFrames(page);
            await form.locator('[data-preview-draft-visual-layer="1"]').nth(0).click();
            await waitForFrames(page);
            evidence.widths[key].afterDraftSwitch = await cropSnapshot(form);
            assertPlacement(evidence.widths[key].afterRedo, evidence.widths[key].afterDraftSwitch, width + ' draft/image switch');
            expect(evidence.widths[key].afterDraftSwitch.crop).not.toEqual(evidence.widths[key].afterRedo.crop);
            await modal.screenshot({path: path.join(env.artifactRoot, 'crop-recrop-' + width + '-after-switch.png')});
        }
    } catch (error) {
        evidence.error = String(error && error.stack || error);
        throw error;
    } finally {
        writeEvidence(evidenceFile, evidence);
        await context.close().catch(() => {});
        await browser.close().catch(() => {});
    }
});
