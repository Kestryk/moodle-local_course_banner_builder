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

const draftSelectButton = (form, index) => form.locator(
    '[data-draft-preview-select="1"][data-draft-index="' + index + '"]'
).first();

const expectSelectedDraftButton = async(form, indexes, selectedIndex, label, cdpAssertion = null) => {
    const buttons = form.locator('[data-draft-preview-select="1"]');
    await expect(buttons, label + ': both draft selectors must be available').toHaveCount(2, {timeout: 30000});
    const cdpControls = cdpAssertion ? await captureDraftSelectorAccessibilityAudit(
        cdpAssertion.page, cdpAssertion.context, form, indexes, 'assertion', '', false
    ) : null;
    for (const index of indexes) {
        const selector = draftSelectButton(form, index);
        await expect(selector, label + ': draft selector accessible name').toBeVisible();
        if (cdpControls) {
            const control = cdpControls.controls.find(candidate => String(candidate.index) === String(index));
            const focusable = control?.axNode?.properties?.find(property => property.name === 'focusable');
            expect(control?.axNode?.role, label + ': CDP selector role').toBe('button');
            expect(control?.axNode?.name, label + ': CDP localized selector name').toMatch(/^Select image \d+$/);
            expect(control?.axNode?.ignored, label + ': CDP selector must not be ignored').toBe(false);
            expect(focusable?.value?.value, label + ': CDP selector must be focusable').toBe(true);
        } else {
            await expect(selector, label + ': localized accessible selector name').toHaveAccessibleName(/^Select image \d+$/);
        }
        await expect(selector, label + ': draft selector pressed state').toHaveAttribute(
            'aria-pressed', String(index) === String(selectedIndex) ? 'true' : 'false'
        );
    }
};

const selectDraft = async(form, index, label) => {
    const selector = draftSelectButton(form, index);
    await expect(selector, label + ': accessible draft selector must be visible for user selection').toBeVisible({timeout: 30000});
    await selector.click();
    await expect.poll(() => form.getAttribute('data-active-draft-index'), {
        message: label + ': selected draft must become active', timeout: 30000,
    }).toBe(String(index));
    await expect(selector, label + ': selected draft must expose aria-pressed').toHaveAttribute('aria-pressed', 'true');
};

const readDraftSelectorDom = (form, indexes) => form.evaluate((currentForm, draftIndexes) => {
    const describe = element => {
        if (!element) {
            return null;
        }
        const style = window.getComputedStyle(element);
        const attributes = {};
        Array.from(element.attributes).forEach(attribute => {
            attributes[attribute.name] = attribute.value;
        });
        return {
            attributes,
            className: element.className || '',
            hidden: element.hidden,
            id: element.id || '',
            inert: element.inert,
            tagName: element.tagName,
            computed: {
                display: style.display,
                visibility: style.visibility,
            },
        };
    };
    const ancestry = element => {
        const result = [];
        let current = element;
        while (current) {
            result.push(describe(current));
            current = current.parentElement;
        }
        return result;
    };
    return draftIndexes.map(index => {
        const control = currentForm.querySelector(
            '[data-draft-preview-select="1"][data-draft-index="' + index + '"]'
        );
        const labelledby = (control?.getAttribute('aria-labelledby') || '').trim().split(/\s+/).filter(Boolean);
        return {
            index: String(index),
            control: describe(control),
            ancestors: ancestry(control),
            labelledby: labelledby.map(id => ({
                id,
                target: describe(document.getElementById(id)),
                text: document.getElementById(id)?.textContent?.trim() || '',
            })),
        };
    });
}, indexes);

const simplifyAxNode = node => ({
    backendDOMNodeId: node.backendDOMNodeId || null,
    childIds: node.childIds || [],
    description: node.description?.value || '',
    ignored: !!node.ignored,
    ignoredReasons: node.ignoredReasons || [],
    name: node.name?.value || '',
    nodeId: node.nodeId,
    parentId: node.parentId || null,
    properties: node.properties || [],
    role: node.role?.value || '',
});

const axAncestors = (node, nodesById) => {
    const ancestors = [];
    let current = node;
    while (current) {
        ancestors.push(simplifyAxNode(current));
        current = current.parentId ? nodesById.get(current.parentId) : null;
    }
    return ancestors;
};

