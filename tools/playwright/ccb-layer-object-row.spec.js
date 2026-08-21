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
        'EASYEDU_CCB_LAYER_ROW_SOURCE_CATEGORY_ID', 'EASYEDU_CCB_LAYER_ROW_PROFILE',
        'EASYEDU_CCB_LAYER_ROW_ARTIFACT_ROOT',
    ];
    const missing = required.filter(name => !process.env[name]);
    ensure(!missing.length, 'Missing CCB object-row environment values: ' + missing.join(', '));
    const artifactRoot = path.resolve(process.env.EASYEDU_CCB_LAYER_ROW_ARTIFACT_ROOT);
    const profile = path.resolve(process.env.EASYEDU_CCB_LAYER_ROW_PROFILE);
    ensure(path.isAbsolute(artifactRoot) && path.isAbsolute(profile), 'Object-row paths must be absolute.');
    ensure(profile.toLowerCase().startsWith((artifactRoot + path.sep).toLowerCase()),
        'Chromium profile must stay inside the object-row artifact root.');
    ensure(!artifactRoot.toLowerCase().includes(path.sep + 'local' + path.sep + 'course_banner_builder'),
        'Object-row artifacts must remain outside the CCB repository.');
    ensure(/^\d+$/.test(String(process.env.EASYEDU_CCB_LAYER_ROW_SOURCE_CATEGORY_ID)),
        'Layer-row fixture category must be numeric.');
    return {
        baseUrl: String(process.env.EASYEDU_MOODLE_URL).replace(/\/$/, ''),
        username: process.env.EASYEDU_MOODLE_USERNAME,
        password: process.env.EASYEDU_MOODLE_PASSWORD,
        categoryId: String(process.env.EASYEDU_CCB_LAYER_ROW_SOURCE_CATEGORY_ID),
        profile,
        artifactRoot,
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
    await page.goto(env.baseUrl + '/login/index.php', {waitUntil: 'domcontentloaded'});
    await page.locator('#username').fill(env.username);
    await page.locator('#password').fill(env.password);
    await page.locator('#loginbtn').click({noWaitAfter: true});
    await expect(page).not.toHaveURL(/\/login\//, {timeout: 30000});
};

const rowEvidence = async page => page.evaluate(() => {
    const root = document.querySelector('[data-source-visual-editor="1"]');
    const tbody = document.querySelector('.local-course-banner-builder-layer-sortable[data-layer-sortable="1"]');
    const rect = element => {
        const box = element.getBoundingClientRect();
        return {left: box.left, top: box.top, right: box.right, bottom: box.bottom, width: box.width, height: box.height};
    };
    const rows = tbody ? Array.from(tbody.querySelectorAll(':scope > .local-course-banner-builder-layer-row')).map(row => {
        const cells = Array.from(row.querySelectorAll(':scope > th, :scope > td'));
        const firstCell = cells[0] || row;
        const name = row.querySelector('input[name^="layername_inline["]');
        const id = row.querySelector('.local-course-banner-builder-layer-select')?.value || '';
        const grip = row.querySelector('.local-course-banner-builder-drag-corner');
        const typeIcon = row.querySelector('.local-course-banner-builder-layer-type-icon .fa');
        const imageTypeIcon = row.querySelector('.local-course-banner-builder-layer-type-icon--image');
        const lockHelp = row.querySelector('.local-course-banner-builder-lock-help');
        const aboveOverlayIcon = row.querySelector('.local-course-banner-builder-thumb-above-overlay-badge .fa');
        const lockHelpIcon = lockHelp?.querySelector('.fa');
        const selectionCell = cells[0] || null;
        const sortOrderCell = cells[1] || null;
        const fitOverrideCell = cells[4] || null;
        const bannerLayerCell = cells[5] || null;
        const statusIndicatorSelector = [
            '.local-course-banner-builder-lock-help',
            '.local-course-banner-builder-layer-sort-indicator',
            '.local-course-banner-builder-thumb-status-badge',
        ].join(', ');
        const statusIndicators = Array.from(row.querySelectorAll(statusIndicatorSelector));
        const layerDetailsDisclosure = row.querySelector('.local-course-banner-builder-layer-details-disclosure-shell');
        const layerGroupIndicatorCount = statusIndicators.filter(indicator => indicator.querySelector('.fa')?.classList
            .contains('fa-layer-group')).length;
        const statusIndicatorKinds = statusIndicators.map(indicator => Array.from(indicator.classList)
            .filter(className => /(?:--dynamic|--lock|-crop-badge|-above-border-badge|-above-overlay-badge|-below-inherited-badge|-above-inherited-badge|-center-fixed-badge)$/.test(className))
            .sort()
            .join(' '));
        const overlayVisual = row.querySelector('.local-course-banner-builder-admin-layer-visual--overlay');
        const selectionCheckbox = row.querySelector('.local-course-banner-builder-layer-select')?.closest(
            '.local-course-banner-builder-selection-checkbox'
        );
        const enabledCheckbox = row.querySelector('input[name^="isenabled_inline["]')?.closest(
            '.local-course-banner-builder-selection-checkbox'
        );
        const style = getComputedStyle(firstCell);
        const rowStyle = getComputedStyle(row);
        return {
            id,
            name: name?.value || '',
            draggable: row.getAttribute('draggable') === 'true',
            locked: row.classList.contains('local-course-banner-builder-layer-row--order-locked'),
            dragging: row.classList.contains('local-course-banner-builder-layer-row-dragging'),
            cursor: rowStyle.cursor,
            opacity: rowStyle.opacity,
            cellBackground: style.backgroundColor,
            cellBackgroundImage: style.backgroundImage,
            cellBoxShadow: style.boxShadow,
            cellBoxShadows: cells.map(cell => getComputedStyle(cell).boxShadow),
            cellBorderBottom: style.borderBottomColor,
            firstCellBeforeContent: getComputedStyle(firstCell, '::before').content,
            rowBackgroundImage: rowStyle.backgroundImage,
            rowBackgroundPosition: rowStyle.backgroundPosition,
            grip: grip ? {className: grip.className, opacity: getComputedStyle(grip).opacity, rect: rect(grip)} : null,
            typeIcon: typeIcon ? typeIcon.className : null,
            imageTypeIcon: imageTypeIcon ? {
                opacity: getComputedStyle(imageTypeIcon).opacity,
                rect: rect(imageTypeIcon),
            } : null,
            typeIconInSelectionCell: !!typeIcon && typeIcon.closest('td') === selectionCell,
            typeIconInIdentityRail: !!typeIcon && typeIcon.closest('.local-course-banner-builder-layer-row-identity') !== null,
            typeIconInSortOrderCell: !!typeIcon && typeIcon.closest('td') === sortOrderCell,
            lockHelpInSortOrderCell: !!lockHelp && lockHelp.closest('td') === sortOrderCell,
            lockHelpInIndicatorStack: !!lockHelp && lockHelp.closest('.local-course-banner-builder-layer-sort-indicators') !== null,
            lockHelpBesideSortPosition: !!lockHelp && lockHelp.closest('.local-course-banner-builder-layer-order-state') !== null,
            lockHelpIconClass: lockHelpIcon?.className || '',
            lockHelpGlyphFontSize: lockHelpIcon ? getComputedStyle(lockHelpIcon).fontSize : null,
            aboveOverlayGlyphFontSize: aboveOverlayIcon ? getComputedStyle(aboveOverlayIcon).fontSize : null,
            sortPositionText: sortOrderCell?.querySelector('[data-sort-display]')?.textContent.trim() || '',
            statusIndicatorCount: statusIndicators.length,
            statusIndicatorCursors: statusIndicators.map(indicator => getComputedStyle(indicator).cursor),
            layerGroupIndicatorCount,
            statusIndicatorsInSortOrderCell: statusIndicators.every(indicator => indicator.closest('td') === sortOrderCell),
            duplicateStatusIndicators: statusIndicatorKinds.some((kind, index) => kind && statusIndicatorKinds.indexOf(kind) !== index),
            aboveOverlayStatusBadgeCount: row.querySelectorAll('.local-course-banner-builder-thumb-above-overlay-badge').length,
            selectionCellStatusIndicatorCount: selectionCell ? selectionCell.querySelectorAll(statusIndicatorSelector).length : 0,
            bannerLayerCellStatusIndicatorCount: bannerLayerCell ? bannerLayerCell.querySelectorAll(statusIndicatorSelector).length : 0,
            keyboardPopoverIndicatorCount: sortOrderCell ? sortOrderCell.querySelectorAll(
                '[role="button"][tabindex="0"][data-toggle="popover"]'
            ).length : 0,
            hasLayerDetailsDisclosure: !!layerDetailsDisclosure,
            hasLayerDetailsHelp: !!layerDetailsDisclosure?.querySelector('.local-course-banner-builder-layer-details-help'),
            overlayVisual: overlayVisual ? {
                backgroundImage: getComputedStyle(overlayVisual).backgroundImage,
                backgroundColor: getComputedStyle(overlayVisual).backgroundColor,
            } : null,
            selectionCheckbox: selectionCheckbox ? getComputedStyle(selectionCheckbox).display : null,
            enabledCheckbox: enabledCheckbox ? getComputedStyle(enabledCheckbox).display : null,
            fitOverrideCellBackground: fitOverrideCell ? getComputedStyle(fitOverrideCell).backgroundColor : null,
            rect: rect(row),
        };
    }) : [];
    const actionButtons = tbody ? Array.from(tbody.querySelectorAll(
        '.local-course-banner-builder-layer-actions-cell .local-course-banner-builder-action-list .btn'
    )).map(button => {
        const icon = button.querySelector('.fa, .icon');
        const label = button.querySelector('.local-course-banner-builder-action-label');
        const style = getComputedStyle(button);
        const labelStyle = label ? getComputedStyle(label) : null;
        const buttonRect = rect(button);
        const iconRect = icon ? rect(icon) : null;
        const labelRect = label ? rect(label) : null;
        return {
            text: (button.textContent || '').trim(),
            display: style.display,
            gridTemplateColumns: style.gridTemplateColumns,
            buttonRect,
            iconRect,
            labelRect,
            labelClientWidth: label?.clientWidth ?? null,
            labelScrollWidth: label?.scrollWidth ?? null,
            labelTextAlign: labelStyle?.textAlign || '',
        };
    }) : [];
    return {
        documentClientWidth: document.documentElement.clientWidth,
        documentScrollWidth: document.documentElement.scrollWidth,
        rootClientWidth: root?.clientWidth ?? null,
        rootScrollWidth: root?.scrollWidth ?? null,
        rows,
        actionButtons,
    };
});

const assertNoOverflow = evidence => {
    expect(evidence.documentScrollWidth).toBeLessThanOrEqual(evidence.documentClientWidth + 1);
    expect(evidence.rootScrollWidth).toBeLessThanOrEqual(evidence.rootClientWidth + 1);
};

const assertActionAlignment = (evidence, scope) => {
    expect(evidence.actionButtons.length).toBeGreaterThan(0);
    evidence.actionButtons.forEach(action => {
        const message = scope + ' / ' + action.text;
        expect(action.iconRect, message + ': action icon missing').not.toBeNull();
        expect(action.labelRect, message + ': action label missing').not.toBeNull();
        expect(action.display, message + ': action must use the shared mirrored grid').toBe('grid');
        expect(action.gridTemplateColumns.split(' ').length, message + ': action must reserve three grid slots').toBe(3);
        expect(action.labelTextAlign, message + ': label must be centered').toBe('center');
        expect(action.iconRect.left, message + ': icon must stay at the leading edge').toBeGreaterThanOrEqual(
            action.buttonRect.left - 1
        );
        expect(action.labelRect.left, message + ': label must remain inside its action').toBeGreaterThanOrEqual(
            action.buttonRect.left - 1
        );
        expect(action.labelRect.right, message + ': label must remain inside its action').toBeLessThanOrEqual(
            action.buttonRect.right + 1
        );
        expect(Math.abs(
            (action.labelRect.left + action.labelRect.right) / 2 -
            (action.buttonRect.left + action.buttonRect.right) / 2
        ), message + ': label must remain centered despite its leading icon').toBeLessThanOrEqual(1);
        expect(action.labelScrollWidth, message + ': label must not clip').toBeLessThanOrEqual(
            action.labelClientWidth + 1
        );
    });
};

const sourceActionEvidence = async page => page.evaluate(() => {
    const rect = element => {
        const box = element.getBoundingClientRect();
        return {left: box.left, top: box.top, right: box.right, bottom: box.bottom, width: box.width, height: box.height};
    };
    const actionButtons = Array.from(document.querySelectorAll(
        '.local-course-banner-builder-source-actions-cell .local-course-banner-builder-action-list .btn'
    )).map(button => {
        const icon = button.querySelector('.fa, .icon');
        const label = button.querySelector('.local-course-banner-builder-action-label');
        const style = getComputedStyle(button);
        const labelStyle = label ? getComputedStyle(label) : null;
        return {
            text: (button.textContent || '').trim(),
            display: style.display,
            gridTemplateColumns: style.gridTemplateColumns,
            buttonRect: rect(button),
            iconRect: icon ? rect(icon) : null,
            labelRect: label ? rect(label) : null,
            labelClientWidth: label?.clientWidth ?? null,
            labelScrollWidth: label?.scrollWidth ?? null,
            labelTextAlign: labelStyle?.textAlign || '',
        };
    });
    return {actionButtons};
});

const helpPopoverEvidence = async trigger => trigger.evaluate(trigger => {
    const popover = trigger?.getAttribute('aria-describedby') ?
        document.getElementById(trigger.getAttribute('aria-describedby')) : null;
    const arrow = popover?.querySelector(':scope > .popover-arrow');
    const body = popover?.querySelector(':scope > .popover-body');
    const read = element => {
        if (!element) {
            return null;
        }
        const style = getComputedStyle(element);
        const box = element.getBoundingClientRect();
        return {
            background: style.backgroundImage || style.backgroundColor,
            borderRadius: style.borderRadius,
            color: style.color,
            cursor: style.cursor,
            height: box.height,
            width: box.width,
            padding: style.padding,
            textAlign: style.textAlign,
            fontWeight: style.fontWeight,
            role: element.getAttribute('role'),
            ariaLabel: trigger?.getAttribute('aria-label') || '',
        };
    };
    return {
        trigger: read(trigger),
        popover: read(popover),
        arrow: read(arrow),
        body: read(body),
        isBodyPortal: popover?.parentElement === document.body,
        isCompactCentered: popover?.classList.contains('local-course-banner-builder-hover-popover--compact-centered') || false,
    };
});

const assertHelpPopover = evidence => {
    expect(evidence.trigger).not.toBeNull();
    expect(evidence.popover).not.toBeNull();
    expect(evidence.trigger.width).toBeGreaterThanOrEqual(20);
    expect(evidence.trigger.height).toBeGreaterThanOrEqual(20);
    expect(evidence.trigger.borderRadius).not.toBe('0px');
    expect(evidence.popover.role).toBe('tooltip');
    expect(evidence.trigger.ariaLabel).not.toBe('');
    expect(evidence.trigger.cursor).toBe('pointer');
    expect(evidence.arrow).not.toBeNull();
    expect(evidence.arrow.width).toBeGreaterThan(0);
    expect(evidence.body).not.toBeNull();
    expect(evidence.body.padding).not.toBe('0px');
    expect(Number(evidence.body.fontWeight)).toBeLessThan(600);
    expect(evidence.body.textAlign).toBe('center');
    expect(evidence.isBodyPortal).toBe(true);
    expect(evidence.isCompactCentered).toBe(true);
};

const layerDetailsEvidence = async(page, selector = '.local-course-banner-builder-layer-details-accordion') => page.evaluate(selector => {
    const details = document.querySelector(selector);
    const summary = details?.querySelector(':scope > summary');
    const shell = details?.closest('.local-course-banner-builder-layer-details-disclosure-shell');
    const help = shell?.querySelector(':scope > .local-course-banner-builder-layer-details-help');
    const content = details?.querySelector('.local-course-banner-builder-layer-details-accordion-content');
    const rect = element => {
        const box = element.getBoundingClientRect();
        return {left: box.left, right: box.right, top: box.top, bottom: box.bottom, width: box.width, height: box.height};
    };
    const metadata = Array.from(content?.querySelectorAll('.local-course-banner-builder-border-summary-list > div') || [])
        .map(row => ({term: rect(row.querySelector('dt')), value: rect(row.querySelector('dd'))}));
    return {
        exists: !!details,
        open: !!details?.open,
        summary: summary ? {text: summary.textContent.trim(), ...rect(summary)} : null,
        help: help ? rect(help) : null,
        content: content ? {text: content.textContent.trim(), ...rect(content)} : null,
        metadata,
    };
}, selector);

const assertLayerDetailsAccordion = evidence => {
    expect(evidence.exists).toBe(true);
    expect(evidence.open).toBe(true);
    expect(evidence.summary).not.toBeNull();
    expect(evidence.summary.text).toContain('Layer infos');
    expect(evidence.summary.width).toBeGreaterThan(0);
    expect(evidence.content).not.toBeNull();
    expect(evidence.content.text).not.toContain('Image sizing mode');
    expect(evidence.content.height).toBeGreaterThan(0);
    expect(evidence.help).not.toBeNull();
    expect(evidence.help.top).toBeGreaterThanOrEqual(evidence.summary.top - 0.1);
    expect(evidence.help.bottom).toBeLessThanOrEqual(evidence.summary.bottom + 0.1);
    evidence.metadata.forEach(pair => {
        expect(pair.term.left).toBeLessThan(pair.value.left);
        expect(pair.value.right).toBeGreaterThan(pair.term.right);
    });
};

const orderIds = evidence => evidence.rows.filter(row => row.draggable).map(row => row.id);

const dispatchDrag = async(source, target = null) => {
    await source.evaluate((element, targetElement) => {
        const transfer = new DataTransfer();
        element.dispatchEvent(new DragEvent('dragstart', {bubbles: true, cancelable: true, dataTransfer: transfer}));
        if (targetElement) {
            const box = targetElement.getBoundingClientRect();
            targetElement.dispatchEvent(new DragEvent('dragover', {
                bubbles: true, cancelable: true, dataTransfer: transfer, clientY: box.bottom - 2,
            }));
            targetElement.dispatchEvent(new DragEvent('drop', {bubbles: true, cancelable: true, dataTransfer: transfer}));
        }
        element.dispatchEvent(new DragEvent('dragend', {bubbles: true, cancelable: true, dataTransfer: transfer}));
    }, await target?.elementHandle());
};

const runCell = async(env, zoom, root) => {
    const cellRoot = path.join(root, String(zoom));
    const profile = path.join(cellRoot, 'profile');
    const zoomProfile = prepareNativeZoomProfile(profile, env.baseUrl, zoom);
    const consoleErrors = [];
    const failedRequests = [];
    const context = await chromium.launchPersistentContext(profile, {
        headless: zoom === 100,
        viewport: {width: 1600, height: 900},
        deviceScaleFactor: 1,
        ignoreHTTPSErrors: true,
        args: zoom === 200 ? [
            '--window-position=-32000,-32000', '--start-minimized', '--disable-gpu',
            '--disable-features=CalculateNativeWinOcclusion', '--disable-backgrounding-occluded-windows',
        ] : ['--disable-gpu'],
    });
    const page = context.pages()[0] || await context.newPage();
    page.setDefaultTimeout(25000);
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
    const evidence = {zoom, zoomProfile, consoleErrors, failedRequests};
    const url = new URL('/local/course_banner_builder/admin_manage.php', env.baseUrl);
    url.searchParams.set('sourcekey', 'category:' + env.categoryId);
    try {
        await login(page, env);
        evidence.zoomEvidence = await readZoomEvidence(page, context);
        if (zoom === 200) {
            const ratio = 1600 / Math.max(evidence.zoomEvidence.inPage.innerWidth, 1);
            const scale = evidence.zoomEvidence.inPage.visualViewportScale || 1;
            expect(ratio >= 1.9 || scale >= 1.9, 'The 200% cell must prove genuine Chromium zoom.').toBe(true);
        }
        await page.goto(env.baseUrl + '/local/course_banner_builder/admin_manage.php', {
            waitUntil: 'domcontentloaded', timeout: zoom === 200 ? 60000 : 25000,
        });
        const sourceAction = page.locator(
            '.local-course-banner-builder-source-actions-cell .local-course-banner-builder-action-list .btn'
        ).first();
        await expect(sourceAction).toBeVisible();
        await sourceAction.scrollIntoViewIfNeeded();
        evidence.sourceActions = await sourceActionEvidence(page);
        assertActionAlignment(evidence.sourceActions, 'Source action list');
        await expect(page.locator('.local-course-banner-builder-configured-sources-table')).not.toContainText('Image sizing mode');
        await captureCdp(page, context, path.join(cellRoot, 'source-action-alignment-' + zoom + '.png'));
        await page.goto(url.toString(), {waitUntil: 'domcontentloaded', timeout: zoom === 200 ? 60000 : 25000});
        const rootEditor = page.locator('[data-source-visual-editor="1"]').first();
        const tbody = page.locator('.local-course-banner-builder-layer-sortable[data-layer-sortable="1"]').first();
        await expect(rootEditor).toBeVisible();
        await expect(tbody).toBeVisible();
        await expect(page.locator('input#id_fitmode[type="hidden"]')).toHaveCount(1);
        await expect(page.locator('select#id_fitmode')).toHaveCount(0);
        await expect(page.locator(
            '#local-course-banner-builder-selected-source-content [name="fieldname"][value="fitmode"]'
        )).toHaveCount(0);
        await page.locator('#local-course-banner-builder-selected-source-content .local-course-banner-builder-summary')
            .scrollIntoViewIfNeeded();
        await captureCdp(page, context, path.join(cellRoot, 'selected-source-summary-' + zoom + '.png'));
        await expect(tbody.locator(':scope > .local-course-banner-builder-layer-row')).toHaveCount(5);
        await expect(tbody.locator(':scope > .local-course-banner-builder-layer-row[draggable="true"]')).toHaveCount(2);
        await expect(tbody.locator(':scope > .local-course-banner-builder-layer-row--order-locked')).toHaveCount(3);

        if (zoom === 100) {
            const editLayer = tbody.locator('[data-edit-layer-url]').first();
            const modalId = await editLayer.getAttribute('data-edit-layer-modal');
            expect(modalId).toBeTruthy();
            const editModal = page.locator('#' + modalId);
            await editLayer.click();
            await expect(editModal).toBeVisible({timeout: 30000});
            const layerForm = editModal.locator('form').first();
            await expect(layerForm).toBeVisible({timeout: 30000});
            await expect(layerForm.locator('select#id_fitmodeoverride')).toHaveCount(0);
            const fitOverride = layerForm.locator('[name="fitmodeoverride"]');
            expect(await fitOverride.count()).toBeLessThanOrEqual(1);
            if (await fitOverride.count()) {
                await expect(fitOverride).toHaveAttribute('type', 'hidden');
            }
            await editModal.locator('[data-dismiss="modal"], [data-bs-dismiss="modal"]').first().click();
            await expect(editModal).toBeHidden();
        }

        evidence.initial = await rowEvidence(page);
        assertNoOverflow(evidence.initial);
        assertActionAlignment(evidence.initial, 'Layer action list');
        const emptyLayer = tbody.locator(':scope > .local-course-banner-builder-layer-row').filter({
            has: page.locator('input[name^="layername_inline["][value="CCB QA row two"]'),
        });
        await expect(emptyLayer).toHaveCount(1);
        await expect(emptyLayer.locator('.local-course-banner-builder-layer-details-disclosure-shell')).toHaveCount(0);
        await expect(emptyLayer.locator('.local-course-banner-builder-layer-details-help')).toHaveCount(0);
        const populatedLayer = tbody.locator(':scope > .local-course-banner-builder-layer-row--dynamic').first();
        await expect(populatedLayer).toHaveCount(1);
        const layerDetails = populatedLayer.locator('.local-course-banner-builder-layer-details-accordion');
        await expect(layerDetails).toBeVisible();
        await expect(layerDetails).not.toHaveAttribute('open', '');
        const layerDetailsShell = populatedLayer.locator('.local-course-banner-builder-layer-details-disclosure-shell');
        const helpTrigger = layerDetailsShell.locator(':scope > .local-course-banner-builder-layer-details-help');
        await expect(helpTrigger).toBeVisible();
        const layerDetailsSummary = layerDetails.locator(':scope > summary');
        await layerDetailsSummary.focus();
        await layerDetailsSummary.press('Enter');
        await expect(layerDetails).toHaveAttribute('open', '');
        evidence.layerDetails = await layerDetailsEvidence(
            page,
            '.local-course-banner-builder-layer-row--dynamic .local-course-banner-builder-layer-details-accordion'
        );
        assertLayerDetailsAccordion(evidence.layerDetails);
        evidence.layerDetailsAfterOpen = await rowEvidence(page);
        const emptyLayerEvidence = evidence.layerDetailsAfterOpen.rows.find(row => row.name === 'CCB QA row two');
        expect(emptyLayerEvidence).toBeTruthy();
        expect(emptyLayerEvidence.hasLayerDetailsDisclosure).toBe(false);
        expect(emptyLayerEvidence.hasLayerDetailsHelp).toBe(false);
        expect(emptyLayerEvidence.fitOverrideCellBackground).toBe(emptyLayerEvidence.cellBackground);
        const populatedLayerEvidence = evidence.layerDetailsAfterOpen.rows.find(row => row.name === 'CCB QA row locked dynamic');
        expect(populatedLayerEvidence).toBeTruthy();
        expect(populatedLayerEvidence.hasLayerDetailsDisclosure).toBe(true);
        expect(populatedLayerEvidence.hasLayerDetailsHelp).toBe(true);
        assertNoOverflow(evidence.layerDetailsAfterOpen);
        await captureCdp(page, context, path.join(cellRoot, 'layer-details-accordion-' + zoom + '.png'));
        await helpTrigger.focus();
        await expect.poll(async() => helpTrigger.getAttribute('aria-describedby')).not.toBeNull();
        const helpPopoverId = await helpTrigger.getAttribute('aria-describedby');
        await expect(page.locator('[id="' + helpPopoverId + '"]')).toBeVisible();
        evidence.helpPopover = await helpPopoverEvidence(helpTrigger);
        assertHelpPopover(evidence.helpPopover);
        await captureCdp(page, context, path.join(cellRoot, 'table-help-popover-' + zoom + '.png'));
        const sortOrderHelp = page.locator('.local-course-banner-builder-layer-heading-with-help .local-course-banner-builder-table-help-icon').first();
        await expect(sortOrderHelp).toBeVisible();
        await expect(sortOrderHelp).toHaveAttribute('data-local-course-banner-builder-popover-variant', 'compact-centered');
        await sortOrderHelp.focus();
        await expect.poll(async() => sortOrderHelp.getAttribute('aria-describedby')).not.toBeNull();
        const sortOrderPopoverId = await sortOrderHelp.getAttribute('aria-describedby');
        await expect(page.locator('[id="' + sortOrderPopoverId + '"]')).toBeVisible();
        evidence.sortOrderHelpPopover = await helpPopoverEvidence(sortOrderHelp);
        assertHelpPopover(evidence.sortOrderHelpPopover);
        await captureCdp(page, context, path.join(cellRoot, 'sort-order-help-popover-' + zoom + '.png'));
        const normalRows = evidence.initial.rows.filter(row => row.draggable);
        const lockedRow = evidence.initial.rows.find(row => row.locked);
        expect(normalRows).toHaveLength(2);
        normalRows.forEach(row => {
            expect(row.cursor).toBe('grab');
            expect(row.cellBoxShadow).not.toBe('none');
            expect(row.cellBoxShadows.slice(1)).toEqual(row.cellBoxShadows.slice(1).map(() => 'none'));
            expect(row.grip?.className).toContain('local-course-banner-builder-drag-corner');
            expect(row.typeIcon).toContain('fa-image');
            expect(row.typeIconInSelectionCell).toBe(true);
            expect(row.typeIconInIdentityRail).toBe(true);
            expect(row.typeIconInSortOrderCell).toBe(false);
            expect(Number(row.imageTypeIcon?.opacity)).toBeGreaterThan(0);
            expect(row.statusIndicatorsInSortOrderCell).toBe(true);
            expect(row.statusIndicatorCursors).toEqual(row.statusIndicatorCursors.map(() => 'pointer'));
            expect(row.layerGroupIndicatorCount).toBeLessThanOrEqual(1);
            expect(row.duplicateStatusIndicators).toBe(false);
            expect(row.aboveOverlayStatusBadgeCount).toBeLessThanOrEqual(1);
            expect(row.selectionCellStatusIndicatorCount).toBe(0);
            expect(row.bannerLayerCellStatusIndicatorCount).toBe(0);
            expect(row.selectionCheckbox).toBe('inline-flex');
            expect(row.enabledCheckbox).toBe('inline-flex');
            expect(row.fitOverrideCellBackground).toBe(row.cellBackground);
        });
        expect(lockedRow).toBeTruthy();
        expect(lockedRow.draggable).toBe(false);
        expect(lockedRow.cursor).toBe('default');
        expect(lockedRow.rowBackgroundImage).toContain('repeating-linear-gradient');
        expect(lockedRow.cellBackgroundImage).toBe('none');
        expect(lockedRow.firstCellBeforeContent).toMatch(/^(none|""|'')$/);
        expect(lockedRow.typeIcon).toContain('fa-');
        expect(lockedRow.typeIconInSelectionCell).toBe(true);
        expect(lockedRow.typeIconInIdentityRail).toBe(true);
        expect(lockedRow.typeIconInSortOrderCell).toBe(false);
        expect(lockedRow.lockHelpInSortOrderCell).toBe(true);
        expect(lockedRow.lockHelpInIndicatorStack).toBe(true);
        expect(lockedRow.lockHelpBesideSortPosition).toBe(false);
        expect(lockedRow.lockHelpIconClass).toContain('fa-lock');
        expect(lockedRow.aboveOverlayGlyphFontSize).toBe(lockedRow.lockHelpGlyphFontSize);
        expect(lockedRow.sortPositionText).toContain('Locked');
        expect(lockedRow.statusIndicatorsInSortOrderCell).toBe(true);
        expect(lockedRow.statusIndicatorCursors).toEqual(lockedRow.statusIndicatorCursors.map(() => 'pointer'));
        expect(lockedRow.layerGroupIndicatorCount).toBeLessThanOrEqual(1);
        expect(lockedRow.duplicateStatusIndicators).toBe(false);
        expect(lockedRow.aboveOverlayStatusBadgeCount).toBeLessThanOrEqual(1);
        expect(lockedRow.selectionCellStatusIndicatorCount).toBe(0);
        expect(lockedRow.bannerLayerCellStatusIndicatorCount).toBe(0);
        expect(lockedRow.keyboardPopoverIndicatorCount).toBeGreaterThan(0);
        expect(evidence.initial.rows.some(row => row.aboveOverlayStatusBadgeCount === 1)).toBe(true);
        evidence.initial.rows.filter(row => row.locked).forEach(row => {
            expect(row.cellBoxShadows.slice(1)).toEqual(row.cellBoxShadows.slice(1).map(() => 'none'));
            expect(row.cellBackgroundImage).toBe('none');
            expect(row.cellBorderBottom).not.toBe('rgba(0, 0, 0, 0)');
        });
        expect(new Set(evidence.initial.rows.filter(row => row.locked).map(row => row.rowBackgroundPosition)).size).toBe(1);
        const overlayRow = evidence.initial.rows.find(row => row.overlayVisual);
        expect(overlayRow).toBeTruthy();
        expect(overlayRow.overlayVisual.backgroundImage).toBe('none');
        expect(overlayRow.overlayVisual.backgroundColor).toBe('rgb(255, 255, 255)');

        const sortOrderPopoverIndicators = tbody.locator(
            '.local-course-banner-builder-layer-preview-cell [role="button"][tabindex="0"][data-toggle="popover"]'
        );
        expect(await sortOrderPopoverIndicators.count()).toBeGreaterThan(0);
        for (let index = 0; index < await sortOrderPopoverIndicators.count(); index++) {
            const indicator = sortOrderPopoverIndicators.nth(index);
            await indicator.focus();
            await expect(indicator).toBeFocused();
            await expect.poll(async() => indicator.getAttribute('aria-describedby')).not.toBeNull();
            const popoverId = await indicator.getAttribute('aria-describedby');
            await expect(page.locator('[id="' + popoverId + '"]')).toBeVisible();
        }

        const first = tbody.locator(':scope > .local-course-banner-builder-layer-row[draggable="true"]').nth(0);
        const second = tbody.locator(':scope > .local-course-banner-builder-layer-row[draggable="true"]').nth(1);
        const idleBackground = normalRows[0].cellBackground;
        await first.hover();
        const firstGrip = first.locator('.local-course-banner-builder-drag-corner');
        const firstImageIcon = first.locator('.local-course-banner-builder-layer-type-icon--image');
        await expect.poll(async() => Number(await firstGrip.evaluate(
            element => getComputedStyle(element).opacity
        ))).toBeGreaterThan(0.95);
        await expect.poll(async() => Number(await firstImageIcon.evaluate(
            element => getComputedStyle(element).opacity
        ))).toBeGreaterThan(0.95);
        evidence.hover = await rowEvidence(page);
        const hoveredRow = evidence.hover.rows.find(row => row.id === normalRows[0].id);
        expect(hoveredRow.cellBackground).not.toBe(idleBackground);
        expect(Number(hoveredRow.grip?.opacity || 0)).toBeGreaterThan(
            Number(normalRows[0].grip?.opacity || 0)
        );
        expect(hoveredRow.grip.rect.width).toBeGreaterThanOrEqual(15.9);
        expect(hoveredRow.grip.rect.height).toBeGreaterThanOrEqual(15.9);
        expect(Number(hoveredRow.imageTypeIcon?.opacity || 0)).toBeGreaterThan(0.95);

        const focusControl = first.locator('input[name^="layername_inline["]').first();
        await focusControl.focus();
        await expect(focusControl).toBeFocused();
        evidence.focus = await rowEvidence(page);
        expect(evidence.focus.rows.find(row => row.id === normalRows[0].id).cellBackground).not.toBe(
            idleBackground
        );

        await first.evaluate(element => {
            const transfer = new DataTransfer();
            element.dispatchEvent(new DragEvent('dragstart', {bubbles: true, cancelable: true, dataTransfer: transfer}));
        });
        await expect(first).toHaveClass(/local-course-banner-builder-layer-row-dragging/);
        evidence.dragSource = await rowEvidence(page);
        const dragging = evidence.dragSource.rows.find(row => row.id === normalRows[0].id);
        expect(dragging.opacity).toBe('1');
        expect(dragging.cellBackground).not.toBe(
            evidence.focus.rows.find(row => row.id === normalRows[0].id).cellBackground
        );
        await first.scrollIntoViewIfNeeded();
        await captureCdp(page, context, path.join(cellRoot, 'drag-source-visible-' + zoom + '.png'));
        await first.evaluate(element => {
            element.dispatchEvent(new DragEvent('dragend', {bubbles: true, cancelable: true, dataTransfer: new DataTransfer()}));
        });
        await expect(first).not.toHaveClass(/local-course-banner-builder-layer-row-dragging/);

        if (zoom === 100) {
            const beforeDrag = orderIds(await rowEvidence(page));
            await dispatchDrag(first, second);
            evidence.afterDrag = await rowEvidence(page);
            expect(orderIds(evidence.afterDrag)).toEqual([beforeDrag[1], beforeDrag[0]]);

            const save = page.locator('.local-course-banner-builder-bulk-save-button[form="local-course-banner-builder-bulk-update"]').first();
            const bulkSaveResponse = page.waitForResponse(response =>
                response.request().method() === 'POST' &&
                response.url().includes('/local/course_banner_builder/admin_manage.php') &&
                response.status() >= 200 && response.status() < 400,
            {timeout: 60000});
            await save.click({noWaitAfter: true});
            await bulkSaveResponse;
            await page.goto(url.toString(), {waitUntil: 'domcontentloaded', timeout: 60000});
            evidence.persistedDrag = await rowEvidence(page);
            expect(orderIds(evidence.persistedDrag)).toEqual(orderIds(evidence.afterDrag));

            const moveId = orderIds(evidence.persistedDrag)[1];
            const thumbnail = rootEditor.locator('[data-source-preview-thumbnail-id="' + moveId + '"]').first();
            await thumbnail.focus();
            await expect(thumbnail).toBeFocused();
            await thumbnail.press('Enter');
            const pushBehind = rootEditor.locator('[data-action="local-course-banner-builder-push-source-preview-layer-behind"]').first();
            await expect(pushBehind).toBeEnabled();
            await pushBehind.focus();
            await expect(pushBehind).toBeFocused();
            await pushBehind.press('Enter');
            evidence.afterKeyboard = await rowEvidence(page);
            expect(orderIds(evidence.afterKeyboard)).toEqual([moveId, orderIds(evidence.persistedDrag)[0]]);

            const previewSave = rootEditor.locator('[form="local-course-banner-builder-source-preview-save-form"]').first();
            const previewSaveResponse = page.waitForResponse(response =>
                response.request().method() === 'POST' &&
                response.url().includes('/local/course_banner_builder/admin_manage.php') &&
                response.status() >= 200 && response.status() < 400,
            {timeout: 60000});
            await previewSave.click({noWaitAfter: true});
            await previewSaveResponse;
            await page.goto(url.toString(), {waitUntil: 'domcontentloaded', timeout: 60000});
            evidence.persistedKeyboard = await rowEvidence(page);
            expect(orderIds(evidence.persistedKeyboard)).toEqual(orderIds(evidence.afterKeyboard));
        }
        evidence.final = await rowEvidence(page);
        assertNoOverflow(evidence.final);
        evidence.consoleErrors = consoleErrors;
        evidence.failedRequests = failedRequests;
        writeJson(path.join(cellRoot, 'layer-object-row-evidence.json'), evidence);
        expect(consoleErrors).toEqual([]);
        expect(failedRequests).toEqual([]);
        return evidence;
    } catch (error) {
        evidence.consoleErrors = consoleErrors;
        evidence.failedRequests = failedRequests;
        evidence.error = String(error && error.stack || error);
        writeJson(path.join(cellRoot, 'layer-object-row-failure.json'), evidence);
        throw error;
    } finally {
        await context.close();
    }
};

test('CCB layer table keeps type identity in the rail and preserves native ordering', async() => {
    test.setTimeout(300000);
    const env = requireEnvironment();
    const root = path.join(env.artifactRoot, 'layer-object-row');
    fs.mkdirSync(root, {recursive: true});
    const summary = {artifactRoot: env.artifactRoot, cells: {}};
    try {
        summary.cells.zoom100 = await runCell(env, 100, root);
        summary.cells.zoom200 = await runCell(env, 200, root);
        writeJson(path.join(root, 'layer-object-row-summary.json'), summary);
    } catch (error) {
        writeJson(path.join(root, 'layer-object-row-summary-failure.json'), {
            error: String(error && error.stack || error), summary,
        });
        throw error;
    }
});
