const {test, expect, chromium} = require('@playwright/test');
const fs = require('fs');
const path = require('path');

const ensure = (condition, message) => {
    if (!condition) {
        throw new Error(message);
    }
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

const writeJson = (file, value) => {
    fs.mkdirSync(path.dirname(file), {recursive: true});
    fs.writeFileSync(file, JSON.stringify(value, null, 2));
};

const requireEnvironment = () => {
    const required = [
        'EASYEDU_MOODLE_URL', 'EASYEDU_MOODLE_USERNAME', 'EASYEDU_MOODLE_PASSWORD',
        'EASYEDU_CCB_RESPONSIVE_FIXTURE_COURSE_ID', 'EASYEDU_CCB_RESPONSIVE_SOURCE_CATEGORY_ID',
        'EASYEDU_CCB_RESPONSIVE_PROFILE', 'EASYEDU_CCB_RESPONSIVE_ARTIFACT_ROOT',
        'EASYEDU_CCB_RESPONSIVE_VIEWPORT', 'EASYEDU_CCB_RESPONSIVE_ZOOM',
        'EASYEDU_CCB_RESPONSIVE_FORMAT',
    ];
    const missing = required.filter(name => !process.env[name]);
    ensure(!missing.length, `Missing CCB responsive environment values: ${missing.join(', ')}`);

    const artifactRoot = path.resolve(process.env.EASYEDU_CCB_RESPONSIVE_ARTIFACT_ROOT);
    const profile = path.resolve(process.env.EASYEDU_CCB_RESPONSIVE_PROFILE);
    const separator = path.sep;
    ensure(path.isAbsolute(artifactRoot) && path.isAbsolute(profile), 'Responsive paths must be absolute.');
    ensure(profile.toLowerCase().startsWith((artifactRoot + separator).toLowerCase()),
        'The Chromium profile must remain inside the external artifact root.');
    ensure(!artifactRoot.toLowerCase().includes(`${separator}local${separator}course_banner_builder`),
        'Responsive artifacts must remain outside the CCB repository.');
    const viewportMatch = String(process.env.EASYEDU_CCB_RESPONSIVE_VIEWPORT).match(/^(\d+)x(\d+)$/);
    ensure(viewportMatch, 'Responsive viewport must use WIDTHxHEIGHT notation.');
    const zoom = Number(process.env.EASYEDU_CCB_RESPONSIVE_ZOOM);
    ensure([100, 200].includes(zoom), 'Responsive zoom must be 100 or 200.');
    ensure(/^11$/.test(String(process.env.EASYEDU_CCB_RESPONSIVE_FIXTURE_COURSE_ID)),
        'The responsive fixture must use course 11.');
    ensure(/^\d+$/.test(String(process.env.EASYEDU_CCB_RESPONSIVE_SOURCE_CATEGORY_ID)),
        'The responsive source category must be numeric.');
    return {
        baseUrl: String(process.env.EASYEDU_MOODLE_URL).replace(/\/$/, ''),
        username: process.env.EASYEDU_MOODLE_USERNAME,
        password: process.env.EASYEDU_MOODLE_PASSWORD,
        courseId: String(process.env.EASYEDU_CCB_RESPONSIVE_FIXTURE_COURSE_ID),
        sourceCategoryId: String(process.env.EASYEDU_CCB_RESPONSIVE_SOURCE_CATEGORY_ID),
        profile,
        artifactRoot,
        viewport: {name: String(process.env.EASYEDU_CCB_RESPONSIVE_VIEWPORT), width: Number(viewportMatch[1]), height: Number(viewportMatch[2])},
        zoom,
        format: String(process.env.EASYEDU_CCB_RESPONSIVE_FORMAT),
        port: Number(process.env.EASYEDU_CCB_RESPONSIVE_PORT || (9580 + (process.pid % 100))),
    };
};

const prepareNativeZoomProfile = (profile, baseUrl, percentage) => {
    if (percentage === 100) {
        return null;
    }
    const host = new URL(baseUrl).hostname;
    const zoomLevel = Math.log(percentage / 100) / Math.log(1.2);
    const lastModified = String((Date.now() + 11644473600000) * 1000);
    const preferences = {
        partition: {
            default_zoom_level: {x: 0},
            per_host_zoom_levels: {
                x: {[host]: {zoom_level: zoomLevel, last_modified: lastModified}},
            },
        },
    };
    const defaultProfile = path.join(profile, 'Default');
    fs.mkdirSync(defaultProfile, {recursive: true});
    fs.writeFileSync(path.join(defaultProfile, 'Preferences'), JSON.stringify(preferences));
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

const captureSourcePreview = async(page, context, root, file) => {
    const frame = root.locator('[data-source-preview-frame="1"]').first();
    await frame.evaluate(element => element.scrollIntoView({block: 'center', inline: 'nearest'}));
    await expect(frame).toBeVisible();
    await page.waitForTimeout(150);
    const scrollState = await page.evaluate(() => ({
        scrollX: window.scrollX,
        scrollY: window.scrollY,
        viewportWidth: document.documentElement.clientWidth,
        viewportHeight: document.documentElement.clientHeight,
    }));
    await captureCdp(page, context, file);
    return scrollState;
};

const readSelectedSourceSticky = async page => page.evaluate(() => {
    const sticky = document.querySelector('.local-course-banner-builder-selected-source-sticky');
    const holder = document.querySelector('.local-course-banner-builder-selected-source-sticky-holder');
    const leading = sticky ? sticky.querySelector('.local-course-banner-builder-sticky-leading') : null;
    const deselect = sticky ? sticky.querySelector('.local-course-banner-builder-sticky-deselect') : null;
    const visible = element => {
        if (!element || element.hidden) {
            return false;
        }
        const style = getComputedStyle(element);
        const box = element.getBoundingClientRect();
        return style.display !== 'none' && style.visibility !== 'hidden' && box.width > 0 && box.height > 0;
    };
    const rect = element => {
        const box = element.getBoundingClientRect();
        return {left: box.left, top: box.top, right: box.right, bottom: box.bottom, width: box.width, height: box.height};
    };
    const within = (outer, inner) => inner.left >= outer.left - 1 && inner.right <= outer.right + 1 &&
        inner.top >= outer.top - 1 && inner.bottom <= outer.bottom + 1;
    const viewport = {left: 0, top: 0, right: window.innerWidth, bottom: window.innerHeight};
    if (!visible(sticky)) {
        return null;
    }
    const stickyRect = rect(sticky);
    if (stickyRect.right <= viewport.left || stickyRect.left >= viewport.right ||
            stickyRect.bottom <= viewport.top || stickyRect.top >= viewport.bottom) {
        return null;
    }
    const leadingRect = visible(leading) ? rect(leading) : null;
    const deselectRect = visible(deselect) ? rect(deselect) : null;
    const holderStyle = holder ? getComputedStyle(holder) : null;
    const stickyStyle = getComputedStyle(sticky);
    const title = sticky.querySelector('.local-course-banner-builder-sticky-title');
    const kicker = sticky.querySelector('.local-course-banner-builder-sticky-kicker');
    const drawerToggle = document.querySelector('.drawer-toggles .drawer-right-toggle');
    const navbar = document.querySelector('.navbar.fixed-top') ||
        document.querySelector('header.fixed-top') ||
        document.querySelector('#page-header.fixed-top') ||
        document.querySelector('#page-navbar');
    const navbarBottom = navbar ? Math.max(0, navbar.getBoundingClientRect().bottom) : 0;
    const documentWidth = document.documentElement.clientWidth;
    const overlaps = (first, second) => first && second && first.left < second.right - 1 &&
        first.right > second.left + 1 && first.top < second.bottom - 1 && first.bottom > second.top + 1;
    const runtimeStickySync = window.localCourseBannerBuilderSyncStickyHeader;
    const drawerToggleRect = visible(drawerToggle) ? rect(drawerToggle) : null;
    return {
        rect: stickyRect,
        holderRect: visible(holder) ? rect(holder) : null,
        holderClassName: holder ? holder.className : null,
        holderInBody: Boolean(holder && holder.parentElement === document.body),
        holderPortal: Boolean(holder && holder.dataset.stickyPortal === '1'),
        holderState: holder ? holder.dataset.stickyState || null : null,
        runtimePortalCode: Boolean(runtimeStickySync && runtimeStickySync.toString().includes('stickyPortal')),
        holderInlineTop: holder ? holder.style.top || null : null,
        holderComputedTop: holderStyle ? holderStyle.top : null,
        holderPosition: holderStyle ? holderStyle.position : null,
        backgroundColor: stickyStyle.backgroundColor,
        borderTopWidth: stickyStyle.borderTopWidth,
        loadedAdminManageResources: performance.getEntriesByType('resource')
            .map(entry => entry.name)
            .filter(name => /admin_manage(?:\.min)?\.js|requirejs\.php/i.test(name)),
        leadingRect,
        deselectRect,
        deselectMarginInlineEnd: deselect ? getComputedStyle(deselect).marginInlineEnd : null,
        fillsViewportWidth: Math.abs(stickyRect.left) <= 1 && Math.abs(stickyRect.right - documentWidth) <= 1,
        belowMoodleNavigation: Math.abs(stickyRect.top - navbarBottom) <= 1,
        leadingTextAlign: leading ? getComputedStyle(leading).textAlign : null,
        titleTextAlign: title ? getComputedStyle(title).textAlign : null,
        titleRect: visible(title) ? rect(title) : null,
        kickerRect: visible(kicker) ? rect(kicker) : null,
        titleOverlapsKicker: overlaps(visible(title) ? rect(title) : null, visible(kicker) ? rect(kicker) : null),
        leadingOverlapsDeselect: overlaps(leadingRect, deselectRect),
        drawerToggleRect,
        deselectOverlapsDrawerToggle: overlaps(deselectRect, drawerToggleRect),
        desktopTrayLayout: window.matchMedia('(min-width: 48.0625rem)').matches,
        deselectCentered: Boolean(deselectRect && Math.abs(
            (deselectRect.left + (deselectRect.width / 2)) - (stickyRect.left + (stickyRect.width / 2))
        ) <= 1),
        withinViewport: within(viewport, stickyRect),
        leadingWithinSticky: Boolean(leadingRect && within(stickyRect, leadingRect)),
        deselectWithinSticky: Boolean(deselectRect && within(stickyRect, deselectRect)),
        deselectWithinViewport: Boolean(deselectRect && within(viewport, deselectRect)),
    };
});

const cdpZoomEvidence = async(page, context) => {
    const cdp = await context.newCDPSession(page);
    try {
        const [layout, inPage] = await Promise.all([
            cdp.send('Page.getLayoutMetrics'),
            page.evaluate(() => ({
                innerWidth: window.innerWidth,
                clientWidth: document.documentElement.clientWidth,
                devicePixelRatio: window.devicePixelRatio,
                visualViewportWidth: window.visualViewport?.width ?? null,
                visualViewportScale: window.visualViewport?.scale ?? null,
            })),
        ]);
        return {inPage, layout};
    } finally {
        await cdp.detach().catch(() => {});
    }
};

const login = async(page, env) => {
    // The disposable local Moodle runtime can need longer to render an initial
    // document immediately after a cache purge. This only widens the harness
    // navigation allowance; it does not retry authentication or change data.
    await page.goto(`${env.baseUrl}/login/index.php`, {
        waitUntil: 'domcontentloaded',
        timeout: 60000,
    });
    await page.locator('#username').fill(env.username);
    await page.locator('#password').fill(env.password);
    await page.locator('#loginbtn').click({noWaitAfter: true});
    await expect(page).not.toHaveURL(/\/login\//, {timeout: env.zoom === 200 ? 60000 : 30000});
};

const openSourceEditor = async(page, env) => {
    const url = `${env.baseUrl}/local/course_banner_builder/admin_manage.php?sourcekey=category:${encodeURIComponent(env.sourceCategoryId)}`;
    await page.goto(url, {waitUntil: 'domcontentloaded', timeout: 60000});
    const root = page.locator('[data-source-visual-editor="1"]').first();
    await expect(root).toBeVisible({timeout: 30000});
    await expect(root.locator('[data-source-preview-frame="1"]')).toHaveAttribute('data-banner-format', env.format);
    return root;
};

const readPreview = async(page, env) => page.evaluate(({viewport, zoom, format}) => {
    const root = document.querySelector('[data-source-visual-editor="1"]');
    if (!root) {
        throw new Error('CCB source visual editor is missing.');
    }
    const visible = element => {
        if (!element || element.hidden) {
            return false;
        }
        const style = getComputedStyle(element);
        const rect = element.getBoundingClientRect();
        return style.display !== 'none' && style.visibility !== 'hidden' && rect.width > 0 && rect.height > 0;
    };
    const intersectsViewport = element => {
        if (!element) {
            return false;
        }
        const box = element.getBoundingClientRect();
        return box.right > 0 && box.left < window.innerWidth &&
            box.bottom > 0 && box.top < window.innerHeight;
    };
    const rect = element => {
        const box = element.getBoundingClientRect();
        return {left: box.left, top: box.top, right: box.right, bottom: box.bottom, width: box.width, height: box.height};
    };
    const intersects = (first, second) => first.left < second.right - 1 && first.right > second.left + 1 &&
        first.top < second.bottom - 1 && first.bottom > second.top + 1;
    const within = (outer, inner) => inner.left >= outer.left - 1 && inner.right <= outer.right + 1 &&
        inner.top >= outer.top - 1 && inner.bottom <= outer.bottom + 1;
    const canvas = root.querySelector('.local-course-banner-builder-source-preview-canvas');
    const surface = root.querySelector('.local-course-banner-builder-source-preview-surface');
    const modeControl = root.querySelector('[data-source-preview-mode-control="1"]');
    const frame = root.querySelector('[data-source-preview-frame="1"]');
    const filmstrip = root.querySelector('[data-source-preview-filmstrip="1"]');
    const visibilityRow = root.querySelector('[data-source-preview-visibility-toggle-row="1"]');
    const bottomRow = root.querySelector('.local-course-banner-builder-source-preview-bottom-row');
    const primaryActions = root.querySelector('.local-course-banner-builder-source-preview-primary-actions');
    const controls = root.querySelector('.local-course-banner-builder-source-preview-controls');
    const stickyHolder = document.querySelector('.local-course-banner-builder-selected-source-sticky-holder');
    const sticky = document.querySelector('.local-course-banner-builder-selected-source-sticky');
    const stickyLeading = sticky ? sticky.querySelector('.local-course-banner-builder-sticky-leading') : null;
    const stickyDeselect = sticky ? sticky.querySelector('.local-course-banner-builder-sticky-deselect') : null;
    const viewportBounds = {
        left: 0,
        top: 0,
        right: window.innerWidth,
        bottom: window.innerHeight,
    };
    const styleOf = element => {
        const style = getComputedStyle(element);
        return {
            border: [style.borderTopWidth, style.borderRightWidth, style.borderBottomWidth, style.borderLeftWidth],
            background: style.backgroundColor,
            padding: [style.paddingTop, style.paddingRight, style.paddingBottom, style.paddingLeft],
            overflow: style.overflow,
        };
    };
    const actionButtons = primaryActions ? Array.from(primaryActions.querySelectorAll('button')).filter(visible) : [];
    const buttonEvidence = actionButtons.map((button, index) => {
        const buttonRect = rect(button);
        const style = getComputedStyle(button);
        return {
            index,
            label: button.getAttribute('aria-label') || button.textContent.trim(),
            action: button.getAttribute('data-action'),
            tabIndex: button.tabIndex,
            rect: buttonRect,
            withinPrimaryActions: within(rect(primaryActions), buttonRect),
            clipped: button.scrollWidth > button.clientWidth + 1 || button.scrollHeight > button.clientHeight + 1,
            whiteSpace: style.whiteSpace,
            order: style.order,
        };
    });
    const siblingEntries = [
        ['surface', surface], ['visibility', visibilityRow], ['filmstrip', filmstrip], ['bottom', bottomRow],
    ].filter(([, element]) => visible(element));
    const overlaps = [];
    for (let index = 0; index < siblingEntries.length; index++) {
        for (let next = index + 1; next < siblingEntries.length; next++) {
            const first = siblingEntries[index];
            const second = siblingEntries[next];
            if (intersects(rect(first[1]), rect(second[1]))) {
                overlaps.push([first[0], second[0]]);
            }
        }
    }
    const rootRect = rect(root);
    const previewElementsOutside = Array.from(root.querySelectorAll(
        '.local-course-banner-builder-source-preview-canvas, .local-course-banner-builder-source-preview-surface, [data-source-preview-mode-control="1"], [data-source-preview-frame="1"], [data-source-preview-filmstrip="1"], .local-course-banner-builder-source-preview-bottom-row, .local-course-banner-builder-source-preview-controls, .local-course-banner-builder-source-preview-primary-actions, .local-course-banner-builder-source-preview-button'
    )).filter(visible).map(element => ({className: String(element.className), rect: rect(element)}))
        .filter(entry => !within(rootRect, entry.rect));
    const modeButtons = Array.from(root.querySelectorAll('[data-source-preview-mode-value]')).filter(visible).map(button => ({
        value: button.getAttribute('data-source-preview-mode-value'),
        pressed: button.getAttribute('aria-pressed'),
        action: button.getAttribute('data-action'),
        tabIndex: button.tabIndex,
        rect: rect(button),
    }));
    const actions = Array.from(root.querySelectorAll('[data-action]')).map(element => element.getAttribute('data-action'));
    const frameStyle = frame ? getComputedStyle(frame) : null;
    const frameRect = frame ? rect(frame) : null;
    const surfaceRect = surface ? rect(surface) : null;
    // The selected-source holder remains in normal flow until scrolling brings
    // it to its sticky threshold. At native 200% it may be below the initial
    // viewport; only assert sticky geometry once the holder is materialised in
    // the visible viewport instead of treating an offscreen node as clipped.
    const stickyRect = sticky && visible(sticky) && intersectsViewport(sticky) ? rect(sticky) : null;
    const stickyHolderRect = stickyHolder && visible(stickyHolder) ? rect(stickyHolder) : null;
    const stickyLeadingRect = stickyLeading && visible(stickyLeading) ? rect(stickyLeading) : null;
    const stickyDeselectRect = stickyDeselect && visible(stickyDeselect) ? rect(stickyDeselect) : null;
    const viewportWidth = document.documentElement.clientWidth;
    return {
        viewport,
        zoom,
        format,
        mode: root.getAttribute('data-source-preview-mode'),
        root: {rect: rootRect, clientWidth: root.clientWidth, scrollWidth: root.scrollWidth},
        document: {clientWidth: viewportWidth, scrollWidth: document.documentElement.scrollWidth},
        canvas: canvas ? {rect: rect(canvas), style: styleOf(canvas)} : null,
        surface: surface ? {rect: rect(surface), style: styleOf(surface)} : null,
        modeControl: modeControl ? {rect: rect(modeControl), style: styleOf(modeControl)} : null,
        frame: frame && frameRect ? {
            rect: frameRect,
            format: frame.getAttribute('data-banner-format'),
            logicalWidth: Number(frame.getAttribute('data-source-preview-mobile-logical-width')),
            logicalHeight: Number(frame.getAttribute('data-source-preview-mobile-logical-height')),
            aspectRatio: frameRect.width / Math.max(frameRect.height, 1),
            surfaceWidthRatio: surfaceRect ? frameRect.width / Math.max(surfaceRect.width, 1) : null,
            frameWithinSurface: Boolean(surfaceRect && within(surfaceRect, frameRect)),
            cssAspectRatio: frameStyle.aspectRatio,
        } : null,
        filmstrip: filmstrip && visible(filmstrip) ? {rect: rect(filmstrip), style: styleOf(filmstrip)} : null,
        visibility: visibilityRow && visible(visibilityRow) ? rect(visibilityRow) : null,
        bottom: bottomRow && visible(bottomRow) ? rect(bottomRow) : null,
        primaryActions: primaryActions && visible(primaryActions) ? {rect: rect(primaryActions), style: styleOf(primaryActions)} : null,
        controls: controls && visible(controls) ? {rect: rect(controls), style: styleOf(controls)} : null,
        selectedSourceSticky: stickyRect ? {
            rect: stickyRect,
            holderRect: stickyHolderRect,
            leadingRect: stickyLeadingRect,
            deselectRect: stickyDeselectRect,
            withinViewport: within(viewportBounds, stickyRect),
            leadingWithinSticky: Boolean(stickyLeadingRect && within(stickyRect, stickyLeadingRect)),
            deselectWithinSticky: Boolean(stickyDeselectRect && within(stickyRect, stickyDeselectRect)),
            deselectWithinViewport: Boolean(stickyDeselectRect && within(viewportBounds, stickyDeselectRect)),
        } : null,
        actionButtons: buttonEvidence,
        actionRows: [...new Set(buttonEvidence.map(button => Math.round(button.rect.top)))],
        modeButtons,
        actions,
        overlaps,
        previewElementsOutside,
        previewOverflow: root.scrollWidth > root.clientWidth + 1,
    };
}, {viewport: env.viewport, zoom: env.zoom, format: env.format});

const assertMaterialized = evidence => {
    expect(evidence.canvas).not.toBeNull();
    expect(evidence.surface).not.toBeNull();
    expect(evidence.controls).not.toBeNull();
    [evidence.canvas, evidence.surface, evidence.controls, evidence.modeControl].forEach(entry => {
        expect(entry).not.toBeNull();
        expect(entry.style.border.some(value => value !== '0px')).toBe(true);
        expect(entry.style.background).not.toBe('rgba(0, 0, 0, 0)');
    });
    expect(evidence.document.scrollWidth).toBeLessThanOrEqual(evidence.document.clientWidth + 1);
    expect(evidence.previewOverflow).toBe(false);
    expect(evidence.previewElementsOutside).toEqual([]);
    expect(evidence.overlaps).toEqual([]);
    expect(evidence.actionButtons.length).toBeGreaterThan(0);
    evidence.actionButtons.forEach(button => {
        expect(button.withinPrimaryActions).toBe(true);
        expect(button.clipped).toBe(false);
        expect(button.tabIndex).toBeGreaterThanOrEqual(0);
        expect(button.order).toBe('0');
    });
    expect(evidence.modeButtons.map(button => button.action)).toEqual([
        'local-course-banner-builder-set-source-preview-mode',
        'local-course-banner-builder-set-source-preview-mode',
    ]);
    expect(evidence.frame.format).toBe(evidence.format);
    expect(evidence.frame.logicalWidth).toBe(390);
    expect(evidence.frame.logicalHeight).toBeGreaterThanOrEqual(128);
    // The mobile frame is intentionally narrower than the desktop editor root.
    // Containment in its owning surface is the responsive contract; comparing
    // it with the whole editor root incorrectly fails genuine desktop 200% use.
    expect(evidence.frame.frameWithinSurface).toBe(true);
    if (evidence.selectedSourceSticky) {
        expect(evidence.selectedSourceSticky.withinViewport).toBe(true);
        expect(evidence.selectedSourceSticky.leadingWithinSticky).toBe(true);
        expect(evidence.selectedSourceSticky.deselectWithinSticky).toBe(true);
        expect(evidence.selectedSourceSticky.deselectWithinViewport).toBe(true);
    }
    if (evidence.mode === 'mobile') {
        expect(evidence.frame.rect.width).toBeGreaterThan(0);
        expect(evidence.frame.rect.width).toBeLessThanOrEqual(evidence.frame.logicalWidth + 1);
    }
};

const assertSelectedSourceSticky = sticky => {
    if (!sticky) {
        return;
    }
    expect(sticky.holderInBody).toBe(true);
    expect(sticky.holderPortal).toBe(true);
    expect(sticky.holderState).toBe('stuck');
    expect(sticky.holderPosition).toBe('fixed');
    expect(Number.parseFloat(sticky.deselectMarginInlineEnd)).toBe(0);
    expect(sticky.fillsViewportWidth).toBe(true);
    expect(sticky.belowMoodleNavigation).toBe(true);
    expect(sticky.backgroundColor).not.toBe('rgba(0, 0, 0, 0)');
    expect(Number.parseFloat(sticky.borderTopWidth)).toBeGreaterThan(0);
    expect(sticky.withinViewport).toBe(true);
    expect(sticky.leadingWithinSticky).toBe(true);
    expect(sticky.deselectWithinSticky).toBe(true);
    expect(sticky.deselectWithinViewport).toBe(true);
    expect(sticky.titleOverlapsKicker).toBe(false);
    expect(sticky.leadingOverlapsDeselect).toBe(false);
    if (!sticky.desktopTrayLayout) {
        expect(sticky.deselectCentered).toBe(true);
    }
    if (sticky.desktopTrayLayout && sticky.drawerToggleRect) {
        expect(sticky.deselectOverlapsDrawerToggle).toBe(false);
    }
};

const assertSelectedSourceStickyHysteresis = async page => {
    const positioned = await setSelectedSourceStickyThreshold(page, 6);
    if (positioned.positioned) {
        await page.waitForFunction(() => {
            const holder = document.querySelector('.local-course-banner-builder-selected-source-sticky-holder');
            return Boolean(holder && holder.dataset.stickyState === 'stuck');
        }, null, {timeout: 2000});
        assertSelectedSourceSticky(await readSelectedSourceSticky(page));
    }
};

const setSelectedSourceStickyThreshold = async(page, offset) => page.evaluate(async targetOffset => {
    const holder = document.querySelector('.local-course-banner-builder-selected-source-sticky-holder');
    if (!holder) {
        return {positioned: false, reason: 'holder-missing'};
    }
    const placeholder = document.getElementById('local-course-banner-builder-selected-source-sticky-placeholder');
    const anchor = holder.parentElement === document.body && placeholder ? placeholder : holder;
    // Match the runtime priority exactly. The normal-flow #page-navbar can
    // scroll out of view while Moodle's actual navigation remains fixed.
    const navigation = document.querySelector('.navbar.fixed-top') ||
        document.querySelector('header.fixed-top') ||
        document.querySelector('#page-header.fixed-top') ||
        document.querySelector('#page-navbar');
    const navigationBottom = navigation ? Math.max(0, navigation.getBoundingClientRect().bottom) : 0;
    const targetViewportTop = navigationBottom + targetOffset;
    if (targetViewportTop < 0) {
        return {positioned: false, reason: 'navigation-invalid', targetViewportTop};
    }

    let container = anchor.parentElement;
    while (container && container !== document.body) {
        const style = getComputedStyle(container);
        if (/(auto|scroll|overlay)/.test(style.overflowY) && container.scrollHeight > container.clientHeight + 1) {
            break;
        }
        container = container.parentElement;
    }
    const documentContainer = !container || container === document.body;
    const currentScrollTop = documentContainer ? (window.scrollY || document.documentElement.scrollTop || 0) : container.scrollTop;
    const target = currentScrollTop + anchor.getBoundingClientRect().top - targetViewportTop;
    const maximum = documentContainer ?
        Math.max(0, document.documentElement.scrollHeight - window.innerHeight) :
        Math.max(0, container.scrollHeight - container.clientHeight);
    if (target <= 0 || target > maximum) {
        return {positioned: false, reason: 'target-out-of-range', target, maximum, anchorTop: anchor.getBoundingClientRect().top, targetViewportTop};
    }
    if (documentContainer) {
        window.scrollTo({top: target, behavior: 'auto'});
    } else {
        container.scrollTop = target;
    }
    // Let Chromium commit the scroll geometry before the production listener
    // receives its event. This mirrors the runtime's animation-frame schedule
    // instead of sampling a stale placeholder rect in the same task.
    await new Promise(resolve => window.requestAnimationFrame(() => window.requestAnimationFrame(resolve)));
    (documentContainer ? window : container).dispatchEvent(new Event('scroll'));
    return {positioned: true, target, maximum, anchorTop: anchor.getBoundingClientRect().top, targetViewportTop};
}, offset);

const assertSelectedSourceStickyOscillation = async page => {
    const transitions = [
        {offset: 1, state: 'stuck'},
        {offset: 13, state: 'inline'},
        {offset: 1, state: 'stuck'},
        {offset: 13, state: 'inline'},
        {offset: 1, state: 'stuck'},
    ];
    const samples = [];
    for (const transition of transitions) {
        const positioned = await setSelectedSourceStickyThreshold(page, transition.offset);
        expect(positioned.positioned,
            `The sticky anchor must be reachable on both sides of its hysteresis threshold: ${JSON.stringify(positioned)}`).toBe(true);
        await page.waitForFunction(expectedState => {
            const holder = document.querySelector('.local-course-banner-builder-selected-source-sticky-holder');
            return Boolean(holder && holder.dataset.stickyState === expectedState);
        }, transition.state, {timeout: 2000});
        const sample = await page.evaluate(() => {
            const holder = document.querySelector('.local-course-banner-builder-selected-source-sticky-holder');
            const placeholder = document.getElementById('local-course-banner-builder-selected-source-sticky-placeholder');
            const anchor = holder && holder.parentElement === document.body && placeholder ? placeholder : holder;
            const navigation = document.querySelector('.navbar.fixed-top') ||
                document.querySelector('header.fixed-top') ||
                document.querySelector('#page-header.fixed-top') ||
                document.querySelector('#page-navbar');
            const navigationBottom = navigation ? Math.max(0, navigation.getBoundingClientRect().bottom) : 0;
            return {
                holderCount: document.querySelectorAll('.local-course-banner-builder-selected-source-sticky-holder').length,
                placeholderCount: document.querySelectorAll('#local-course-banner-builder-selected-source-sticky-placeholder').length,
                state: holder ? (holder.dataset.stickyState || null) : null,
                portal: Boolean(holder && holder.dataset.stickyPortal === '1'),
                inBody: Boolean(holder && holder.parentElement === document.body),
                anchorViewportTop: anchor ? anchor.getBoundingClientRect().top : null,
                navigationBottom,
            };
        });
        expect(sample.holderCount).toBe(1);
        expect(sample.state,
            `Unexpected sticky transition: ${JSON.stringify({transition, positioned, sample})}`).toBe(transition.state);
        expect(sample.portal).toBe(transition.state === 'stuck');
        expect(sample.inBody).toBe(transition.state === 'stuck');
        expect(sample.placeholderCount).toBe(transition.state === 'stuck' ? 1 : 0);
        samples.push(sample);
    }
    return samples;
};

const assertSelectedSourceStickyRestores = async page => {
    const setScrollPosition = toEnd => page.evaluate(async end => {
        const holder = document.querySelector('.local-course-banner-builder-selected-source-sticky-holder');
        const placeholder = document.getElementById('local-course-banner-builder-selected-source-sticky-placeholder');
        let current = holder && holder.parentElement === document.body && placeholder ? placeholder : holder;
        while (current && current !== document.body) {
            const style = getComputedStyle(current);
            if (/(auto|scroll|overlay)/.test(style.overflowY) && current.scrollHeight > current.clientHeight + 1) {
                current.scrollTop = end ? current.scrollHeight : 0;
                await new Promise(resolve => window.requestAnimationFrame(() => window.requestAnimationFrame(resolve)));
                current.dispatchEvent(new Event('scroll'));
                return {nested: true, scrollTop: current.scrollTop};
            }
            current = current.parentElement;
        }
        window.scrollTo(0, end ? document.documentElement.scrollHeight : 0);
        await new Promise(resolve => window.requestAnimationFrame(() => window.requestAnimationFrame(resolve)));
        window.dispatchEvent(new Event('scroll'));
        return {nested: false, scrollTop: window.scrollY};
    }, toEnd);
    await setScrollPosition(true);
    await page.waitForFunction(() => {
        const holder = document.querySelector('.local-course-banner-builder-selected-source-sticky-holder');
        return Boolean(holder && holder.dataset.stickyState === 'stuck');
    }, null, {timeout: 2000});
    const floating = await readSelectedSourceSticky(page);
    assertSelectedSourceSticky(floating);
    await assertSelectedSourceStickyHysteresis(page);
    const oscillation = await assertSelectedSourceStickyOscillation(page);

    await setScrollPosition(false);
    await page.waitForFunction(() => {
        const holder = document.querySelector('.local-course-banner-builder-selected-source-sticky-holder');
        return Boolean(holder && holder.dataset.stickyState === 'inline');
    }, null, {timeout: 2000});
    const restored = await page.evaluate(() => {
        const holder = document.querySelector('.local-course-banner-builder-selected-source-sticky-holder');
        const deselect = holder?.querySelector('.local-course-banner-builder-sticky-deselect');
        return {
            inBody: Boolean(holder && holder.parentElement === document.body),
            floating: Boolean(holder && holder.classList.contains('focus-navigation-buttons-holder--floating')),
            portal: Boolean(holder && holder.dataset.stickyPortal === '1'),
            state: holder ? holder.dataset.stickyState || null : null,
            placeholderPresent: Boolean(document.getElementById('local-course-banner-builder-selected-source-sticky-placeholder')),
            position: holder ? getComputedStyle(holder).position : null,
            deselectMarginInlineEnd: deselect ? getComputedStyle(deselect).marginInlineEnd : null,
        };
    });
    expect(restored.inBody).toBe(false);
    expect(restored.floating).toBe(false);
    expect(restored.portal).toBe(false);
    expect(restored.state).toBe('inline');
    expect(restored.placeholderPresent).toBe(false);
    expect(restored.position).not.toBe('fixed');
    expect(Number.parseFloat(restored.deselectMarginInlineEnd)).toBeGreaterThan(0);
    return {oscillation, restored};
};

const inspectModalActionRail = async(page, context, file) => {
    const trigger = page.locator('[data-target="#local-course-banner-builder-add-layer-modal"], ' +
        '[data-bs-target="#local-course-banner-builder-add-layer-modal"]').first();
    await expect(trigger).toBeVisible();
    await trigger.click();
    const modal = page.locator('#local-course-banner-builder-add-layer-modal');
    await expect(modal).toBeVisible({timeout: 30000});
    const rail = modal.locator('[data-modal-preview-action-list="1"]').first();
    await expect(rail).toBeVisible({timeout: 30000});
    await rail.evaluate(element => element.scrollIntoView({block: 'center'}));
    await page.waitForTimeout(160);
    await captureCdp(page, context, file);
    const evidence = await rail.evaluate(element => {
        const body = element.closest('.local-course-banner-builder-layer-modal-body');
        const rect = node => {
            const box = node.getBoundingClientRect();
            return {left: box.left, right: box.right, top: box.top, bottom: box.bottom, width: box.width, height: box.height};
        };
        const bodyStyle = body ? getComputedStyle(body) : null;
        const railStyle = getComputedStyle(element);
        return {
            rail: rect(element),
            body: body ? rect(body) : null,
            railPosition: railStyle.position,
            bodyPaddingRight: bodyStyle ? Number.parseFloat(bodyStyle.paddingRight) : 0,
            viewportWidth: document.documentElement.clientWidth,
        };
    });
    expect(evidence.body).not.toBeNull();
    expect(evidence.rail.left).toBeGreaterThanOrEqual(evidence.body.left - 1);
    expect(evidence.rail.right).toBeLessThanOrEqual(evidence.body.right + 1);
    if (evidence.viewportWidth >= 992) {
        expect(evidence.railPosition).toBe('absolute');
        expect(evidence.bodyPaddingRight).toBeGreaterThanOrEqual(evidence.rail.width + 20);
    } else {
        expect(evidence.railPosition).toBe('static');
    }
    await modal.locator('[data-dismiss="modal"], [data-bs-dismiss="modal"]').first().click();
    await expect(modal).toBeHidden();
    await page.waitForTimeout(250);
    return evidence;
};

test('CCB source preview responsive layout remains contained and legible at the approved matrix', async({}, testInfo) => {
    test.setTimeout(180000);
    const env = requireEnvironment();
    const runRoot = path.join(env.artifactRoot, 'responsive', `${env.viewport.name}-${env.zoom}`);
    const artifact = name => path.join(runRoot, name);
    fs.mkdirSync(runRoot, {recursive: true});
    const zoomProfile = prepareNativeZoomProfile(env.profile, env.baseUrl, env.zoom);
    const consoleErrors = [];
    const failedRequests = [];
    const context = await chromium.launchPersistentContext(env.profile, {
        headless: env.zoom === 100,
        viewport: {width: env.viewport.width, height: env.viewport.height},
        deviceScaleFactor: 1,
        ignoreHTTPSErrors: true,
        args: env.zoom === 200 ? [
            '--window-position=-32000,-32000', '--start-minimized', '--disable-gpu',
            '--disable-features=CalculateNativeWinOcclusion', '--disable-backgrounding-occluded-windows',
        ] : ['--disable-gpu'],
    });
    const page = context.pages()[0] || await context.newPage();
    page.setDefaultTimeout(20000);
    page.on('console', message => {
        if (message.type() === 'error') {
            consoleErrors.push({text: message.text(), location: message.location()});
        }
    });
    page.on('requestfailed', request => {
        const failure = request.failure()?.errorText || 'unknown';
        if (!/ERR_ABORTED/i.test(failure)) {
            failedRequests.push({method: request.method(), resourceType: request.resourceType(), url: safeUrl(request.url()), failure});
        }
    });
    const evidence = {viewport: env.viewport, zoom: env.zoom, format: env.format, profile: env.profile, zoomProfile};
    let desktop = null;
    let mobile = null;
    try {
        await login(page, env);
        evidence.zoomEvidence = await cdpZoomEvidence(page, context);
        if (env.zoom === 200) {
            const widthRatio = env.viewport.width / Math.max(evidence.zoomEvidence.inPage.innerWidth, 1);
            const scale = evidence.zoomEvidence.inPage.visualViewportScale || 1;
            expect(widthRatio >= 1.9 || scale >= 1.9,
                'The 200% cell must prove genuine native Chromium zoom.').toBe(true);
        }
        const root = await openSourceEditor(page, env);
        await page.waitForTimeout(250);
        desktop = await readPreview(page, env);
        assertMaterialized(desktop);
        expect(desktop.mode).toBe('desktop');
        evidence.desktopCapture = await captureSourcePreview(page, context, root, artifact('source-preview-desktop.png'));
        desktop.selectedSourceSticky = await readSelectedSourceSticky(page);
        assertSelectedSourceSticky(desktop.selectedSourceSticky);
        evidence.stickyRoundTrip = await assertSelectedSourceStickyRestores(page);
        evidence.modalActionRail = await inspectModalActionRail(page, context, artifact('modal-action-rail.png'));

        const mobileButton = root.locator('[data-source-preview-mode-value="mobile"]');
        const desktopButton = root.locator('[data-source-preview-mode-value="desktop"]');
        await mobileButton.focus();
        await expect(mobileButton).toBeFocused();
        await mobileButton.press('ArrowLeft');
        await expect(desktopButton).toBeFocused();
        await desktopButton.press('ArrowRight');
        await expect(mobileButton).toBeFocused();
        await mobileButton.press('Space');
        await expect(root).toHaveAttribute('data-source-preview-mode', 'mobile');
        mobile = await readPreview(page, env);
        assertMaterialized(mobile);
        expect(mobile.mode).toBe('mobile');
        expect(Math.abs(mobile.frame.aspectRatio - (mobile.frame.logicalWidth / mobile.frame.logicalHeight))).toBeLessThanOrEqual(0.02);
        evidence.mobileCapture = await captureSourcePreview(page, context, root, artifact('source-preview-mobile.png'));
        mobile.selectedSourceSticky = await readSelectedSourceSticky(page);
        assertSelectedSourceSticky(mobile.selectedSourceSticky);
        if (env.viewport.width <= 576) {
            expect(mobile.selectedSourceSticky.leadingTextAlign).toBe('center');
            expect(mobile.selectedSourceSticky.titleTextAlign).toBe('center');
        }
        await desktopButton.focus();
        await desktopButton.press('Space');
        await expect(root).toHaveAttribute('data-source-preview-mode', 'desktop');

        if (env.zoom === 100 && env.viewport.width >= 1024) {
            expect(desktop.actionRows.length).toBe(1);
        }
        if (env.viewport.width <= 390 || env.zoom === 200) {
            expect(desktop.actionRows.length).toBeGreaterThanOrEqual(Math.min(2, desktop.actionButtons.length));
            expect(mobile.actionRows.length).toBeGreaterThanOrEqual(Math.min(2, mobile.actionButtons.length));
        }
        evidence.desktop = desktop;
        evidence.mobile = mobile;
        evidence.consoleErrors = consoleErrors;
        evidence.failedRequests = failedRequests;
        writeJson(artifact('responsive-evidence.json'), evidence);
        expect(consoleErrors).toEqual([]);
        expect(failedRequests).toEqual([]);
    } catch (error) {
        if (desktop) {
            evidence.desktop = desktop;
        }
        if (mobile) {
            evidence.mobile = mobile;
        }
        evidence.consoleErrors = consoleErrors;
        evidence.failedRequests = failedRequests;
        evidence.error = String(error && error.stack || error);
        writeJson(artifact('responsive-failure.json'), evidence);
        throw error;
    } finally {
        await context.close().catch(() => {});
    }
});
