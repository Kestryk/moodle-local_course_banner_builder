const {test, expect, chromium} = require('@playwright/test');
const fs = require('fs');
const path = require('path');

const ensure = (condition, message) => {
    if (!condition) {
        throw new Error(message);
    }
};

const writeJson = (file, value) => {
    fs.mkdirSync(path.dirname(file), {recursive: true});
    fs.writeFileSync(file, JSON.stringify(value, null, 2));
};

const requireEnvironment = () => {
    const required = [
        'EASYEDU_MOODLE_URL',
        'EASYEDU_MOODLE_USERNAME',
        'EASYEDU_MOODLE_PASSWORD',
        'EASYEDU_CCB_IMG07_SOURCE_CATEGORY_ID',
        'EASYEDU_CCB_IMG07_ARTIFACT_ROOT',
        'EASYEDU_CCB_IMG07_MANIFEST',
        'EASYEDU_CCB_IMG07_IMAGE_A',
        'EASYEDU_CCB_IMG07_IMAGE_B',
    ];
    const missing = required.filter(name => !process.env[name]);
    ensure(!missing.length, 'Missing IMG-07 environment values: ' + missing.join(', '));
    const artifactRoot = path.resolve(process.env.EASYEDU_CCB_IMG07_ARTIFACT_ROOT);
    ensure(path.isAbsolute(artifactRoot), 'IMG-07 artifacts must use an absolute external path.');
    ensure(/^\d+$/.test(process.env.EASYEDU_CCB_IMG07_SOURCE_CATEGORY_ID),
        'IMG-07 fixture category must be numeric.');
    for (const image of [process.env.EASYEDU_CCB_IMG07_IMAGE_A, process.env.EASYEDU_CCB_IMG07_IMAGE_B]) {
        ensure(fs.existsSync(image), 'IMG-07 image fixture is unavailable: ' + image);
    }
    return {
        artifactRoot,
        baseUrl: process.env.EASYEDU_MOODLE_URL.replace(/\/$/, ''),
        categoryId: process.env.EASYEDU_CCB_IMG07_SOURCE_CATEGORY_ID,
        imageA: process.env.EASYEDU_CCB_IMG07_IMAGE_A,
        imageB: process.env.EASYEDU_CCB_IMG07_IMAGE_B,
        manifest: process.env.EASYEDU_CCB_IMG07_MANIFEST,
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

const waitForFrames = page => page.evaluate(() => new Promise(resolve => {
    requestAnimationFrame(() => requestAnimationFrame(() => requestAnimationFrame(resolve)));
}));

const readDraft = (form, index) => form.evaluate((currentForm, draftIndex) => {
    const activeIndex = String(currentForm.dataset.activeDraftIndex || '');
    const layer = currentForm.querySelector(
        '[data-preview-current-image="1"][data-preview-draft-layer="1"][data-draft-index="' + draftIndex + '"]'
    );
    const visual = currentForm.querySelector(
        '[data-preview-draft-visual-layer="1"][data-draft-index="' + draftIndex + '"]'
    );
    let settings = {};
    try {
        settings = JSON.parse(currentForm.querySelector('#id_multilayerdraftsettings')?.value || '{}');
    } catch (error) {
        settings = {parseError: String(error)};
    }
    const crop = node => ({
        enabled: node?.getAttribute('data-preview-crop-enabled') || '',
        left: node?.getAttribute('data-preview-crop-left') || '',
        top: node?.getAttribute('data-preview-crop-top') || '',
        width: node?.getAttribute('data-preview-crop-width') || '',
        height: node?.getAttribute('data-preview-crop-height') || '',
    });
    return {
        activeIndex,
        crop: crop(layer),
        fitmode: layer?.getAttribute('data-preview-fitmode') || '',
        settings: settings[draftIndex] || null,
        visualCrop: crop(visual),
        visualFitmode: visual?.getAttribute('data-preview-fitmode') || '',
    };
}, index);

const cropValues = state => ({
    left: state.crop.left,
    top: state.crop.top,
    width: state.crop.width,
    height: state.crop.height,
});

const expectCrop = (actual, expected, label) => {
    expect(actual.crop.enabled, label + ': crop must remain applied').toBe('1');
    expect(cropValues(actual), label + ': current draft crop values').toEqual(expected);
    expect(cropValues({crop: actual.visualCrop}), label + ': visual draft crop values').toEqual(expected);
    expect(Number(actual.settings?.imagecropleftpercent), label + ': draft JSON left').toBe(Number(expected.left));
    expect(Number(actual.settings?.imagecroptoppercent), label + ': draft JSON top').toBe(Number(expected.top));
    expect(Number(actual.settings?.imagecropwidthpercent), label + ': draft JSON width').toBe(Number(expected.width));
    expect(Number(actual.settings?.imagecropheightpercent), label + ': draft JSON height').toBe(Number(expected.height));
};

const selectDraft = async(form, index, label) => {
    const visual = form.locator(
        '[data-preview-draft-visual-layer="1"][data-draft-index="' + index + '"]'
    ).first();
    await expect(visual, label + ': draft visual must be visible for user selection').toBeVisible({timeout: 30000});
    await visual.click();
    await expect.poll(() => form.getAttribute('data-active-draft-index'), {
        message: label + ': selected draft must become active', timeout: 30000,
    }).toBe(String(index));
};

const uploadDraft = async(page, form, imagePath, expectedCount, label) => {
    const addFile = form.locator(
        '#fitem_id_bannerimage_filemanager .fp-btn-add a, ' +
        '#fitem_id_bannerimage_filemanager input.fp-btn-choose'
    ).first();
    await expect(addFile, label + ': Moodle file-manager add action must be available').toBeVisible({timeout: 60000});
    await addFile.click();
    const picker = page.locator('.file-picker:visible').last();
    await expect(picker, label + ': Moodle file picker must open').toBeVisible({timeout: 30000});
    let upload = picker.locator('input[name="repo_upload_file"]:visible').first();
    if (await upload.count() === 0) {
        const uploadRepository = picker.locator('.fp-repo-name', {hasText: /upload/i}).first();
        await expect(uploadRepository, label + ': Upload repository must be available').toBeVisible();
        await uploadRepository.click();
        upload = picker.locator('input[name="repo_upload_file"]:visible').first();
    }
    await expect(upload, label + ': upload input must be visible and ready').toBeVisible({timeout: 30000});
    await upload.setInputFiles(imagePath);
    const name = path.basename(imagePath).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    await expect(upload, label + ': selected image must remain in the file field').toHaveValue(
        new RegExp(name + '$', 'i'), {timeout: 30000}
    );
    const uploadResponse = page.waitForResponse(response => {
        const url = new URL(response.url());
        return response.request().method() === 'POST' &&
            url.pathname === '/repository/repository_ajax.php' &&
            url.searchParams.get('action') === 'upload' && response.status() === 200;
    }, {timeout: 45000});
    const submit = picker.locator('.fp-upload-btn').first();
    await expect(submit, label + ': upload confirmation must be available').toBeVisible();
    await submit.click();
    await uploadResponse;
    await expect(picker, label + ': picker must close after upload').toBeHidden({timeout: 45000});
    await expect.poll(() => form.locator('[data-preview-draft-visual-layer="1"]').count(), {
        message: label + ': uploaded draft visual must render', timeout: 45000,
    }).toBe(expectedCount);
    await expect.poll(async() => form.locator(
        '[data-preview-current-image="1"][data-preview-draft-layer="1"]'
    ).count(), {
        message: label + ': active draft shell must be available', timeout: 45000,
    }).toBe(1);
};

const applyCrop = async(page, form, label, evidence) => {
    const layer = form.locator('[data-preview-current-image="1"][data-preview-draft-layer="1"]').first();
    const cropToggle = form.locator('[data-action="local-course-banner-builder-toggle-modal-preview-crop"]').first();
    await expect(cropToggle, label + ': Crop must be enabled').toBeEnabled({timeout: 30000});
    await cropToggle.click();
    const cropBox = layer.locator('[data-preview-crop-box="1"]').first();
    const handle = layer.locator('[data-preview-crop-handle="se"]').first();
    await expect(cropBox, label + ': crop editor must open').toBeVisible();
    await expect(handle, label + ': southeast crop handle must be visible').toBeVisible();
    const box = await handle.boundingBox();
    ensure(box, label + ': crop handle has no layout box');
    const started = Date.now();
    await page.mouse.move(box.x + box.width / 2, box.y + box.height / 2);
    await page.mouse.down();
    await page.mouse.move(box.x - 70, box.y - 30, {steps: 8});
    await page.mouse.up();
    evidence.gestureMilliseconds[label] = Date.now() - started;
    await expect.poll(() => layer.getAttribute('data-preview-crop-width'), {
        message: label + ': crop resize must change the draft width', timeout: 15000,
    }).not.toBe('100');
    const apply = layer.locator('[data-action="local-course-banner-builder-apply-preview-crop"]').first();
    await expect(apply, label + ': Apply must be available').toBeVisible();
    await apply.click();
    await expect(cropBox, label + ': crop editor must close after Apply').toBeHidden();
};

const cancelRecrop = async(page, form, label, evidence) => {
    const layer = form.locator('[data-preview-current-image="1"][data-preview-draft-layer="1"]').first();
    const cropToggle = form.locator('[data-action="local-course-banner-builder-toggle-modal-preview-crop"]').first();
    await cropToggle.click();
    const handle = layer.locator('[data-preview-crop-handle="se"]').first();
    await expect(handle, label + ': recrop handle must be visible').toBeVisible();
    const box = await handle.boundingBox();
    ensure(box, label + ': recrop handle has no layout box');
    const started = Date.now();
    await page.mouse.move(box.x + box.width / 2, box.y + box.height / 2);
    await page.mouse.down();
    await page.mouse.move(box.x - 45, box.y - 20, {steps: 6});
    await page.mouse.up();
    evidence.gestureMilliseconds[label] = Date.now() - started;
    const cancel = layer.locator('[data-action="local-course-banner-builder-cancel-preview-crop"]').first();
    await expect(cancel, label + ': Cancel must be available').toBeVisible();
    await cancel.click();
    await expect(layer.locator('[data-preview-crop-box="1"]'), label + ': crop editor must close after Cancel').toBeHidden();
};

test('IMG-07 image-modal draft state transitions preserve Crop across Fill, A/B, Cancel, Undo and Redo', async() => {
    test.setTimeout(300000);
    const env = requireEnvironment();
    fs.mkdirSync(env.artifactRoot, {recursive: true});
    const evidenceFile = path.join(env.artifactRoot, 'img-07-evidence.json');
    const traceFile = path.join(env.artifactRoot, 'img-07-trace.zip');
    const consoleErrors = [];
    const pageErrors = [];
    const evidence = {consoleErrors, gestureMilliseconds: {}, pageErrors};
    const browser = await chromium.launch({headless: true});
    const context = await browser.newContext({ignoreHTTPSErrors: true, viewport: {width: 1440, height: 900}});
    await context.tracing.start({screenshots: true, snapshots: true, sources: true});
    const page = await context.newPage();
    page.setDefaultTimeout(30000);
    page.on('console', message => {
        if (message.type() === 'error') {
            consoleErrors.push({location: message.location(), text: message.text()});
        }
    });
    page.on('pageerror', error => pageErrors.push(String(error && error.stack || error)));

    try {
        await login(page, env);
        const url = new URL('/local/course_banner_builder/admin_manage.php', env.baseUrl);
        url.searchParams.set('sourcekey', 'category:' + env.categoryId);
        await page.goto(url.toString(), {waitUntil: 'domcontentloaded', timeout: 60000});
        const trigger = page.locator(
            '[data-target="#local-course-banner-builder-add-layer-modal"], ' +
            '[data-bs-target="#local-course-banner-builder-add-layer-modal"]'
        ).first();
        await expect(trigger, 'Add-image trigger must be available').toBeVisible();
        await trigger.click();
        const modal = page.locator('#local-course-banner-builder-add-layer-modal').first();
        await expect(modal, 'Add-image modal must open').toBeVisible({timeout: 30000});
        const form = modal.locator('form.mform').first();
        const draftItemId = await form.locator('#id_bannerimage_filemanager').inputValue();
        ensure(/^\d+$/.test(draftItemId), 'Draft item id must be numeric before upload.');
        const manifest = JSON.parse(fs.readFileSync(env.manifest, 'utf8').replace(/^\uFEFF/, ''));
        manifest.draftitemid = Number(draftItemId);
        fs.writeFileSync(env.manifest, JSON.stringify(manifest, null, 2));
        await page.evaluate(() => {
            window.__easyEduImg07 = {longTasks: []};
            try {
                const observer = new PerformanceObserver(list => {
                    list.getEntries().forEach(entry => window.__easyEduImg07.longTasks.push({
                        duration: entry.duration,
                        startTime: entry.startTime,
                    }));
                });
                observer.observe({entryTypes: ['longtask']});
                window.__easyEduImg07.longTaskObserver = observer;
            } catch (error) {
                window.__easyEduImg07.longTaskObserverError = String(error);
            }
        });

        await uploadDraft(page, form, env.imageA, 1, 'draft A upload');
        const draftA = await form.getAttribute('data-active-draft-index');
        ensure(/^\d+$/.test(draftA || ''), 'Draft A must become the initial active draft.');
        await applyCrop(page, form, 'crop A Apply', evidence);
        await waitForFrames(page);
        evidence.cropAApplied = await readDraft(form, draftA);
        const appliedCrop = cropValues(evidence.cropAApplied);
        expect(evidence.cropAApplied.crop.enabled, 'Crop A must be applied before Fill').toBe('1');
        await modal.screenshot({path: path.join(env.artifactRoot, 'img-07-a-crop-applied.png')});

        const fill = form.locator('[data-action="local-course-banner-builder-fill-layer-preview-image"]').first();
        await expect(fill, 'Fill must be enabled for draft A').toBeEnabled();
        const fillStarted = Date.now();
        await fill.click();
        evidence.gestureMilliseconds.fillA = Date.now() - fillStarted;
        await waitForFrames(page);
        evidence.afterFillA = await readDraft(form, draftA);
        expectCrop(evidence.afterFillA, appliedCrop, 'Fill A must preserve its applied Crop');
        const fillExpected = await form.evaluate(currentForm => {
            const preview = currentForm.querySelector('[data-layer-banner-preview="1"], [data-source-preview-frame="1"], .local-course-banner-builder-border-preview-frame');
            return preview?.getAttribute('data-default-fitmode') === 'bannerfit' ? '' : 'bannerfit';
        });
        expect(evidence.afterFillA.fitmode, 'Fill A must update the active draft fit mode').toBe(fillExpected);
        expect(evidence.afterFillA.visualFitmode, 'Fill A must update the visual draft fit mode').toBe(fillExpected);
        expect(evidence.afterFillA.settings?.fitmodeoverride, 'Fill A must persist the resolved fit override').toBe(fillExpected);
        await modal.screenshot({path: path.join(env.artifactRoot, 'img-07-a-fill-retains-crop.png')});

        await uploadDraft(page, form, env.imageB, 2, 'draft B upload');
        const draftB = await form.getAttribute('data-active-draft-index');
        ensure(/^\d+$/.test(draftB || '') && draftB !== draftA, 'Upload B must select a distinct second draft.');
        evidence.draftB = await readDraft(form, draftB);
        await modal.screenshot({path: path.join(env.artifactRoot, 'img-07-b-selected.png')});

        const selectAStarted = Date.now();
        await selectDraft(form, draftA, 'switch B to A');
        evidence.gestureMilliseconds.switchBToA = Date.now() - selectAStarted;
        await waitForFrames(page);
        evidence.afterSwitchBackA = await readDraft(form, draftA);
        expectCrop(evidence.afterSwitchBackA, appliedCrop, 'Switch B to A must restore all Crop A values');
        await modal.screenshot({path: path.join(env.artifactRoot, 'img-07-a-restored.png')});

        await cancelRecrop(page, form, 'recrop A Cancel', evidence);
        await waitForFrames(page);
        evidence.afterRecropCancel = await readDraft(form, draftA);
        expectCrop(evidence.afterRecropCancel, appliedCrop, 'Recrop A Cancel must retain the applied Crop');
        await modal.screenshot({path: path.join(env.artifactRoot, 'img-07-a-recrop-cancel.png')});

        await applyCrop(page, form, 'recrop A Apply', evidence);
        await waitForFrames(page);
        evidence.recropAApplied = await readDraft(form, draftA);
        const recropValues = cropValues(evidence.recropAApplied);
        expect(recropValues, 'Recrop A Apply must produce a changed Crop').not.toEqual(appliedCrop);
        const undo = form.locator('[data-action="local-course-banner-builder-undo-modal-preview-change"]').first();
        await expect(undo, 'Undo must be enabled after applying Recrop A').toBeEnabled();
        await undo.click();
        await waitForFrames(page);
        evidence.afterUndo = await readDraft(form, draftA);
        expectCrop(evidence.afterUndo, appliedCrop, 'Undo must restore Crop A applied before recrop');
        const redo = form.locator('[data-action="local-course-banner-builder-redo-modal-preview-change"]').first();
        await expect(redo, 'Redo must be enabled after Undo').toBeEnabled();
        await redo.click();
        await waitForFrames(page);
        evidence.afterRedo = await readDraft(form, draftA);
        expectCrop(evidence.afterRedo, recropValues, 'Redo must restore the reapplied Crop A');
        await modal.screenshot({path: path.join(env.artifactRoot, 'img-07-a-redo.png')});

        evidence.instrumentation = await page.evaluate(() => {
            const result = Object.assign({}, window.__easyEduImg07 || {});
            delete result.longTaskObserver;
            return result;
        });
        evidence.maxLongTaskMilliseconds = Math.max(0,
            ...(evidence.instrumentation.longTasks || []).map(entry => entry.duration || 0));
        evidence.responsiveEnough = Object.values(evidence.gestureMilliseconds).every(value => value < 2500) &&
            evidence.maxLongTaskMilliseconds < 750;
        writeJson(evidenceFile, evidence);
        expect(evidence.instrumentation.longTaskObserverError,
            'Long-task instrumentation must be available in the supervised Chromium run').toBeFalsy();
        expect(evidence.responsiveEnough, 'State-transition gestures must not block the image modal').toBe(true);
        expect(consoleErrors, 'Browser console errors').toEqual([]);
        expect(pageErrors, 'Uncaught page errors').toEqual([]);
    } catch (error) {
        evidence.error = String(error && error.stack || error);
        writeJson(evidenceFile, evidence);
        throw error;
    } finally {
        await context.tracing.stop({path: traceFile}).catch(() => {});
        await context.close().catch(() => {});
        await browser.close().catch(() => {});
    }
});
