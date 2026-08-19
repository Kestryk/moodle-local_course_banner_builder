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
        'EASYEDU_CCB_IMG06_SOURCE_CATEGORY_ID',
        'EASYEDU_CCB_IMG06_ARTIFACT_ROOT',
        'EASYEDU_CCB_IMG06_MANIFEST',
        'EASYEDU_CCB_IMG06_IMAGE_FIXTURE',
    ];
    const missing = required.filter(name => !process.env[name]);
    ensure(!missing.length, 'Missing IMG-06 environment values: ' + missing.join(', '));
    const artifactRoot = path.resolve(process.env.EASYEDU_CCB_IMG06_ARTIFACT_ROOT);
    ensure(path.isAbsolute(artifactRoot), 'IMG-06 artifacts must use an absolute external path.');
    ensure(/^\d+$/.test(process.env.EASYEDU_CCB_IMG06_SOURCE_CATEGORY_ID),
        'IMG-06 fixture category must be numeric.');
    ensure(fs.existsSync(process.env.EASYEDU_CCB_IMG06_IMAGE_FIXTURE),
        'IMG-06 image fixture is unavailable.');
    return {
        artifactRoot,
        baseUrl: process.env.EASYEDU_MOODLE_URL.replace(/\/$/, ''),
        categoryId: process.env.EASYEDU_CCB_IMG06_SOURCE_CATEGORY_ID,
        imageFixture: process.env.EASYEDU_CCB_IMG06_IMAGE_FIXTURE,
        manifest: process.env.EASYEDU_CCB_IMG06_MANIFEST,
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

const sampleDraft = form => form.evaluate(currentForm => {
    const box = node => {
        if (!node) {
            return null;
        }
        const rect = node.getBoundingClientRect();
        return {
            bottom: rect.bottom,
            height: rect.height,
            left: rect.left,
            right: rect.right,
            top: rect.top,
            width: rect.width,
        };
    };
    const activeIndex = String(currentForm.dataset.activeDraftIndex || '');
    const current = currentForm.querySelector(
        '[data-preview-current-image="1"][data-preview-draft-layer="1"][data-draft-index="' + activeIndex + '"]'
    );
    const visual = currentForm.querySelector(
        '[data-preview-draft-visual-layer="1"][data-draft-index="' + activeIndex + '"]'
    );
    const handle = current?.querySelector(
        '[data-preview-resize-handle="1"][data-preview-resize-edge="bottom-right"]'
    );
    const settingsInput = currentForm.querySelector('#id_multilayerdraftsettings');
    let settings = {};
    try {
        settings = JSON.parse(settingsInput?.value || '{}');
    } catch (error) {
        settings = {parseError: String(error)};
    }
    const state = settings[activeIndex] || null;
    const field = id => currentForm.querySelector(id)?.value ?? null;
    return {
        activeIndex,
        current: current ? {
            anchor: current.getAttribute('data-preview-anchor'),
            fitmode: current.getAttribute('data-preview-fitmode'),
            height: current.getAttribute('data-preview-custom-height'),
            left: current.getAttribute('data-preview-offset-left'),
            rect: box(current),
            style: current.getAttribute('style') || '',
            top: current.getAttribute('data-preview-offset-top'),
            width: current.getAttribute('data-preview-custom-width'),
        } : null,
        fields: {
            anchor: field('[data-layer-position-anchor="1"]'),
            fitmode: field('#id_fitmodeoverride, [name="fitmodeoverride"]'),
            height: field('#id_customheightpercent'),
            left: field('#id_offsetleftpercent'),
            top: field('#id_offsettoppercent'),
            width: field('#id_customwidthpercent'),
        },
        form: {
            elementId: field('#id_elementid'),
            previewApplyingInteraction: currentForm.dataset.previewApplyingInteraction || '',
            previewInteractionStarting: currentForm.dataset.previewInteractionStarting || '',
            renderingDraftPreview: currentForm.dataset.renderingDraftPreview || '',
        },
        handle: handle ? {
            inlinePointerDown: handle.getAttribute('onpointerdown') || '',
            rect: box(handle),
        } : null,
        state,
        visual: visual ? {
            anchor: visual.getAttribute('data-preview-anchor'),
            fitmode: visual.getAttribute('data-preview-fitmode'),
            height: visual.getAttribute('data-preview-custom-height'),
            left: visual.getAttribute('data-preview-offset-left'),
            rect: box(visual),
            style: visual.getAttribute('style') || '',
            top: visual.getAttribute('data-preview-offset-top'),
            width: visual.getAttribute('data-preview-custom-width'),
        } : null,
    };
});

const rectChanged = (before, after) => !!(
    before && after && (
        Math.abs(before.width - after.width) > 1 ||
        Math.abs(before.height - after.height) > 1 ||
        Math.abs(before.left - after.left) > 1 ||
        Math.abs(before.top - after.top) > 1
    )
);

test('IMG-06 add-image modal Fit and resize handles transform the active draft without lag', async() => {
    test.setTimeout(240000);
    const env = requireEnvironment();
    fs.mkdirSync(env.artifactRoot, {recursive: true});
    const evidenceFile = path.join(env.artifactRoot, 'img-06-evidence.json');
    const traceFile = path.join(env.artifactRoot, 'img-06-trace.zip');
    const consoleErrors = [];
    const pageErrors = [];
    const browser = await chromium.launch({headless: true});
    const context = await browser.newContext({
        ignoreHTTPSErrors: true,
        viewport: {width: 1440, height: 900},
    });
    await context.tracing.start({screenshots: true, snapshots: true, sources: true});
    const page = await context.newPage();
    page.setDefaultTimeout(30000);
    page.on('console', message => {
        if (message.type() === 'error') {
            consoleErrors.push({location: message.location(), text: message.text()});
        }
    });
    page.on('pageerror', error => pageErrors.push(String(error && error.stack || error)));
    const evidence = {consoleErrors, pageErrors};

    try {
        await login(page, env);
        const url = new URL('/local/course_banner_builder/admin_manage.php', env.baseUrl);
        url.searchParams.set('sourcekey', 'category:' + env.categoryId);
        await page.goto(url.toString(), {waitUntil: 'domcontentloaded', timeout: 60000});

        const trigger = page.locator(
            '[data-target="#local-course-banner-builder-add-layer-modal"], ' +
            '[data-bs-target="#local-course-banner-builder-add-layer-modal"]'
        ).first();
        await expect.poll(() => page.evaluate(() =>
            typeof window.localCourseBannerBuilderStartModalResizeInteraction
        ), {
            message: 'CCB modal interactions must be initialised before opening the create modal',
            timeout: 30000,
        }).toBe('function');
        await expect(trigger, 'Add-image trigger must be available').toBeVisible();
        await trigger.click();
        const modal = page.locator('#local-course-banner-builder-add-layer-modal').first();
        await expect(modal, 'Add-image modal must open').toBeVisible({timeout: 30000});
        const form = modal.locator('form.mform').first();
        await expect(form.locator('#id_elementid'), 'Create form must expose elementid').toHaveValue('0');

        const draftItemId = await form.locator('#id_bannerimage_filemanager').inputValue();
        ensure(/^\d+$/.test(draftItemId), 'Draft item id must be numeric before upload.');
        const manifest = JSON.parse(fs.readFileSync(env.manifest, 'utf8').replace(/^\uFEFF/, ''));
        manifest.draftitemid = Number(draftItemId);
        fs.writeFileSync(env.manifest, JSON.stringify(manifest, null, 2));

        const addFile = form.locator(
            '#fitem_id_bannerimage_filemanager .fp-btn-add a, ' +
            '#fitem_id_bannerimage_filemanager input.fp-btn-choose'
        ).first();
        await expect(addFile, 'Moodle file-manager add action must be available').toBeVisible({timeout: 60000});
        await addFile.click();
        const visiblePicker = page.locator('.file-picker:visible').last();
        await expect(visiblePicker, 'Moodle file picker must open').toBeVisible({timeout: 30000});
        const pickerId = await visiblePicker.getAttribute('id');
        ensure(pickerId, 'Moodle file picker must expose a stable id.');
        const picker = page.locator('#' + pickerId);
        let upload = picker.locator('input[name="repo_upload_file"]').first();
        if (await upload.count() === 0) {
            const uploadRepository = picker.locator('.fp-repo-name', {hasText: /upload/i}).first();
            await expect(uploadRepository, 'Upload repository must be available').toBeVisible();
            await uploadRepository.click();
            upload = picker.locator('input[name="repo_upload_file"]').first();
        }
        await expect(upload, 'Upload input must be attached').toBeAttached({timeout: 30000});
        await upload.setInputFiles(env.imageFixture);
        await picker.locator('.fp-upload-btn').first().click();
        await expect(picker, 'File picker must close after upload').toBeHidden({timeout: 45000});

        const currentLayer = form.locator(
            '[data-preview-current-image="1"][data-preview-draft-layer="1"]'
        ).first();
        await expect.poll(() => currentLayer.getAttribute('data-preview-current-url'), {
            message: 'Uploaded image must populate the active draft',
            timeout: 45000,
        }).toMatch(/\/draftfile\.php\//);
        const currentImage = currentLayer.locator('[data-preview-image-tag="1"]').first();
        await expect.poll(() => currentImage.evaluate(image =>
            image.complete ? image.naturalWidth : 0
        ), {
            message: 'Uploaded draft image must finish loading before geometry is measured',
            timeout: 45000,
        }).toBeGreaterThan(0);
        const fitButton = form.locator(
            '[data-action="local-course-banner-builder-fit-layer-preview-image"]'
        ).first();
        const resizeHandle = currentLayer.locator(
            '[data-preview-resize-handle="1"][data-preview-resize-edge="bottom-right"]'
        ).first();
        await expect(fitButton, 'Fit to preview must be enabled').toBeEnabled();
        await expect(resizeHandle, 'Bottom-right resize handle must be visible').toBeVisible();

        await page.evaluate(() => {
            window.__easyEduImg06 = {fitClicks: 0, handlePointerDowns: 0, pointerMoves: 0, longTasks: []};
            const formNode = document.querySelector('#local-course-banner-builder-add-layer-modal form.mform');
            const fit = formNode?.querySelector(
                '[data-action="local-course-banner-builder-fit-layer-preview-image"]'
            );
            fit?.addEventListener('click', () => window.__easyEduImg06.fitClicks++, true);
            formNode?.addEventListener('pointerdown', event => {
                if (event.target.closest('[data-preview-resize-handle="1"]')) {
                    window.__easyEduImg06.handlePointerDowns++;
                }
            }, true);
            document.addEventListener('pointermove', () => window.__easyEduImg06.pointerMoves++, true);
            try {
                const observer = new PerformanceObserver(list => {
                    list.getEntries().forEach(entry => window.__easyEduImg06.longTasks.push({
                        duration: entry.duration,
                        startTime: entry.startTime,
                    }));
                });
                observer.observe({entryTypes: ['longtask']});
                window.__easyEduImg06.longTaskObserver = observer;
            } catch (error) {
                window.__easyEduImg06.longTaskObserverError = String(error);
            }
        });

        await waitForFrames(page);
        evidence.before = await sampleDraft(form);
        await modal.screenshot({path: path.join(env.artifactRoot, 'img-06-before.png')});

        await fitButton.click();
        await waitForFrames(page);
        await page.waitForTimeout(250);
        evidence.afterFit = await sampleDraft(form);
        await modal.screenshot({path: path.join(env.artifactRoot, 'img-06-after-fit.png')});

        const handleBox = await resizeHandle.boundingBox();
        ensure(handleBox, 'Bottom-right resize handle has no layout box.');
        const gestureStarted = Date.now();
        await page.mouse.move(handleBox.x + handleBox.width / 2, handleBox.y + handleBox.height / 2);
        await page.mouse.down();
        await page.mouse.move(handleBox.x + handleBox.width / 2 + 90,
            handleBox.y + handleBox.height / 2 + 45, {steps: 10});
        await page.mouse.up();
        evidence.resizeGestureMilliseconds = Date.now() - gestureStarted;
        await waitForFrames(page);
        await page.waitForTimeout(250);
        evidence.afterResize = await sampleDraft(form);
        evidence.instrumentation = await page.evaluate(() => {
            const result = Object.assign({}, window.__easyEduImg06 || {});
            delete result.longTaskObserver;
            return result;
        });
        evidence.fitWorks = !!(
            evidence.afterFit.current?.fitmode === 'cover' &&
            evidence.afterFit.visual?.fitmode === 'cover' &&
            evidence.afterFit.state?.fitmodeoverride === 'cover' &&
            rectChanged(evidence.before.current?.rect, evidence.afterFit.current?.rect)
        );
        evidence.resizeWorks = !!(
            evidence.afterResize.current?.fitmode === 'custom' &&
            evidence.afterResize.visual?.fitmode === 'custom' &&
            evidence.afterResize.state?.fitmodeoverride === 'custom' &&
            rectChanged(evidence.afterFit.current?.rect, evidence.afterResize.current?.rect)
        );
        evidence.maxLongTaskMilliseconds = Math.max(0,
            ...(evidence.instrumentation.longTasks || []).map(entry => entry.duration || 0));
        evidence.responsiveEnough = evidence.resizeGestureMilliseconds < 2500 &&
            evidence.maxLongTaskMilliseconds < 750;
        await modal.screenshot({path: path.join(env.artifactRoot, 'img-06-after-resize.png')});
        writeJson(evidenceFile, evidence);

        expect(evidence.instrumentation.fitClicks, 'Fit click must reach the modal control').toBe(1);
        expect(evidence.instrumentation.handlePointerDowns, 'Pointerdown must reach the draft handle').toBeGreaterThan(0);
        expect(evidence.fitWorks, 'Fit must visibly transform and persist the active create draft').toBe(true);
        expect(evidence.resizeWorks, 'The handle must visibly resize and persist the active create draft').toBe(true);
        expect(evidence.responsiveEnough, 'The resize gesture must not block the modal').toBe(true);
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
