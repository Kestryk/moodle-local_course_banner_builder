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

const safeUrl = value => {
    try {
        const parsed = new URL(value);
        parsed.search = '';
        parsed.hash = '';
        return parsed.toString();
    } catch (error) {
        return String(value).replace(/([?&](?:sesskey|password|token|auth|sessionid)=[^&\s]+)/gi, '$1[redacted]');
    }
};

const requireEnvironment = () => {
    const required = [
        'EASYEDU_MOODLE_URL', 'EASYEDU_MOODLE_USERNAME', 'EASYEDU_MOODLE_PASSWORD',
        'EASYEDU_CCB_MODAL_RAIL_SOURCE_CATEGORY_ID', 'EASYEDU_CCB_MODAL_RAIL_ARTIFACT_ROOT',
    ];
    const missing = required.filter(name => !process.env[name]);
    ensure(!missing.length, 'Missing CCB modal action-rail environment values: ' + missing.join(', '));
    const artifactRoot = path.resolve(process.env.EASYEDU_CCB_MODAL_RAIL_ARTIFACT_ROOT);
    ensure(path.isAbsolute(artifactRoot), 'Modal action-rail artifacts must use an absolute external path.');
    ensure(!artifactRoot.toLowerCase().includes(path.sep + 'local' + path.sep + 'course_banner_builder'),
        'Modal action-rail artifacts must remain outside the CCB repository.');
    ensure(/^\d+$/.test(String(process.env.EASYEDU_CCB_MODAL_RAIL_SOURCE_CATEGORY_ID)),
        'Modal action-rail fixture category must be numeric.');
    return {
        artifactRoot,
        baseUrl: String(process.env.EASYEDU_MOODLE_URL).replace(/\/$/, ''),
        categoryId: String(process.env.EASYEDU_CCB_MODAL_RAIL_SOURCE_CATEGORY_ID),
        password: process.env.EASYEDU_MOODLE_PASSWORD,
        username: process.env.EASYEDU_MOODLE_USERNAME,
    };
};

const prepareNativeZoomProfile = (profile, baseUrl, percentage) => {
    if (percentage === 100) {
        return null;
    }
    const host = new URL(baseUrl).hostname;
    const zoomLevel = Math.log(percentage / 100) / Math.log(1.2);
    const defaultProfile = path.join(profile, 'Default');
    fs.mkdirSync(defaultProfile, {recursive: true});
    fs.writeFileSync(path.join(defaultProfile, 'Preferences'), JSON.stringify({
        partition: {
            default_zoom_level: {x: 0},
            per_host_zoom_levels: {
                x: {[host]: {zoom_level: zoomLevel, last_modified: String((Date.now() + 11644473600000) * 1000)}},
            },
        },
    }));
    return {host, percentage, zoomLevel, method: 'Chromium per-host native zoom preference'};
};

const captureCdp = async(page, context, file) => {
    const cdp = await context.newCDPSession(page);
    try {
        const result = await cdp.send('Page.captureScreenshot', {
            format: 'png', fromSurface: true, captureBeyondViewport: false,
        });
        fs.mkdirSync(path.dirname(file), {recursive: true});
        fs.writeFileSync(file, Buffer.from(result.data, 'base64'));
    } finally {
        await cdp.detach().catch(() => {});
    }
};

const readZoomEvidence = async(page, context) => {
    const cdp = await context.newCDPSession(page);
    try {
        const [layout, inPage] = await Promise.all([
            cdp.send('Page.getLayoutMetrics'),
            page.evaluate(() => ({
                innerWidth: window.innerWidth,
                clientWidth: document.documentElement.clientWidth,
                visualViewportScale: window.visualViewport?.scale ?? null,
            })),
        ]);
        return {inPage, layout};
    } finally {
        await cdp.detach().catch(() => {});
    }
};