const captureDraftSelectorAccessibilityAudit = async(page, context, form, indexes, stage, artifactRoot, captureScreenshot = true) => {
    const dom = await readDraftSelectorDom(form, indexes);
    const cdp = await context.newCDPSession(page);
    try {
        await cdp.send('Accessibility.enable');
        const documentRoot = await cdp.send('DOM.getDocument', {depth: 1, pierce: true});
        const fullTree = await cdp.send('Accessibility.getFullAXTree');
        const nodesById = new Map((fullTree.nodes || []).map(node => [node.nodeId, node]));
        const controls = [];
        for (const entry of dom) {
            const selector = '#local-course-banner-builder-add-layer-modal ' +
                '[data-draft-preview-select="1"][data-draft-index="' + entry.index + '"]';
            const node = await cdp.send('DOM.querySelector', {
                nodeId: documentRoot.root.nodeId,
                selector,
            });
            const described = node.nodeId ? await cdp.send('DOM.describeNode', {
                nodeId: node.nodeId,
                depth: 0,
                pierce: true,
            }) : {node: null};
            const backendNodeId = described.node?.backendNodeId || null;
            const partialTree = backendNodeId ? await cdp.send('Accessibility.getPartialAXTree', {
                backendNodeId,
                fetchRelatives: true,
            }) : {nodes: []};
            const axNode = backendNodeId ? (fullTree.nodes || []).find(candidate =>
                candidate.backendDOMNodeId === backendNodeId
            ) : null;
            controls.push({
                axAncestors: axNode ? axAncestors(axNode, nodesById) : [],
                axNode: axNode ? simplifyAxNode(axNode) : null,
                backendNodeId,
                domNodeId: node.nodeId || null,
                index: entry.index,
                partialTree: partialTree.nodes || [],
            });
        }
        if (captureScreenshot) {
            const screenshot = await cdp.send('Page.captureScreenshot', {
                format: 'png', fromSurface: true, captureBeyondViewport: false,
            });
            fs.writeFileSync(path.join(artifactRoot, 'img-07-ax-' + stage + '.png'), screenshot.data, 'base64');
        }
        return {controls, dom, fullTree: fullTree.nodes || [], stage};
    } finally {
        await cdp.detach().catch(() => {});
    }
};

const capturePlaywrightDraftSelectorAccessibility = async(form, indexes) => {
    const controls = [];
    for (const index of indexes) {
        const selector = draftSelectButton(form, index);
        const result = {index: String(index)};
        try {
            await expect(selector).toHaveAccessibleName(/^Select image \d+$/, {timeout: 1000});
            result.toHaveAccessibleName = {passed: true};
        } catch (error) {
            result.toHaveAccessibleName = {passed: false, error: String(error && error.message || error)};
        }
        try {
            result.ariaSnapshot = await selector.ariaSnapshot();
        } catch (error) {
            result.ariaSnapshotError = String(error && error.message || error);
        }
        controls.push(result);
    }
    return controls;
};

const capturePageAndFrameState = page => ({
    frames: page.frames().map(frame => ({
        isMainFrame: frame === page.mainFrame(),
        name: frame.name(),
        url: frame.url(),
    })),
    mainFrameUrl: page.mainFrame().url(),
    pageUrl: page.url(),
});

