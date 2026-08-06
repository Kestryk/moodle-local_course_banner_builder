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
        'EASYEDU_CCB_ORIENTATION_ADMIN_PATH', 'EASYEDU_CCB_ORIENTATION_PUBLIC_PATH',
    ];
    const missing = required.filter(name => !process.env[name]);
    ensure(!missing.length, 'Missing CCB orientation environment values: ' + missing.join(', '));

    const artifactRoot = path.resolve(process.env.EASYEDU_CCB_RESPONSIVE_ARTIFACT_ROOT);
    const profile = path.resolve(process.env.EASYEDU_CCB_RESPONSIVE_PROFILE);
    const separator = path.sep;
    ensure(path.isAbsolute(artifactRoot) && path.isAbsolute(profile), 'Orientation paths must be absolute.');
    ensure(profile.toLowerCase().startsWith((artifactRoot + separator).toLowerCase()),
        'The Chromium profile must remain inside the external artifact root.');
    ensure(!artifactRoot.toLowerCase().includes(separator + 'local' + separator + 'course_banner_builder'),
        'Orientation artifacts must remain outside the CCB repository.');
    [
        process.env.EASYEDU_CCB_ORIENTATION_ADMIN_PATH,
        process.env.EASYEDU_CCB_ORIENTATION_PUBLIC_PATH,
    ].forEach(route => ensure(route.startsWith('/') && !route.includes('://'),
        'Orientation routes must be relative Moodle paths supplied by the runner.'));
    ensure(/^\d+$/.test(String(process.env.EASYEDU_CCB_RESPONSIVE_FIXTURE_COURSE_ID)),
        'The orientation fixture course must be numeric.');
    ensure(/^\d+$/.test(String(process.env.EASYEDU_CCB_RESPONSIVE_SOURCE_CATEGORY_ID)),
        'The orientation source category must be numeric.');
    return {
        baseUrl: String(process.env.EASYEDU_MOODLE_URL).replace(/\/$/, ''),
        username: process.env.EASYEDU_MOODLE_USERNAME,
        password: process.env.EASYEDU_MOODLE_PASSWORD,
        courseId: String(process.env.EASYEDU_CCB_RESPONSIVE_FIXTURE_COURSE_ID),
        sourceKey: 'category:' + process.env.EASYEDU_CCB_RESPONSIVE_SOURCE_CATEGORY_ID,
        adminPath: process.env.EASYEDU_CCB_ORIENTATION_ADMIN_PATH,
        publicPath: process.env.EASYEDU_CCB_ORIENTATION_PUBLIC_PATH,
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

const login = async(page, env) => {
    await page.goto(env.baseUrl + '/login/index.php', {waitUntil: 'domcontentloaded'});
    await page.locator('#username').fill(env.username);
    await page.locator('#password').fill(env.password);
    await page.locator('#loginbtn').click({noWaitAfter: true});
    await expect(page).not.toHaveURL(/\/login\//, {timeout: 30000});
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

const readLayoutEvidence = async page => page.evaluate(() => {
    const root = document.querySelector('[data-source-visual-editor="1"]');
    const hint = root ? root.querySelector('[data-source-preview-orientation-hint="1"]') : null;
    const hintRect = hint ? hint.getBoundingClientRect() : null;
    const rootRect = root ? root.getBoundingClientRect() : null;
    const layout = root ? root.querySelector('.local-course-banner-builder-source-preview-layout') : null;
    const controls = root ? root.querySelector('.local-course-banner-builder-source-preview-controls') : null;
    const overflowNodes = root ? Array.from(root.querySelectorAll('*')).map(node => {
        const rect = node.getBoundingClientRect();
        return {
            node,
            tag: node.tagName,
            className: node.className,
            scrollWidth: node.scrollWidth,
            clientWidth: node.clientWidth,
            left: rect.left,
            right: rect.right,
        };
    }).filter(item => item.scrollWidth > item.clientWidth + 1 ||
        (rootRect && (item.left < rootRect.left - 1 || item.right > rootRect.right + 1)))
        .slice(0, 20).map(item => ({
            tag: item.tag,
            className: String(item.className),
            scrollWidth: item.scrollWidth,
            clientWidth: item.clientWidth,
            left: item.left,
            right: item.right,
        })) : [];
    return {
        innerWidth: window.innerWidth,
        innerHeight: window.innerHeight,
        orientationMediaMatches: window.matchMedia ?
            window.matchMedia('(max-width: 576px) and (orientation: portrait)').matches : null,
        orientationTrace: window.__ccbOrientationTrace || [],
        rootPresent: !!root,
        hintPresent: !!hint,
        hintHidden: hint ? hint.hidden : null,
        hintVisible: !!(hint && !hint.hidden && getComputedStyle(hint).display !== 'none' &&
            hintRect && hintRect.width > 0 && hintRect.height > 0),
        dismissPresent: !!(hint && hint.querySelector('[data-source-preview-orientation-dismiss="1"]')),
        documentClientWidth: document.documentElement.clientWidth,
        documentScrollWidth: document.documentElement.scrollWidth,
        rootClientWidth: root ? root.clientWidth : null,
        rootScrollWidth: root ? root.scrollWidth : null,
        layout: layout ? {
            clientWidth: layout.clientWidth,
            scrollWidth: layout.scrollWidth,
            rect: (() => { const rect = layout.getBoundingClientRect(); return {left: rect.left, right: rect.right, width: rect.width}; })(),
            gridTemplateColumns: getComputedStyle(layout).gridTemplateColumns,
        } : null,
        controls: controls ? {
            clientWidth: controls.clientWidth,
            scrollWidth: controls.scrollWidth,
            gridTemplateColumns: getComputedStyle(controls).gridTemplateColumns,
        } : null,
        overflowNodes,
        hintRect: hintRect ? {
            left: hintRect.left, right: hintRect.right, width: hintRect.width,
            top: hintRect.top, bottom: hintRect.bottom, height: hintRect.height,
        } : null,
        rootRect: rootRect ? {
            left: rootRect.left, right: rootRect.right, width: rootRect.width,
            top: rootRect.top, bottom: rootRect.bottom, height: rootRect.height,
        } : null,
    };
});

const assertNoOverflow = evidence => {
    expect(evidence.documentScrollWidth).toBeLessThanOrEqual(evidence.documentClientWidth + 1);
    if (evidence.hintVisible && evidence.hintRect && evidence.rootRect) {
        expect(evidence.hintRect.left).toBeGreaterThanOrEqual(evidence.rootRect.left - 1);
        expect(evidence.hintRect.right).toBeLessThanOrEqual(evidence.rootRect.right + 1);
    }
};

const runCell = async(env, zoom, cellRoot) => {
    const zoomProfile = prepareNativeZoomProfile(env.profile, env.baseUrl, zoom);
    const consoleErrors = [];
    const failedRequests = [];
    const context = await chromium.launchPersistentContext(env.profile, {
        headless: zoom === 100,
        viewport: {width: 390, height: 844},
        deviceScaleFactor: 1,
        ignoreHTTPSErrors: true,
        args: zoom === 200 ? [
            '--window-position=-32000,-32000', '--start-minimized', '--disable-gpu',
            '--disable-features=CalculateNativeWinOcclusion', '--disable-backgrounding-occluded-windows',
        ] : ['--disable-gpu'],
    });
    const page = context.pages()[0] || await context.newPage();
    await page.addInitScript(() => {
        window.__ccbOrientationTrace = [];
        new MutationObserver(records => {
            records.forEach(record => {
                if (record.type === 'attributes' && record.target.matches &&
                        record.target.matches('[data-source-preview-orientation-hint="1"]')) {
                    window.__ccbOrientationTrace.push({
                        hidden: record.target.hidden,
                        rootDismissed: record.target.closest('[data-source-visual-editor="1"]')?.dataset.sourcePreviewOrientationDismissed || '',
                        innerWidth: window.innerWidth,
                        innerHeight: window.innerHeight,
                        mediaMatches: window.matchMedia ?
                            window.matchMedia('(max-width: 576px) and (orientation: portrait)').matches : null,
                    });
                }
            });
        }).observe(document, {subtree: true, attributes: true, attributeFilter: ['hidden']});
    });
    page.setDefaultTimeout(20000);
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
    const evidence = {zoom, zoomProfile, cells: {}, consoleErrors, failedRequests};
    const adminUrl = new URL(env.adminPath, env.baseUrl + '/');
    adminUrl.searchParams.set('sourcekey', env.sourceKey);
    const publicUrl = new URL(env.publicPath, env.baseUrl + '/');
    publicUrl.searchParams.set('id', env.courseId);
    try {
        await login(page, env);
        await page.goto(adminUrl.toString(), {waitUntil: 'domcontentloaded'});
        const root = page.locator('[data-source-visual-editor="1"]').first();
        const hint = root.locator('[data-source-preview-orientation-hint="1"]').first();
        await expect(root).toBeVisible();
        const initialPortrait = await readLayoutEvidence(page);
        evidence.cells.initialPortrait = initialPortrait;
        writeJson(path.join(cellRoot, 'orientation-initial-evidence.json'), evidence);
        await expect(hint).toBeVisible();
        const portrait = await readLayoutEvidence(page);
        assertNoOverflow(portrait);
        expect(portrait.hintVisible).toBe(true);
        expect(portrait.dismissPresent).toBe(true);
        const hintText = await hint.locator('.local-course-banner-builder-source-preview-orientation-hint-message').textContent();
        expect(hintText).toBeTruthy();
        expect(hintText.trim()).not.toMatch(/^\[\[/);
        evidence.cells.portrait = portrait;
        await hint.scrollIntoViewIfNeeded();
        await page.mouse.move(0, 0);
        await captureCdp(page, context, path.join(cellRoot, 'orientation-' + zoom + '-portrait-visible.png'));
        const dismiss = page.locator('[data-source-preview-orientation-dismiss="1"]').first();
        await dismiss.focus();
        await expect(dismiss).toBeFocused();
        await dismiss.press('Enter');
        await expect(hint).toBeHidden();
        evidence.cells.dismissed = await readLayoutEvidence(page);
        await captureCdp(page, context, path.join(cellRoot, 'orientation-' + zoom + '-portrait.png'));

        await page.setViewportSize({width: 844, height: 390});
        await page.goto(adminUrl.toString(), {waitUntil: 'domcontentloaded'});
        const landscapeHint = page.locator('[data-source-preview-orientation-hint="1"]').first();
        await expect(landscapeHint).toBeHidden();
        evidence.cells.landscape = await readLayoutEvidence(page);
        assertNoOverflow(evidence.cells.landscape);

        await page.setViewportSize({width: 1024, height: 768});
        await page.goto(adminUrl.toString(), {waitUntil: 'domcontentloaded'});
        const wideHint = page.locator('[data-source-preview-orientation-hint="1"]').first();
        await expect(wideHint).toBeHidden();
        evidence.cells.wide = await readLayoutEvidence(page);
        assertNoOverflow(evidence.cells.wide);

        await page.goto(publicUrl.toString(), {waitUntil: 'domcontentloaded'});
        await expect(page.locator('[data-source-visual-editor="1"]')).toHaveCount(0);
        await expect(page.locator('[data-source-preview-orientation-hint="1"]')).toHaveCount(0);
        evidence.cells.public = await readLayoutEvidence(page);
        assertNoOverflow(evidence.cells.public);
        expect(evidence.cells.public.rootPresent).toBe(false);
        expect(evidence.cells.public.hintPresent).toBe(false);
        evidence.consoleErrors = consoleErrors;
        evidence.failedRequests = failedRequests;
        writeJson(path.join(cellRoot, 'orientation-evidence.json'), evidence);
        expect(consoleErrors).toEqual([]);
        expect(failedRequests).toEqual([]);
        return evidence;
    } catch (error) {
        evidence.consoleErrors = consoleErrors;
        evidence.failedRequests = failedRequests;
        evidence.error = String(error && error.stack || error);
        writeJson(path.join(cellRoot, 'orientation-failure.json'), evidence);
        throw error;
    } finally {
        await context.close();
    }
};

test('CCB orientation hint is scoped to narrow portrait banner editing', async() => {
    test.setTimeout(240000);
    const env = requireEnvironment();
    const runRoot = path.join(env.artifactRoot, 'orientation-hint');
    fs.mkdirSync(runRoot, {recursive: true});
    const evidence = {artifactRoot: env.artifactRoot, cells: {}};
    try {
        evidence.cells.zoom100 = await runCell(env, 100, path.join(runRoot, '100'));
        evidence.cells.zoom200 = await runCell(env, 200, path.join(runRoot, '200'));
        writeJson(path.join(runRoot, 'orientation-summary.json'), evidence);
    } catch (error) {
        writeJson(path.join(runRoot, 'orientation-summary-failure.json'), {
            error: String(error && error.stack || error), evidence,
        });
        throw error;
    }
});
