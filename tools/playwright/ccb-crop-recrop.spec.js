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
    const cropValue = name => {
        const field = currentForm.querySelector('#id_' + name) || currentForm.querySelector('[name="' + name + '"]');
        return field ? field.value : null;
    };
    let drafts = {};
    try {
        drafts = JSON.parse(value('#id_multilayerdraftsettings') || '{}');
    } catch (error) {
        drafts = {parseError: String(error)};
    }
    return {
        activeIndex,
        crop: {
            enabled: cropValue('imagecropenabled'),
            height: cropValue('imagecropheightpercent'),
            left: cropValue('imagecropleftpercent'),
            top: cropValue('imagecroptoppercent'),
            width: cropValue('imagecropwidthpercent'),
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

const assertCropBinding = (snapshot, name) => {
    const draft = snapshot.draft || {};
    expect(snapshot.crop, name + ': Crop fields must reflect the active draft payload').toEqual({
        enabled: draft.imagecropenabled ? '1' : '0',
        height: String(draft.imagecropheightpercent),
        left: String(draft.imagecropleftpercent),
        top: String(draft.imagecroptoppercent),
        width: String(draft.imagecropwidthpercent),
    });
};

const draftSelectButton = (form, index) => form.locator(
    '[data-draft-preview-select="1"][data-draft-index="' + index + '"]'
).first();

const login = async(page, env) => {
    await page.goto(env.baseUrl + '/login/index.php', {waitUntil: 'domcontentloaded', timeout: 60000});
    await page.locator('#username').fill(env.username);
    await page.locator('#password').fill(env.password);
    await page.locator('#loginbtn').click({noWaitAfter: true});
    await expect(page).not.toHaveURL(/\/login\//, {timeout: 60000});
};

const completeUpload = async(page, picker, upload) => {
    // Moodle's upload template renders the form and its action as siblings.
    // Scope both to the active content panel; an ancestor-form lookup would
    // never find the button even though the selected file is attached.
    const uploadButton = picker.locator('.fp-content:visible .fp-upload-btn').first();
    await expect(uploadButton, 'Moodle file picker upload action for the attached file').toBeEnabled({timeout: 30000});
    await uploadButton.click();
    const rename = page.getByRole('button', {name: /^Rename to /i}).last();
    const uploadError = page.locator('.file-picker.fp-msg-error:visible').last();
    const outcome = await Promise.race([
        picker.waitFor({state: 'hidden', timeout: 45000}).then(() => 'closed'),
        rename.waitFor({state: 'visible', timeout: 45000}).then(() => 'rename'),
        uploadError.waitFor({state: 'visible', timeout: 45000}).then(() => 'error'),
    ]);
    if (outcome === 'error') {
        throw new Error('Moodle file picker upload error: ' + (await uploadError.innerText()).trim());
    }
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
    const picker = page.locator('.file-picker:not(.fp-msg):visible').last();
    await expect(picker, 'Moodle file picker').toBeVisible({timeout: 30000});
    let upload = picker.locator('.fp-content:visible .fp-file input[name="repo_upload_file"]').last();
    if (await upload.count() === 0) {
        await picker.locator('.fp-repo-name', {hasText: /upload/i}).first().click();
        upload = picker.locator('.fp-content:visible .fp-file input[name="repo_upload_file"]').last();
    }
    await expect(upload, 'Moodle file picker current upload field').toBeVisible({timeout: 30000});
    await upload.setInputFiles(imageFixture);
    await expect.poll(() => upload.evaluate(input => input.files ? input.files.length : 0), {
        message: 'Moodle file picker must receive the selected fixture before upload',
        timeout: 10000,
    }).toBe(1);
    await completeUpload(page, picker, upload);
};

const waitForStableBox = async(locator, name) => {
    let previous = null;
    let stableReads = 0;
    let settled = null;
    await expect.poll(async() => {
        const current = await locator.boundingBox();
        if (!current) {
            previous = null;
            stableReads = 0;
            return stableReads;
        }
        const isStable = previous && ['height', 'width', 'x', 'y'].every(key =>
            Math.abs(current[key] - previous[key]) <= 0.5
        );
        stableReads = isStable ? stableReads + 1 : 0;
        previous = current;
        settled = current;
        return stableReads;
    }, {message: name + ' must settle before pointer input', timeout: 10000}).toBeGreaterThanOrEqual(2);
    ensure(settled, name + ' has no stable layout box.');
    return settled;
};

const changeCrop = async(page, form, scaleDelta) => {
    await form.locator('[data-action="local-course-banner-builder-toggle-modal-preview-crop"]').first().click();
    const activeLayer = form.locator(
        '[data-preview-current-image="1"][data-preview-draft-layer="1"]' +
        '.local-course-banner-builder-preview-image-layer--crop-editing'
    );
    await expect(activeLayer, 'Active draft Crop layer').toBeVisible();
    await expect(activeLayer, 'Exactly one active draft Crop layer').toHaveCount(1);
    const handle = activeLayer.locator('[data-preview-crop-handle="se"]');
    await expect(handle, 'Active Crop southeast handle').toBeVisible();
    const box = await waitForStableBox(handle, 'Active Crop southeast handle');
    const hitOwnsPointer = await handle.evaluate((node, point) => {
        const hit = document.elementFromPoint(point.x, point.y);
        return hit === node || node.contains(hit);
    }, {x: box.x + box.width / 2, y: box.y + box.height / 2});
    expect(hitOwnsPointer, 'Active Crop southeast handle must own its centre point').toBe(true);
    const layerBox = await waitForStableBox(activeLayer, 'Active draft Crop layer');
    const liveCropBefore = await activeLayer.evaluate(node => JSON.stringify({
        height: node.getAttribute('data-preview-crop-height'),
        left: node.getAttribute('data-preview-crop-left'),
        top: node.getAttribute('data-preview-crop-top'),
        width: node.getAttribute('data-preview-crop-width'),
    }));
    const moveX = Math.sign(scaleDelta) * Math.max(8, Math.min(48, layerBox.width * Math.abs(scaleDelta)));
    const moveY = Math.sign(scaleDelta) * Math.max(6, Math.min(36, layerBox.height * Math.abs(scaleDelta)));
    await page.mouse.move(box.x + box.width / 2, box.y + box.height / 2);
    await page.mouse.down();
    await page.mouse.move(box.x + box.width / 2 + moveX, box.y + box.height / 2 + moveY, {steps: 8});
    await page.mouse.up();
    await expect.poll(() => activeLayer.evaluate(node => JSON.stringify({
        height: node.getAttribute('data-preview-crop-height'),
        left: node.getAttribute('data-preview-crop-left'),
        top: node.getAttribute('data-preview-crop-top'),
        width: node.getAttribute('data-preview-crop-width'),
    })), {
        message: 'Active Crop payload must change before Apply',
        timeout: 10000,
    }).not.toBe(liveCropBefore);
};

const cropPointerDiagnostic = async(page, form, env) => {
    await form.locator('[data-action="local-course-banner-builder-toggle-modal-preview-crop"]').first().click();
    const activeLayer = form.locator(
        '[data-preview-current-image="1"][data-preview-draft-layer="1"]' +
        '.local-course-banner-builder-preview-image-layer--crop-editing'
    );
    await expect(activeLayer, 'Diagnostic active draft Crop layer').toBeVisible();
    await expect(activeLayer, 'Diagnostic exactly one active draft Crop layer').toHaveCount(1);
    const handle = activeLayer.locator('[data-preview-crop-handle="se"]');
    await expect(handle, 'Diagnostic active Crop southeast handle').toBeVisible();
    const handleBox = await waitForStableBox(handle, 'Diagnostic active Crop southeast handle');
    const layerBox = await waitForStableBox(activeLayer, 'Diagnostic active draft Crop layer');
    const startPoint = {x: handleBox.x + handleBox.width / 2, y: handleBox.y + handleBox.height / 2};
    const endPoint = {
        x: startPoint.x - Math.max(8, Math.min(48, layerBox.width * 0.12)),
        y: startPoint.y - Math.max(6, Math.min(36, layerBox.height * 0.12)),
    };
    const diagnostic = await activeLayer.evaluate((layer, points) => {
        const handleNode = layer.querySelector('[data-preview-crop-handle="se"]');
        const snapshot = () => ({
            height: layer.getAttribute('data-preview-crop-height'),
            left: layer.getAttribute('data-preview-crop-left'),
            top: layer.getAttribute('data-preview-crop-top'),
            width: layer.getAttribute('data-preview-crop-width'),
        });
        const hit = document.elementFromPoint(points.start.x, points.start.y);
        const records = {
            before: snapshot(),
            directAfter: null,
            directChanged: false,
            hitOwnsPointer: hit === handleNode || (handleNode ? handleNode.contains(hit) : false),
            hitTag: hit ? {
                action: hit.getAttribute('data-action'),
                cropHandle: hit.getAttribute('data-preview-crop-handle'),
                cropBox: hit.getAttribute('data-preview-crop-box'),
                tagName: hit.tagName,
            } : null,
            mutations: [],
            nativeEvents: [],
        };
        ['pointerdown', 'pointermove', 'pointerup', 'pointercancel'].forEach(type => {
            document.addEventListener(type, event => {
                records.nativeEvents.push({
                    button: event.button,
                    buttons: event.buttons,
                    clientX: event.clientX,
                    clientY: event.clientY,
                    phase: event.eventPhase,
                    targetAction: event.target && event.target.getAttribute ? event.target.getAttribute('data-action') : null,
                    targetHandle: event.target && event.target.getAttribute ?
                        event.target.getAttribute('data-preview-crop-handle') : null,
                    targetTag: event.target ? event.target.tagName : null,
                    type,
                });
            }, true);
        });
        const observer = new MutationObserver(entries => {
            entries.forEach(entry => {
                records.mutations.push({
                    attributeName: entry.attributeName,
                    newValue: layer.getAttribute(entry.attributeName),
                    oldValue: entry.oldValue,
                });
            });
        });
        observer.observe(layer, {
            attributeFilter: [
                'data-preview-crop-height',
                'data-preview-crop-left',
                'data-preview-crop-top',
                'data-preview-crop-width',
            ],
            attributeOldValue: true,
        });
        window.__ccbCropPointerDiagnostic = {layer, points, records, observer};
        return records;
    }, {start: startPoint, end: endPoint});

    await page.mouse.move(startPoint.x, startPoint.y);
    await page.mouse.down();
    await page.mouse.move(endPoint.x, endPoint.y, {steps: 8});
    await page.mouse.up();
    await waitForFrames(page);

    const nativeResult = await activeLayer.evaluate(layer => {
        const diagnostic = window.__ccbCropPointerDiagnostic;
        const snapshot = () => ({
            height: layer.getAttribute('data-preview-crop-height'),
            left: layer.getAttribute('data-preview-crop-left'),
            top: layer.getAttribute('data-preview-crop-top'),
            width: layer.getAttribute('data-preview-crop-width'),
        });
        diagnostic.records.afterNative = snapshot();
        diagnostic.records.nativeChanged =
            JSON.stringify(diagnostic.records.afterNative) !== JSON.stringify(diagnostic.records.before);
        return diagnostic.records;
    });

    const directResult = await activeLayer.evaluate(layer => {
        const diagnostic = window.__ccbCropPointerDiagnostic;
        const handleNode = layer.querySelector('[data-preview-crop-handle="se"]');
        const eventFor = (target, point) => ({
            button: 0,
            buttons: 1,
            clientX: point.x,
            clientY: point.y,
            preventDefault: () => {},
            stopPropagation: () => {},
            target,
        });
        const snapshot = () => ({
            height: layer.getAttribute('data-preview-crop-height'),
            left: layer.getAttribute('data-preview-crop-left'),
            top: layer.getAttribute('data-preview-crop-top'),
            width: layer.getAttribute('data-preview-crop-width'),
        });
        window.localCourseBannerBuilderStartCropInteraction(eventFor(handleNode, diagnostic.points.start));
        window.localCourseBannerBuilderHandleCropPointerMove(eventFor(document, diagnostic.points.end));
        window.localCourseBannerBuilderStopCropInteraction();
        diagnostic.records.directAfter = snapshot();
        diagnostic.records.directChanged =
            JSON.stringify(diagnostic.records.directAfter) !== JSON.stringify(diagnostic.records.afterNative);
        diagnostic.observer.disconnect();
        return diagnostic.records;
    });

    writeEvidence(path.join(env.artifactRoot, 'ccb-crop-pointer-diagnostic.json'), {
        scenario: 'EED-CCB-2026-0043-QA2',
        initial: diagnostic,
        nativeResult,
        directResult,
    });
    await form.locator('[data-action="local-course-banner-builder-cancel-preview-crop"]').first().click();
    await waitForFrames(page);
    expect(directResult.hitOwnsPointer, 'Diagnostic handle must own its centre point').toBe(true);
    expect(directResult.nativeEvents.some(event => event.type === 'pointerdown'), 'Diagnostic native pointerdown must be logged').toBe(true);
    expect(directResult.nativeEvents.some(event => event.type === 'pointermove'), 'Diagnostic native pointermove must be logged').toBe(true);
    expect(
        directResult.nativeChanged || directResult.directChanged,
        'Diagnostic must prove whether native or direct Crop handling can mutate the payload'
    ).toBe(true);
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
        // Moodle can retain an earlier hidden Add layer dialog in the DOM. The
        // trigger opens the later, visible instance, so scope every following
        // interaction to that instance rather than DOM order.
        const modal = page.locator('#local-course-banner-builder-add-layer-modal:visible').last();
        await expect(modal).toBeVisible({timeout: 30000});
        const form = modal.locator('form.mform').first();
        await uploadImage(page, form, env.imageFixture, env.manifest);
        await uploadImage(page, form, env.imageFixture, env.manifest);
        const image = form.locator('[data-preview-current-image="1"][data-preview-draft-layer="1"] [data-preview-image-tag="1"]').first();
        await expect.poll(() => image.evaluate(node => node.complete ? node.naturalWidth : 0), {timeout: 45000}).toBeGreaterThan(0);

        if (process.env.EASYEDU_CCB_CROP_POINTER_DIAGNOSTIC === '1') {
            await cropPointerDiagnostic(page, form, env);
            return;
        }

        for (const width of [1440, 760]) {
            await page.setViewportSize({width, height: 960});
            await waitForFrames(page);
            const key = String(width);
            evidence.widths[key] = {};
            evidence.widths[key].before = await cropSnapshot(form);
            assertCropBinding(evidence.widths[key].before, width + ' before Crop');
            await modal.screenshot({path: path.join(env.artifactRoot, 'crop-recrop-' + width + '-before.png')});

            await changeCrop(page, form, -0.12);
            await form.locator('[data-action="local-course-banner-builder-apply-preview-crop"]').first().click();
            await waitForFrames(page);
            evidence.widths[key].afterInitialCrop = await cropSnapshot(form);
            assertPlacement(evidence.widths[key].before, evidence.widths[key].afterInitialCrop, width + ' initial Crop');
            assertCropBinding(evidence.widths[key].afterInitialCrop, width + ' initial Crop');
            expect(evidence.widths[key].afterInitialCrop.crop).not.toEqual(evidence.widths[key].before.crop);
            await modal.screenshot({path: path.join(env.artifactRoot, 'crop-recrop-' + width + '-after-initial.png')});

            await changeCrop(page, form, -0.06);
            await form.locator('[data-action="local-course-banner-builder-cancel-preview-crop"]').first().click();
            await waitForFrames(page);
            evidence.widths[key].afterCancel = await cropSnapshot(form);
            assertPlacement(evidence.widths[key].afterInitialCrop, evidence.widths[key].afterCancel, width + ' Cancel Recrop');
            assertCropBinding(evidence.widths[key].afterCancel, width + ' Cancel Recrop');
            expect(evidence.widths[key].afterCancel.crop).toEqual(evidence.widths[key].afterInitialCrop.crop);
            await modal.screenshot({path: path.join(env.artifactRoot, 'crop-recrop-' + width + '-after-cancel.png')});

            await form.locator('[data-action="local-course-banner-builder-undo-modal-preview-change"]').first().click();
            await waitForFrames(page);
            evidence.widths[key].afterUndo = await cropSnapshot(form);
            assertPlacement(evidence.widths[key].before, evidence.widths[key].afterUndo, width + ' Undo');
            assertCropBinding(evidence.widths[key].afterUndo, width + ' Undo');
            expect(evidence.widths[key].afterUndo.crop).toEqual(evidence.widths[key].before.crop);
            await form.locator('[data-action="local-course-banner-builder-redo-modal-preview-change"]').first().click();
            await waitForFrames(page);
            evidence.widths[key].afterRedo = await cropSnapshot(form);
            assertPlacement(evidence.widths[key].afterInitialCrop, evidence.widths[key].afterRedo, width + ' Redo');
            assertCropBinding(evidence.widths[key].afterRedo, width + ' Redo');
            expect(evidence.widths[key].afterRedo.crop).toEqual(evidence.widths[key].afterInitialCrop.crop);

            // Alter the in-progress Crop without applying it, then switch
            // drafts. A switch must discard that uncommitted interaction and
            // keep the accepted Crop bound to its original draft.
            await changeCrop(page, form, 0.05);
            const originalIndex = evidence.widths[key].afterRedo.activeIndex;
            const draftIndexes = await form.locator('[data-draft-preview-select="1"]').evaluateAll(buttons =>
                buttons.map(button => String(button.dataset.draftIndex || '')).filter(Boolean)
            );
            ensure(draftIndexes.length === 2, width + ': exactly two user-facing draft selectors are required.');
            const alternateIndex = draftIndexes.find(index => index !== originalIndex);
            ensure(alternateIndex, width + ': alternate draft selector is unavailable.');
            await draftSelectButton(form, alternateIndex).click();
            await waitForFrames(page);
            await expect.poll(() => form.getAttribute('data-active-draft-index'), {
                message: width + ': alternate draft selection must settle',
            }).toBe(alternateIndex);
            const alternateDraft = await cropSnapshot(form);
            expect(alternateDraft.crop).not.toEqual(evidence.widths[key].afterRedo.crop);
            await draftSelectButton(form, originalIndex).click();
            await waitForFrames(page);
            await expect.poll(() => form.getAttribute('data-active-draft-index'), {
                message: width + ': original draft selection must settle',
            }).toBe(originalIndex);
            evidence.widths[key].afterDraftSwitch = await cropSnapshot(form);
            assertPlacement(evidence.widths[key].afterRedo, evidence.widths[key].afterDraftSwitch, width + ' draft/image switch');
            assertCropBinding(evidence.widths[key].afterDraftSwitch, width + ' draft/image switch');
            expect(evidence.widths[key].afterDraftSwitch.crop).toEqual(evidence.widths[key].afterRedo.crop);
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