const uploadDraft = async(page, form, imagePath, expectedCount, label) => {
    const addFile = form.locator(
        '#fitem_id_bannerimage_filemanager .fp-btn-add a, ' +
        '#fitem_id_bannerimage_filemanager input.fp-btn-choose'
    ).first();
    await expect(addFile, label + ': Moodle file-manager add action must be available').toBeVisible({timeout: 60000});
    await addFile.click();
    const visiblePicker = page.locator('.file-picker:visible').last();
    await expect(visiblePicker, label + ': Moodle file picker must open').toBeVisible({timeout: 30000});
    const pickerId = await visiblePicker.getAttribute('id');
    ensure(pickerId, label + ': Moodle file picker must expose a stable id.');
    const picker = page.locator('#' + pickerId);
    let upload = picker.locator('input[name="repo_upload_file"]:visible').first();
    if (await upload.count() === 0) {
        const uploadRepository = picker.locator('.fp-repo-name', {hasText: /upload/i}).first();
        await expect(uploadRepository, label + ': Upload repository must be available').toBeVisible();
        await uploadRepository.click();
        upload = picker.locator('input[name="repo_upload_file"]:visible').first();
    }
    await expect(upload, label + ': upload input must be visible and ready').toBeVisible({timeout: 30000});
    await upload.setInputFiles(imagePath);
    const expectedName = path.basename(imagePath);
    const selectedFiles = await upload.evaluate(input => Array.from(input.files || []).map(file => file.name));
    ensure(selectedFiles.length === 1 && selectedFiles[0].toLowerCase() === expectedName.toLowerCase(),
        label + ': repo_upload_file must contain the selected file; files=' + JSON.stringify(selectedFiles) +
        '; value=' + JSON.stringify(await upload.inputValue()));
    const name = expectedName.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    await expect(upload, label + ': selected image must remain in the file field').toHaveValue(
        new RegExp(name + '$', 'i'), {timeout: 30000}
    );
    const uploadRequest = page.waitForRequest(request => {
        const url = new URL(request.url());
        return request.method() === 'POST' &&
            url.pathname === '/repository/repository_ajax.php' &&
            url.searchParams.get('action') === 'upload';
    }, {timeout: 45000});
    const uploadResponse = page.waitForResponse(response => {
        const url = new URL(response.url());
        return response.request().method() === 'POST' &&
            url.pathname === '/repository/repository_ajax.php' &&
            url.searchParams.get('action') === 'upload' && response.status() === 200;
    }, {timeout: 45000});
    const submit = picker.locator('.fp-upload-btn').first();
    await expect(submit, label + ': upload confirmation must be available').toBeVisible();
    await submit.click();
    try {
        await uploadRequest;
    } catch (error) {
        throw new Error(label + ': repository upload POST was not emitted; selected file=' +
            JSON.stringify(selectedFiles) + '; ' + error.message);
    }
    try {
        await uploadResponse;
    } catch (error) {
        throw new Error(label + ': repository upload POST was emitted but did not return HTTP 200; ' + error.message);
    }
    await expect(picker, label + ': picker must close after upload').toBeHidden({timeout: 45000});
    await expect.poll(() => form.locator('[data-preview-draft-visual-layer="1"]').count(), {
        message: label + ': uploaded draft visual must render', timeout: 45000,
    }).toBe(expectedCount);
    const currentLayer = form.locator('[data-preview-current-image="1"][data-preview-draft-layer="1"]').first();
    await expect.poll(() => form.locator(
        '[data-preview-current-image="1"][data-preview-draft-layer="1"]'
    ).count(), {
        message: label + ': active draft shell must be available', timeout: 45000,
    }).toBe(1);
    await expect.poll(() => currentLayer.getAttribute('data-preview-current-url'), {
        message: label + ': uploaded draft must expose a draftfile.php URL', timeout: 45000,
    }).toMatch(/\/draftfile\.php\//);
    const currentImage = currentLayer.locator('[data-preview-image-tag="1"]').first();
    await expect.poll(async() => currentImage.evaluate(image => image.complete ? image.naturalWidth : 0), {
        message: label + ': uploaded draft image must finish loading', timeout: 45000,
    }).toBeGreaterThan(0);
};

const applyCrop = async(page, form, label, evidence, expand = false) => {
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
    const cropWidthBeforeGesture = await layer.getAttribute('data-preview-crop-width');
    const started = Date.now();
    await page.mouse.move(box.x + box.width / 2, box.y + box.height / 2);
    await page.mouse.down();
    await page.mouse.move(box.x + (expand ? 70 : -70), box.y + (expand ? 30 : -30), {steps: 8});
    await page.mouse.up();
    evidence.gestureMilliseconds[label] = Date.now() - started;
    await expect.poll(() => layer.getAttribute('data-preview-crop-width'), {
        message: label + ': crop resize must change the draft width', timeout: 15000,
    }).not.toBe(cropWidthBeforeGesture);
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
    const axAuditOnly = process.env.EASYEDU_CCB_IMG07_AX_AUDIT === '1';
    const rf3eQaDiagnostic = process.env.EASYEDU_CCB_IMG07_RF3E_QA_DIAGNOSTIC === '1';
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
        const draftIndexes = await form.locator('[data-preview-draft-visual-layer="1"]').evaluateAll(layers =>
            layers.map(layer => layer.getAttribute('data-draft-index'))
        );
        ensure(draftIndexes.length === 2 && draftIndexes.every(index => /^\d+$/.test(index || '')),
            'Upload B must expose exactly two numeric visual draft indexes.');
        const draftB = draftIndexes.find(index => index !== draftA);
        ensure(draftB, 'Upload B must expose a visual draft index distinct from A.');
        evidence.draftIndexes = draftIndexes;
        await expectSelectedDraftButton(form, [draftA, draftB], draftA, 'after draft B upload');
        const cdpDraftSelectorAssertion = {context, page};
        if (axAuditOnly) {
            evidence.axBeforeB = await captureDraftSelectorAccessibilityAudit(
                page, context, form, [draftA, draftB], 'before-b', env.artifactRoot
            );
        }
        const selectBStarted = Date.now();
        await selectDraft(form, draftB, 'select B through its accessible selector');
        evidence.gestureMilliseconds.selectB = Date.now() - selectBStarted;
        if (rf3eQaDiagnostic) {
            const playwright = await capturePlaywrightDraftSelectorAccessibility(form, [draftA, draftB]);
            const cdp = await captureDraftSelectorAccessibilityAudit(
                page, context, form, [draftA, draftB], 'rf3e-after-b', env.artifactRoot, false
            );
            const comparison = [draftA, draftB].map((index, position) => ({
                cdpName: cdp.controls[position].axNode?.name || '',
                cdpIgnored: cdp.controls[position].axNode?.ignored || false,
                draftIndex: String(index),
                playwrightAccessibleNamePassed: playwright[position].toHaveAccessibleName.passed,
                playwrightAriaSnapshot: playwright[position].ariaSnapshot || '',
            }));
            evidence.rf3eAccessibilityDiagnostic = {
                cdp,
                comparison,
                page: capturePageAndFrameState(page),
                playwright,
                versions: {
                    browser: browser.version(),
                    playwright: require('@playwright/test/package.json').version,
                },
            };
            writeJson(path.join(env.artifactRoot, 'img-07-rf3e-accessibility-diagnostic.json'), evidence);
            expect(consoleErrors, 'Browser console errors during RF3-E QA diagnostic').toEqual([]);
            expect(pageErrors, 'Uncaught page errors during RF3-E QA diagnostic').toEqual([]);
            return;
        }
        if (axAuditOnly) {
            evidence.axAfterB = await captureDraftSelectorAccessibilityAudit(
                page, context, form, [draftA, draftB], 'after-b', env.artifactRoot
            );
            evidence.axAuditComparison = {
                activeDraftIndexAfterB: await form.getAttribute('data-active-draft-index'),
                beforeNames: evidence.axBeforeB.controls.map(control => control.axNode?.name || ''),
                afterNames: evidence.axAfterB.controls.map(control => control.axNode?.name || ''),
            };
            writeJson(path.join(env.artifactRoot, 'img-07-ax-audit.json'), evidence);
            expect(consoleErrors, 'Browser console errors during AX audit').toEqual([]);
            expect(pageErrors, 'Uncaught page errors during AX audit').toEqual([]);
            return;
        }
        await expectSelectedDraftButton(form, [draftA, draftB], draftB,
            'after explicit accessible B selection', cdpDraftSelectorAssertion);
        evidence.draftB = await readDraft(form, draftB);
        await modal.screenshot({path: path.join(env.artifactRoot, 'img-07-b-selected.png')});

        const selectAStarted = Date.now();
        await selectDraft(form, draftA, 'switch B to A');
        evidence.gestureMilliseconds.switchBToA = Date.now() - selectAStarted;
        await waitForFrames(page);
        await expectSelectedDraftButton(form, [draftA, draftB], draftA,
            'after B to A selection', cdpDraftSelectorAssertion);
        const selectBFromAStarted = Date.now();
        await selectDraft(form, draftB, 'switch A to B');
        evidence.gestureMilliseconds.switchAToB = Date.now() - selectBFromAStarted;
        await waitForFrames(page);
        await expectSelectedDraftButton(form, [draftA, draftB], draftB,
            'after A to B selection', cdpDraftSelectorAssertion);
        const selectAFromBStarted = Date.now();
        await selectDraft(form, draftA, 'switch B to A again');
        evidence.gestureMilliseconds.switchBToAAgain = Date.now() - selectAFromBStarted;
        await waitForFrames(page);
        await expectSelectedDraftButton(form, [draftA, draftB], draftA,
            'after B to A selection again', cdpDraftSelectorAssertion);
        evidence.afterSwitchBackA = await readDraft(form, draftA);
        expectCrop(evidence.afterSwitchBackA, appliedCrop, 'Switch B to A must restore all Crop A values');
        await modal.screenshot({path: path.join(env.artifactRoot, 'img-07-a-restored.png')});

        await cancelRecrop(page, form, 'recrop A Cancel', evidence);
        await waitForFrames(page);
        evidence.afterRecropCancel = await readDraft(form, draftA);
        expectCrop(evidence.afterRecropCancel, appliedCrop, 'Recrop A Cancel must retain the applied Crop');
        await modal.screenshot({path: path.join(env.artifactRoot, 'img-07-a-recrop-cancel.png')});

        await applyCrop(page, form, 'recrop A Apply', evidence, true);
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