const login = async(page, env) => {
    await page.goto(env.baseUrl + '/login/index.php', {waitUntil: 'domcontentloaded', timeout: 60000});
    await page.locator('#username').fill(env.username);
    await page.locator('#password').fill(env.password);
    await page.locator('#loginbtn').click({noWaitAfter: true});
    await expect(page).not.toHaveURL(/\/login\//, {timeout: 60000});
};

const railEvidence = async(page, modalId) => page.evaluate(currentModalId => {
    const rect = element => {
        const box = element.getBoundingClientRect();
        return {left: box.left, top: box.top, right: box.right, bottom: box.bottom, width: box.width, height: box.height};
    };
    const modal = document.getElementById(currentModalId);
    const dialog = modal?.querySelector('.local-course-banner-builder-layer-modal-dialog');
    const content = modal?.querySelector('.local-course-banner-builder-layer-modal-content');
    const body = modal?.querySelector('.local-course-banner-builder-layer-modal-body');
    const form = modal?.querySelector('form.local-course-banner-builder-has-preview-proxy');
    const preview = form?.querySelector('.local-course-banner-builder-banner-preview-panel');
    const rail = form?.querySelector('[data-modal-preview-action-list="1"]');
    const buttons = rail ? Array.from(rail.querySelectorAll('button:not([hidden]), a:not([hidden])')).map(button => {
        const label = button.querySelector('span');
        const labelStyle = label ? getComputedStyle(label) : null;
        return {
            text: (button.textContent || '').trim(),
            rect: rect(button),
            focused: document.activeElement === button,
            label: label ? {
                rect: rect(label),
                clientWidth: label.clientWidth,
                scrollWidth: label.scrollWidth,
                overflow: labelStyle?.overflow || '',
                textOverflow: labelStyle?.textOverflow || '',
                whiteSpace: labelStyle?.whiteSpace || '',
            } : null,
        };
    }).filter(button => button.rect.width > 0 && button.rect.height > 0) : [];
    const modalStyle = modal ? getComputedStyle(modal) : null;
    const railStyle = rail ? getComputedStyle(rail) : null;
    const bodyStyle = body ? getComputedStyle(body) : null;
    return {
        documentClientWidth: document.documentElement.clientWidth,
        documentScrollWidth: document.documentElement.scrollWidth,
        modal: modal ? rect(modal) : null,
        dialog: dialog ? rect(dialog) : null,
        content: content ? rect(content) : null,
        body: body ? rect(body) : null,
        form: form ? rect(form) : null,
        preview: preview ? rect(preview) : null,
        rail: rail ? rect(rail) : null,
        buttons,
        modalDisplay: modalStyle?.display || '',
        bodyOverflowX: bodyStyle?.overflowX || '',
        bodyPaddingRight: bodyStyle?.paddingRight || '',
        bodyClientWidth: body?.clientWidth ?? null,
        bodyScrollWidth: body?.scrollWidth ?? null,
        bodyClientHeight: body?.clientHeight ?? null,
        bodyScrollHeight: body?.scrollHeight ?? null,
        bodyScrollTop: body?.scrollTop ?? null,
        railPosition: railStyle?.position || '',
        railWidth: railStyle?.width || '',
        railGridColumns: railStyle?.gridTemplateColumns || '',
    };
}, modalId);

const accordionEvidence = async(page, modalId, selectors) => page.evaluate(({currentModalId, currentSelectors}) => {
    const rect = element => {
        const box = element.getBoundingClientRect();
        return {left: box.left, top: box.top, right: box.right, bottom: box.bottom, width: box.width, height: box.height};
    };
    const modal = document.getElementById(currentModalId);
    return currentSelectors.map(selector => {
        const details = modal?.querySelector(selector);
        const summary = details?.querySelector(':scope > summary');
        const title = summary?.querySelector('.local-course-banner-builder-border-summary-title');
        const chevron = summary?.querySelector('[data-accordion-chevron="1"]');
        const contentId = summary?.getAttribute('aria-controls') || '';
        const content = contentId ? document.getElementById(contentId) : null;
        const detailsStyle = details ? getComputedStyle(details) : null;
        const summaryStyle = summary ? getComputedStyle(summary) : null;
        return {
            selector,
            exists: !!details,
            open: details?.hasAttribute('open') || false,
            disabled: details?.classList.contains('local-course-banner-builder-disabled') || false,
            ariaExpanded: summary?.getAttribute('aria-expanded') || '',
            ariaControls: contentId,
            contentHidden: content?.hidden ?? null,
            detailsRect: details ? rect(details) : null,
            summaryRect: summary ? rect(summary) : null,
            titleRect: title ? rect(title) : null,
            chevronRect: chevron ? rect(chevron) : null,
            detailsBorderTopWidth: detailsStyle?.borderTopWidth || '',
            summaryMinHeight: summaryStyle?.minHeight || '',
            summaryBackground: summaryStyle?.backgroundColor || '',
        };
    });
}, {currentModalId: modalId, currentSelectors: selectors});

const assertContained = (evidence, cell) => {
    expect(evidence.documentScrollWidth, cell.id + ': document horizontal overflow').toBeLessThanOrEqual(
        evidence.documentClientWidth + 1
    );
    for (const name of ['dialog', 'content', 'body', 'preview', 'rail']) {
        expect(evidence[name], cell.id + ': missing ' + name).toBeTruthy();
    }
    expect(evidence.rail.left, cell.id + ': rail escapes content left edge').toBeGreaterThanOrEqual(evidence.content.left - 1);
    expect(evidence.rail.right, cell.id + ': rail escapes content right edge').toBeLessThanOrEqual(evidence.content.right + 1);
    expect(evidence.bodyOverflowX, cell.id + ': modal body must not expose horizontal scrolling').not.toBe('visible');
    if (!cell.compact) {
        expect(evidence.rail.top, cell.id + ': desktop rail escapes body top edge').toBeGreaterThanOrEqual(evidence.body.top - 1);
        expect(evidence.rail.bottom, cell.id + ': desktop rail escapes body bottom edge').toBeLessThanOrEqual(
            evidence.body.bottom + 1
        );
    }
    for (const button of evidence.buttons) {
        expect(button.rect.left, cell.id + ': action escapes rail left edge').toBeGreaterThanOrEqual(evidence.rail.left - 1);
        expect(button.rect.right, cell.id + ': action escapes rail right edge').toBeLessThanOrEqual(evidence.rail.right + 1);
        if (button.label) {
            expect(button.label.textOverflow, cell.id + ': action label must not be ellipsized').not.toBe('ellipsis');
            expect(button.label.whiteSpace, cell.id + ': action label must be allowed to wrap').not.toBe('nowrap');
            expect(button.label.scrollWidth, cell.id + ': action label must remain readable').toBeLessThanOrEqual(
                button.label.clientWidth + 1
            );
        }
    }
};

const assertDesktopRail = (evidence, cell) => {
    expect(evidence.railPosition, cell.id + ': desktop rail must remain an in-modal absolute rail').toBe('absolute');
    expect(evidence.bodyPaddingRight, cell.id + ': body must reserve rail space').not.toBe('0px');
    expect(evidence.preview.right, cell.id + ': preview overlaps the right rail').toBeLessThanOrEqual(evidence.rail.left + 1);
    expect(evidence.rail.width, cell.id + ': desktop rail must retain a useful action width').not.toBe('0px');
    const firstAction = evidence.buttons[0];
    expect(firstAction, cell.id + ': desktop rail must expose a first action').toBeTruthy();
    expect(firstAction.rect.top - evidence.preview.top,
        cell.id + ': desktop rail must leave 0.75rem above its first action').toBeGreaterThanOrEqual(11.5);
};

const assertCompactRail = (evidence, cell) => {
    expect(evidence.railPosition, cell.id + ': compact rail must return to normal flow').not.toBe('absolute');
    expect(evidence.rail.width, cell.id + ': compact rail must occupy available width').toBeTruthy();
    expect(evidence.rail.left, cell.id + ': compact rail must align with the body flow').toBeGreaterThanOrEqual(evidence.body.left - 1);
    expect(evidence.rail.right, cell.id + ': compact rail must align with the body flow').toBeLessThanOrEqual(evidence.body.right + 1);
};

const assertAccordionContract = (evidence, cell, phase, expectedCount) => {
    expect(evidence, cell.id + ': ' + phase + ' accordion evidence').toHaveLength(expectedCount);
    for (const accordion of evidence) {
        expect(accordion.exists, cell.id + ': missing ' + accordion.selector).toBe(true);
        expect(accordion.disabled, cell.id + ': ' + accordion.selector + ' must be available in its editing modal').toBe(false);
        expect(accordion.ariaControls, cell.id + ': ' + accordion.selector + ' must expose its content relationship').not.toBe('');
        expect(accordion.ariaExpanded, cell.id + ': ' + accordion.selector + ' must expose its disclosure state').toMatch(/^(true|false)$/);
        expect(accordion.detailsRect, cell.id + ': ' + accordion.selector + ' must have a rendered surface').toBeTruthy();
        expect(accordion.detailsBorderTopWidth, cell.id + ': ' + accordion.selector + ' must retain its framed surface').not.toBe('0px');
    }
};

const validateModalAccordions = async(page, modal, cell, modalId, selectors) => {
    const initial = await accordionEvidence(page, modalId, selectors);
    assertAccordionContract(initial, cell, 'initial', selectors.length);
    for (const accordion of initial) {
        expect(accordion.open, cell.id + ': ' + accordion.selector + ' must remain open after keyboard expansion').toBe(true);
        expect(accordion.ariaExpanded, cell.id + ': ' + accordion.selector + ' must announce its expanded state').toBe('true');
        expect(accordion.contentHidden, cell.id + ': ' + accordion.selector + ' content must not remain hidden after expansion').toBe(false);
    }
    return {initial};
};

const overlayControlSelector = [
    '[data-overlay-style-mode-option]',
    '[data-overlay-target-option]',
    '[data-overlay-color-picker="overlaybanner"]',
    '[data-overlay-opacity-control="1"] input[type="range"]',
    '[data-overlay-toggle-button-for="#id_overlaytitleabove"]',
    '[data-overlay-toggle-button-for="#id_overlayborderabove"]',
].join(', ');

const readOverlayControlReachability = async(accordion, selector) => accordion.evaluate((panel, controlSelector) => {
    const rect = element => {
        const box = element.getBoundingClientRect();
        return {left: box.left, top: box.top, right: box.right, bottom: box.bottom, width: box.width, height: box.height};
    };
    const visibleControls = Array.from(panel.querySelectorAll(controlSelector)).filter(control => {
        const style = getComputedStyle(control);
        const box = control.getBoundingClientRect();
        return !control.hidden && style.display !== 'none' && style.visibility !== 'hidden' &&
            box.width > 0 && box.height > 0;
    });
    const modal = panel.closest('.modal');
    const body = modal?.querySelector('.local-course-banner-builder-layer-modal-body');
    const rail = panel.closest('[data-modal-preview-action-list="1"]');
    const footer = modal?.querySelector('.local-course-banner-builder-layer-modal-footer, .modal-footer');
    return {
        count: visibleControls.length,
        first: visibleControls[0] ? rect(visibleControls[0]) : null,
        last: visibleControls.length ? rect(visibleControls[visibleControls.length - 1]) : null,
        body: body ? rect(body) : null,
        rail: rail ? rect(rail) : null,
        footer: footer ? rect(footer) : null,
    };
}, selector);

const validateOverlayControlReachability = async(modal, rail, accordion, cell) => {
    const controls = accordion.locator(overlayControlSelector);
    await expect(controls.first(), cell.id + ': first Overlay control must be visible').toBeVisible();
    await controls.first().focus();
    await expect(controls.first(), cell.id + ': first Overlay control must accept keyboard focus').toBeFocused();

    const scrollTarget = cell.compact
        ? modal.locator('.local-course-banner-builder-layer-modal-body').first()
        : rail;
    await scrollTarget.evaluate(element => {
        element.scrollTop = element.scrollHeight;
    });
    await expect(controls.last(), cell.id + ': final Overlay control must remain visible after normal scrolling').toBeVisible();
    await controls.last().focus();
    await expect(controls.last(), cell.id + ': final Overlay control must accept keyboard focus').toBeFocused();

    const evidence = await readOverlayControlReachability(accordion, overlayControlSelector);
    expect(evidence.count, cell.id + ': Overlay panel must expose multiple real controls').toBeGreaterThanOrEqual(2);
    expect(evidence.first, cell.id + ': first Overlay control geometry must be measurable').toBeTruthy();
    expect(evidence.last, cell.id + ': final Overlay control geometry must be measurable').toBeTruthy();
    if (cell.compact) {
        expect(evidence.footer, cell.id + ': compact modal footer geometry must be measurable').toBeTruthy();
        expect(evidence.last.bottom, cell.id + ': final Overlay control must stop above the fixed footer').toBeLessThanOrEqual(
            evidence.footer.top + 1
        );
    } else {
        expect(evidence.rail, cell.id + ': desktop Overlay rail geometry must be measurable').toBeTruthy();
        expect(evidence.last.bottom, cell.id + ': final Overlay control must stay inside the scrollable rail').toBeLessThanOrEqual(
            evidence.rail.bottom + 1
        );
    }
    return evidence;
};

const cells = [
    {id: 'desktop-100', viewport: {width: 1600, height: 900}, zoom: 100, compact: false},
    {id: 'tablet-100', viewport: {width: 1024, height: 768}, zoom: 100, compact: false},
    {id: 'mobile-100', viewport: {width: 390, height: 844}, zoom: 100, compact: true},
    {id: 'desktop-200', viewport: {width: 1600, height: 900}, zoom: 200, compact: true},
    {id: 'mobile-200', viewport: {width: 390, height: 844}, zoom: 200, compact: true},
];

const modalScenarios = [
    {
        id: 'image',
        modalId: 'local-course-banner-builder-edit-image-layer-modal',
        panelKey: 'imageoptions',
        accordionSelectors: ['.local-course-banner-builder-image-options-accordion'],
    },
    {
        id: 'border',
        modalId: 'local-course-banner-builder-edit-border-layer-modal',
        panelKey: 'borderstyle',
        accordionSelectors: ['.local-course-banner-builder-border-accordion'],
    },
    {
        id: 'overlay',
        modalId: 'local-course-banner-builder-edit-overlay-layer-modal',
        panelKey: 'overlaystyle',
        accordionSelectors: ['.local-course-banner-builder-overlay-accordion'],
    },
];

const validateAppliedDraftCropUndo = async(page, context, cell, cellRoot) => {
    const trigger = page.locator(
        '[data-target="#local-course-banner-builder-add-layer-modal"], ' +
        '[data-bs-target="#local-course-banner-builder-add-layer-modal"]'
    ).first();
    await expect(trigger, cell.id + ': add-image trigger must be available').toBeVisible();
    await trigger.click();
    const modal = page.locator('#local-course-banner-builder-add-layer-modal').first();
    await expect(modal, cell.id + ': add-image modal must open').toBeVisible({timeout: 30000});
    const form = modal.locator('form.mform').first();
    const addFile = form.locator(
        '#fitem_id_bannerimage_filemanager .fp-btn-add a, ' +
        '#fitem_id_bannerimage_filemanager input.fp-btn-choose'
    ).first();
    await expect(addFile, cell.id + ': Moodle file-manager add action must be available').toBeVisible();
    await addFile.click();
    const picker = page.locator('.file-picker:visible').last();
    await expect(picker, cell.id + ': Moodle file picker must open').toBeVisible({timeout: 30000});
    let upload = picker.locator('input[name="repo_upload_file"]').first();
    if (await upload.count() === 0) {
        const uploadRepository = picker.locator('.fp-repo-name', {hasText: /upload/i}).first();
        await expect(uploadRepository, cell.id + ': Upload repository must be offered by Moodle').toBeVisible();
        await uploadRepository.click();
        upload = picker.locator('input[name="repo_upload_file"]').first();
    }
    await expect(upload, cell.id + ': Moodle upload input must be available').toBeAttached({timeout: 30000});
    const imageFixture = path.resolve(
        __dirname,
        '../../../../mod/workshop/tests/fixtures/moodlelogo.png'
    );
    ensure(fs.existsSync(imageFixture), cell.id + ': Moodle PNG fixture must be available');
    await upload.setInputFiles(imageFixture);
    const uploadSubmit = picker.locator('.fp-upload-btn').first();
    await expect(uploadSubmit, cell.id + ': Moodle upload confirmation must be available').toBeVisible();
    await uploadSubmit.click();
    await expect(picker, cell.id + ': Moodle file picker must close after upload').toBeHidden({timeout: 45000});
    const layer = form.locator('[data-preview-current-image="1"]').first();
    await expect.poll(async() => layer.getAttribute('data-preview-current-url'), {
        message: cell.id + ': uploaded image must populate the draft preview', timeout: 45000,
    }).toMatch(/\/draftfile\.php\//);
    const cropToggle = form.locator('[data-action="local-course-banner-builder-toggle-modal-preview-crop"]').first();
    await expect(cropToggle, cell.id + ': crop action must become available').toBeEnabled({timeout: 30000});
    await cropToggle.click();
    const cropBox = layer.locator('[data-preview-crop-box="1"]').first();
    const southeastHandle = layer.locator('[data-preview-crop-handle="se"]').first();
    await expect(cropBox, cell.id + ': crop editor must open').toBeVisible();
    await expect(southeastHandle, cell.id + ': crop resize handle must be available').toBeVisible();
    const handleBox = await southeastHandle.boundingBox();
    ensure(handleBox, cell.id + ': crop resize handle has no layout box');
    await page.mouse.move(handleBox.x + handleBox.width / 2, handleBox.y + handleBox.height / 2);
    await page.mouse.down();
    await page.mouse.move(handleBox.x - 90, handleBox.y - 35, {steps: 8});
    await page.mouse.up();
    await expect.poll(async() => layer.getAttribute('data-preview-crop-width'), {
        message: cell.id + ': crop interaction must change the draft width', timeout: 15000,
    }).not.toBe('100');
    await captureCdp(page, context, path.join(cellRoot, 'crop-draft-active-' + cell.id + '.png'));

    const apply = layer.locator('[data-action="local-course-banner-builder-apply-preview-crop"]').first();
    await expect(apply, cell.id + ': crop editor Apply action must be available').toBeVisible();
    await apply.click();
    await expect(cropBox, cell.id + ': crop editor must close after Apply').toBeHidden();
    await expect.poll(async() => layer.getAttribute('data-preview-crop-enabled'), {
        message: cell.id + ': Apply must persist the changed crop in the active draft before Undo', timeout: 15000,
    }).toBe('1');
    await captureCdp(page, context, path.join(cellRoot, 'crop-draft-applied-' + cell.id + '.png'));

    const undo = form.locator('[data-action="local-course-banner-builder-undo-modal-preview-change"]').first();
    await expect(undo, cell.id + ': modal Undo action must be available after Apply').toBeEnabled();
    await undo.click();
    await expect.poll(async() => layer.getAttribute('data-preview-crop-enabled'), {
        message: cell.id + ': Undo must restore the full image in the active draft preview', timeout: 15000,
    }).toBe('0');
    const restored = await form.evaluate(currentForm => {
        const currentLayer = currentForm.querySelector('[data-preview-current-image="1"]');
        const draftLayer = currentForm.querySelector('[data-preview-current-image="1"][data-preview-draft-layer="1"]');
        const visualLayer = currentForm.querySelector(
            '[data-preview-draft-visual-layer="1"][data-draft-index="' +
            (currentForm.dataset.activeDraftIndex || '') + '"]'
        );
        const settings = JSON.parse(currentForm.querySelector('#id_multilayerdraftsettings')?.value || '{}');
        const index = currentForm.dataset.activeDraftIndex || '';
        const snapshot = {
            activeDraftIndex: index,
            layerDraftIndex: currentLayer?.getAttribute('data-draft-index') || '',
            layerIsDraft: currentLayer?.hasAttribute('data-preview-draft-layer') || false,
            draftLayer: draftLayer ? {
                index: draftLayer.getAttribute('data-draft-index'),
                enabled: draftLayer.getAttribute('data-preview-crop-enabled'),
            } : null,
            visualLayer: visualLayer ? {
                index: visualLayer.getAttribute('data-draft-index'),
                enabled: visualLayer.getAttribute('data-preview-crop-enabled'),
            } : null,
            formFields: ['imagecropenabled', 'imagecropleftpercent', 'imagecroptoppercent',
                'imagecropwidthpercent', 'imagecropheightpercent'].reduce((values, name) => {
                values[name] = currentForm.querySelector('[name="' + name + '"]')?.value || '';
                return values;
            }, {}),
            enabled: currentLayer?.getAttribute('data-preview-crop-enabled') || '',
            left: currentLayer?.getAttribute('data-preview-crop-left') || '',
            top: currentLayer?.getAttribute('data-preview-crop-top') || '',
            width: currentLayer?.getAttribute('data-preview-crop-width') || '',
            height: currentLayer?.getAttribute('data-preview-crop-height') || '',
            draft: settings[index] || {},
            layerDraft: settings[currentLayer?.getAttribute('data-draft-index') || ''] || {},
            settings,
            submittedCrop: currentForm.querySelector('[name="previewcropstate"]')?.value || '',
        };
        return snapshot;
    });
    writeJson(path.join(cellRoot, 'crop-draft-applied-undo-evidence.json'), restored);
    expect(restored.enabled, cell.id + ': Undo must disable crop in the active preview').toBe('0');
    expect(restored.left, cell.id + ': Undo must restore the left edge').toBe('0');
    expect(restored.top, cell.id + ': Undo must restore the top edge').toBe('0');
    expect(restored.width, cell.id + ': Undo must restore full width').toBe('100');
    expect(restored.height, cell.id + ': Undo must restore full height').toBe('100');
    expect(restored.formFields.imagecropenabled, cell.id + ': Undo must restore the hidden crop toggle').toBe('0');
    expect(restored.formFields.imagecropleftpercent, cell.id + ': Undo must restore the hidden crop left edge').toBe('0');
    expect(restored.formFields.imagecroptoppercent, cell.id + ': Undo must restore the hidden crop top edge').toBe('0');
    expect(restored.formFields.imagecropwidthpercent, cell.id + ': Undo must restore the hidden crop width').toBe('100');
    expect(restored.formFields.imagecropheightpercent, cell.id + ': Undo must restore the hidden crop height').toBe('100');
    expect(restored.visualLayer?.enabled, cell.id + ': Undo must restore the visual draft layer').toBe('0');
    expect(restored.draft.imagecropenabled, cell.id +
        ': Undo must clear the draft setting before a later save').toBeFalsy();
    expect(restored.draft.imagecropleftpercent, cell.id + ': Undo must restore the draft left edge').toBe(0);
    expect(restored.draft.imagecroptoppercent, cell.id + ': Undo must restore the draft top edge').toBe(0);
    expect(restored.draft.imagecropwidthpercent, cell.id + ': Undo must restore the draft width').toBe(100);
    expect(restored.draft.imagecropheightpercent, cell.id + ': Undo must restore the draft height').toBe(100);
    await captureCdp(page, context, path.join(cellRoot, 'crop-draft-applied-undo-' + cell.id + '.png'));

    const submit = form.locator(
        'input[type="submit"][name="submitbutton"], button[type="submit"]'
    ).first();
    await expect(submit, cell.id + ': Moodle add-image submit control must be available').toBeAttached();
    await submit.evaluate(button => {
        const ownerForm = button.form;
        if (ownerForm?.requestSubmit) {
            ownerForm.requestSubmit(button);
        } else {
            button.click();
        }
    });
    await page.waitForLoadState('domcontentloaded', {timeout: 60000});
    await expect(modal, cell.id + ': add-image modal must close after save').toBeHidden({timeout: 60000});
    const persistedCrops = await page.locator('[data-source-preview-layer="1"][data-preview-crop-enabled="1"]').count();
    expect(persistedCrops, cell.id + ': an applied then undone crop must not be persisted after save').toBe(0);
    await captureCdp(page, context, path.join(cellRoot, 'crop-draft-persisted-' + cell.id + '.png'));
    return {restored, persistedCrops};
};

const runModalScenario = async(page, context, cell, cellRoot, scenario) => {
    const scenarioCell = {...cell, id: cell.id + '-' + scenario.id};
    const openEdit = page.locator('[data-edit-layer-url][data-edit-layer-modal="' + scenario.modalId + '"]').first();
    const inspectLoading = cell.id === 'desktop-100' && scenario.id === 'overlay';
    const editUrl = await openEdit.getAttribute('data-edit-layer-url');
    const interceptedUrl = editUrl ? new URL(editUrl, page.url()).toString() : '';
    let loadingRequestDelayed = false;
    const delayModalResponse = async route => {
        loadingRequestDelayed = true;
        await new Promise(resolve => setTimeout(resolve, 650));
        try {
            await route.continue();
        } catch (error) {
            // The route can already be released when the modal test has
            // completed its delayed-loading assertion and removes its handler.
            if (!String(error?.message || error).includes('Route is already handled')) {
                throw error;
            }
        }
    };
    if (inspectLoading) {
        ensure(interceptedUrl, scenarioCell.id + ': modal loading request URL must be available');
        await page.route(interceptedUrl, delayModalResponse);
    }
    await expect(openEdit, scenarioCell.id + ': modal action must be available').toBeVisible();
    await openEdit.focus();
    await expect(openEdit, scenarioCell.id + ': modal action must be keyboard reachable').toBeFocused();
    await openEdit.press('Enter');
    const modal = page.locator('#' + scenario.modalId).first();
    await expect(modal, scenarioCell.id + ': modal must open from keyboard activation').toBeVisible({timeout: 60000});
    let loading = null;
    if (inspectLoading) {
        const loadingStatus = modal.locator('.local-course-banner-builder-layer-modal-loading').first();
        await expect(loadingStatus, scenarioCell.id + ': real modal loading status must be visible').toBeVisible({timeout: 15000});
        loading = await modal.evaluate(currentModal => {
            const rect = element => {
                const box = element.getBoundingClientRect();
                return {left: box.left, top: box.top, width: box.width, height: box.height};
            };
            const body = currentModal.querySelector('.modal-body');
            const status = currentModal.querySelector('.local-course-banner-builder-layer-modal-loading');
            const spinner = status?.querySelector('.spinner-border');
            if (!body || !status || !spinner) {
                return null;
            }
            const bodyRect = rect(body);
            const spinnerRect = rect(spinner);
            return {
                display: getComputedStyle(status).display,
                horizontalDelta: Math.abs((spinnerRect.left + spinnerRect.width / 2) - (bodyRect.left + bodyRect.width / 2)),
                verticalDelta: Math.abs((spinnerRect.top + spinnerRect.height / 2) - (bodyRect.top + bodyRect.height / 2)),
            };
        });
        expect(loading, scenarioCell.id + ': modal loading geometry must be measurable').not.toBeNull();
        expect(loading.display, scenarioCell.id + ': modal loading must use a centering layout').toBe('flex');
        expect(loading.horizontalDelta, scenarioCell.id + ': loading spinner must be horizontally centered').toBeLessThanOrEqual(2);
        expect(loading.verticalDelta, scenarioCell.id + ': loading spinner must be vertically centered').toBeLessThanOrEqual(2);
        await captureCdp(page, context, path.join(cellRoot, 'modal-loading-centered-' + cell.id + '.png'));
        await page.unroute(interceptedUrl, delayModalResponse);
        expect(loadingRequestDelayed, scenarioCell.id + ': the real modal response must have been delayed').toBeTruthy();
    }
    const rail = modal.locator('[data-modal-preview-action-list="1"]').first();
    await expect(rail, scenarioCell.id + ': action rail must be visible').toBeVisible({timeout: 60000});
    if (scenario.id === 'image') {
        const previewLayer = modal.locator(
            '[data-preview-current-image="1"], [data-source-preview-layer="1"][data-source-preview-editable="1"]'
        ).first();
        if (await previewLayer.count()) {
            await previewLayer.click({position: {x: 4, y: 4}}).catch(() => {});
        }
    }
    const visibleAction = rail.locator(
        'button.local-course-banner-builder-source-preview-button:not([hidden]):not([disabled]):not([aria-disabled="true"])'
    ).first();
    await expect(visibleAction, scenarioCell.id + ': at least one action must stay usable').toBeVisible({timeout: 60000});
    await visibleAction.focus();
    await expect(visibleAction, scenarioCell.id + ': action rail must preserve keyboard focus').toBeFocused();

    const layout = await railEvidence(page, scenario.modalId);
    assertContained(layout, scenarioCell);
    await captureCdp(page, context, path.join(cellRoot, 'modal-action-rail-' + scenario.id + '-initial-' + cell.id + '.png'));

    const panelTrigger = rail.locator('[data-modal-preview-side-panel-target="' + scenario.panelKey + '"]').first();
    await expect(panelTrigger, scenarioCell.id + ': accordion side-panel action must exist').toBeVisible();
    await panelTrigger.focus();
    await expect(panelTrigger, scenarioCell.id + ': side-panel action must be keyboard focusable').toBeFocused();
    const triggerDecoration = await panelTrigger.evaluate(button => {
        const chevron = getComputedStyle(button, '::after');
        return {
            order: chevron.order,
            marginLeft: chevron.marginLeft,
            marginRight: chevron.marginRight,
            display: chevron.display,
        };
    });
    expect(triggerDecoration.display, scenarioCell.id + ': side-panel state chevron must remain visible').not.toBe('none');
    expect(triggerDecoration.order, scenarioCell.id + ': side-panel state chevron must lead the type icon').toBe('-1');
    expect(triggerDecoration.marginLeft, scenarioCell.id + ': side-panel state chevron must not stay right-aligned').toBe('0px');
    const initialPanelState = await panelTrigger.getAttribute('aria-expanded');
    expect(initialPanelState, scenarioCell.id + ': side-panel action must expose its initial state').toMatch(/^(true|false)$/);
    if (initialPanelState === 'true') {
        await panelTrigger.press('Enter');
        await expect.poll(() => panelTrigger.getAttribute('aria-expanded'), {
            message: scenarioCell.id + ': keyboard Enter must collapse the side-panel action',
        }).toBe('false');
        await expect(modal.locator(scenario.accordionSelectors[0]).first(), scenarioCell.id + ': collapsed panel must be hidden').toBeHidden();
    }
    await panelTrigger.press('Enter');
    await expect.poll(() => panelTrigger.getAttribute('aria-expanded'), {
        message: scenarioCell.id + ': keyboard Enter must expand the side-panel action',
    }).toBe('true');
    const accordion = modal.locator(scenario.accordionSelectors[0]).first();
    await expect(accordion, scenarioCell.id + ': selected accordion panel must become visible').toBeVisible();
    const panelBoundary = await accordion.evaluate(panel => {
        const trigger = panel.previousElementSibling;
        if (!trigger) {
            return null;
        }
        const panelRect = panel.getBoundingClientRect();
        const triggerRect = trigger.getBoundingClientRect();
        const style = getComputedStyle(panel);
        return {
            borderTopWidth: parseFloat(style.borderTopWidth || '0'),
            borderTopStyle: style.borderTopStyle,
            gap: panelRect.top - triggerRect.bottom,
        };
    });
    expect(panelBoundary, scenarioCell.id + ': expanded panel boundary must be measurable').not.toBeNull();
    expect(panelBoundary.borderTopWidth, scenarioCell.id + ': expanded panel must retain a visible top border').toBeGreaterThanOrEqual(0.5);
    expect(panelBoundary.borderTopStyle, scenarioCell.id + ': expanded panel top border must be solid').toBe('solid');
    expect(panelBoundary.gap, scenarioCell.id + ': expanded panel must not be covered by its trigger').toBeGreaterThanOrEqual(1);
    const accordions = await validateModalAccordions(
        page,
        modal,
        scenarioCell,
        scenario.modalId,
        scenario.accordionSelectors
    );
    const overlayControls = scenario.id === 'overlay'
        ? await validateOverlayControlReachability(modal, rail, accordion, cell)
        : null;
    const panelToggle = {
        initial: initialPanelState,
        final: await panelTrigger.getAttribute('aria-expanded'),
    };
    await captureCdp(page, context, path.join(cellRoot, 'modal-accordion-' + scenario.id + '-expanded-' + cell.id + '.png'));
    if (overlayControls) {
        await captureCdp(page, context, path.join(cellRoot, 'modal-overlay-controls-reachable-' + cell.id + '.png'));
    }

    let scrolledLayout = null;
    if (cell.compact) {
        assertCompactRail(layout, scenarioCell);
        const initialFirstAction = layout.buttons[0];
        expect(initialFirstAction.rect.top, scenarioCell.id + ': first compact action must be initially visible').toBeGreaterThanOrEqual(
            layout.body.top - 1
        );
        expect(initialFirstAction.rect.bottom, scenarioCell.id + ': first compact action must be initially visible').toBeLessThanOrEqual(
            layout.body.bottom + 1
        );
        await modal.locator('.local-course-banner-builder-layer-modal-body').evaluate(element => {
            element.scrollTop = element.scrollHeight;
        });
        scrolledLayout = await railEvidence(page, scenario.modalId);
        assertContained(scrolledLayout, scenarioCell);
        const finalAction = scrolledLayout.buttons[scrolledLayout.buttons.length - 1];
        expect(finalAction.rect.top, scenarioCell.id + ': last compact action must be reachable by modal-body scrolling').toBeGreaterThanOrEqual(
            scrolledLayout.body.top - 1
        );
        expect(finalAction.rect.bottom, scenarioCell.id + ': last compact action must be reachable by modal-body scrolling').toBeLessThanOrEqual(
            scrolledLayout.body.bottom + 1
        );
        await captureCdp(page, context, path.join(cellRoot, 'modal-action-rail-' + scenario.id + '-scrolled-' + cell.id + '.png'));
    } else {
        assertDesktopRail(layout, scenarioCell);
    }
    await page.keyboard.press('Escape');
    await expect(modal, scenarioCell.id + ': Escape must close the modal').toBeHidden({timeout: 30000});
    return {layout, scrolledLayout, accordions, overlayControls, panelToggle, triggerDecoration, panelBoundary, loading};
};

const runCell = async(env, cell, root) => {
    const cellRoot = path.join(root, cell.id);
    const profile = path.join(cellRoot, 'profile');
    const zoomProfile = prepareNativeZoomProfile(profile, env.baseUrl, cell.zoom);
    const consoleErrors = [];
    const failedRequests = [];
    const context = await chromium.launchPersistentContext(profile, {
        headless: cell.zoom === 100,
        viewport: cell.viewport,
        deviceScaleFactor: 1,
        ignoreHTTPSErrors: true,
        args: cell.zoom === 200 ? [
            '--window-position=-32000,-32000', '--start-minimized', '--disable-gpu',
            '--disable-features=CalculateNativeWinOcclusion', '--disable-backgrounding-occluded-windows',
        ] : ['--disable-gpu'],
    });
    const page = await context.newPage();
    page.setDefaultTimeout(30000);
    page.on('console', message => {
        if (message.type() === 'error') {
            consoleErrors.push({text: message.text(), location: message.location()});
        }
    });
    page.on('requestfailed', request => {
        const failure = request.failure()?.errorText || 'unknown';
        if (!/ERR_ABORTED/i.test(failure)) {
            failedRequests.push({method: request.method(), resourceType: request.resourceType(),
                url: safeUrl(request.url()), failure});
        }
    });
    const evidence = {cell, zoomProfile, consoleErrors, failedRequests};
    try {
        await login(page, env);
        evidence.zoomEvidence = await readZoomEvidence(page, context);
        if (cell.zoom === 200) {
            const ratio = cell.viewport.width / Math.max(evidence.zoomEvidence.inPage.innerWidth, 1);
            const scale = evidence.zoomEvidence.inPage.visualViewportScale || 1;
            expect(ratio >= 1.9 || scale >= 1.9, cell.id + ': genuine native 200% zoom evidence').toBe(true);
        }
        const url = new URL('/local/course_banner_builder/admin_manage.php', env.baseUrl);
        url.searchParams.set('sourcekey', 'category:' + env.categoryId);
        await page.goto(url.toString(), {waitUntil: 'domcontentloaded', timeout: 60000});
        evidence.modalOpenMethod = 'keyboard-enter';
        evidence.modals = {};
        for (const scenario of modalScenarios) {
            evidence.modals[scenario.id] = await runModalScenario(page, context, cell, cellRoot, scenario);
        }
        if (cell.id === 'desktop-100') {
            evidence.appliedDraftCropUndo = await validateAppliedDraftCropUndo(page, context, cell, cellRoot);
        }
        evidence.consoleErrors = consoleErrors;
        evidence.failedRequests = failedRequests;
        writeJson(path.join(cellRoot, 'modal-action-rail-evidence.json'), evidence);
        expect(consoleErrors, cell.id + ': console errors').toEqual([]);
        expect(failedRequests, cell.id + ': failed requests').toEqual([]);
        return evidence;
    } catch (error) {
        evidence.consoleErrors = consoleErrors;
        evidence.failedRequests = failedRequests;
        evidence.error = String(error && error.stack || error);
        writeJson(path.join(cellRoot, 'modal-action-rail-failure.json'), evidence);
        throw error;
    } finally {
        await context.close();
    }
};

test('CCB layer modal action rails and disclosures remain contained and keyboard-operable', async() => {
    test.setTimeout(720000);
    const env = requireEnvironment();
    const root = path.join(env.artifactRoot, 'modal-action-rail');
    fs.mkdirSync(root, {recursive: true});
    const summary = {artifactRoot: env.artifactRoot, cells: {}};
    try {
        for (const cell of cells) {
            summary.cells[cell.id] = await runCell(env, cell, root);
        }
        writeJson(path.join(root, 'modal-action-rail-summary.json'), summary);
    } catch (error) {
        writeJson(path.join(root, 'modal-action-rail-summary-failure.json'), {
            error: String(error && error.stack || error), summary,
        });
        throw error;
    }
});
