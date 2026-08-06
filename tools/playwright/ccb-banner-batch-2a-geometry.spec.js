const {test, expect, chromium} = require('@playwright/test');
const fs = require('fs');
const path = require('path');
const {metadata: {ccbArtifactsRoot}} = require('./playwright.config');

const requireEnvironment = () => {
    const required = [
        'EASYEDU_MOODLE_URL', 'EASYEDU_MOODLE_USERNAME', 'EASYEDU_MOODLE_PASSWORD',
        'EASYEDU_CCB_FIXTURE_COURSE_ID', 'EASYEDU_CCB_2A_SOURCE_CATEGORY_ID',
        'EASYEDU_CCB_2A_PROFILE', 'EASYEDU_CCB_2A_VIEWPORT', 'EASYEDU_CCB_2A_FORMAT',
        'EASYEDU_CCB_2A_ZOOM',
    ];
    const missing = required.filter(name => !process.env[name]);
    if (missing.length) {
        throw new Error(`Missing Batch 2A environment values: ${missing.join(', ')}`);
    }
    const courseId = String(process.env.EASYEDU_CCB_FIXTURE_COURSE_ID);
    const sourceCategoryId = String(process.env.EASYEDU_CCB_2A_SOURCE_CATEGORY_ID);
    if (!/^11$/.test(courseId) || !/^\d+$/.test(sourceCategoryId)) {
        throw new Error('Batch 2A requires course 11 and a numeric temporary source category.');
    }
    const profile = path.resolve(process.env.EASYEDU_CCB_2A_PROFILE);
    const artifactRoot = path.resolve(process.env.EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT || ccbArtifactsRoot);
    if (!path.isAbsolute(profile) || !path.isAbsolute(artifactRoot)) {
        throw new Error('Batch 2A profile and artifacts must be absolute paths.');
    }
    return {
        baseUrl: String(process.env.EASYEDU_MOODLE_URL).replace(/\/$/, ''),
        username: process.env.EASYEDU_MOODLE_USERNAME,
        password: process.env.EASYEDU_MOODLE_PASSWORD,
        courseId,
        sourceCategoryId,
        profile,
        artifactRoot,
        format: String(process.env.EASYEDU_CCB_2A_FORMAT),
        zoom: Number(process.env.EASYEDU_CCB_2A_ZOOM),
        viewport: (() => {
            const match = String(process.env.EASYEDU_CCB_2A_VIEWPORT).match(/^(\d+)x(\d+)$/);
            if (!match) {
                throw new Error('Batch 2A viewport must use WIDTHxHEIGHT notation.');
            }
            return {name: String(process.env.EASYEDU_CCB_2A_VIEWPORT), width: Number(match[1]), height: Number(match[2])};
        })(),
    };
};

const safeUrl = value => {
    try {
        const parsed = new URL(value);
        parsed.search = '';
        parsed.hash = '';
        return parsed.toString();
    } catch (error) {
        return String(value).replace(/([?&](?:sesskey|password|token)=[^&\s]+)/gi, '$1[redacted]');
    }
};

const writeJson = (file, value) => {
    fs.mkdirSync(path.dirname(file), {recursive: true});
    fs.writeFileSync(file, JSON.stringify(value, null, 2));
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

const prepareNativeZoomProfile = (profile, baseUrl, percentage) => {
    if (percentage === 100) {
        return null;
    }
    const parsedUrl = new URL(baseUrl);
    const host = parsedUrl.hostname;
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
    return {host, percentage, zoomLevel};
};

const login = async(page, env) => {
    await page.goto(`${env.baseUrl}/login/index.php`, {waitUntil: 'domcontentloaded'});
    await page.locator('#username').fill(env.username);
    await page.locator('#password').fill(env.password);
    await page.locator('#loginbtn').click({noWaitAfter: true});
    await expect(page).not.toHaveURL(/\/login\//, {timeout: 30000});
};

const inspectSurface = async(locator, childSelector) => locator.evaluate((element, selector) => {
    const box = element.getBoundingClientRect();
    const style = getComputedStyle(element);
    const children = Array.from(element.querySelectorAll(selector)).map(child => {
        const childBox = child.getBoundingClientRect();
        return {
            x: childBox.x, y: childBox.y, width: childBox.width, height: childBox.height,
            normalised: {
                left: box.width ? (childBox.left - box.left) / box.width : null,
                top: box.height ? (childBox.top - box.top) / box.height : null,
                width: box.width ? childBox.width / box.width : null,
                height: box.height ? childBox.height / box.height : null,
            },
        };
    });
    return {
        box: {x: box.x, y: box.y, width: box.width, height: box.height},
        aspectRatio: box.height ? Number((box.width / box.height).toFixed(6)) : null,
        format: element.getAttribute('data-banner-format') ||
            (element.className.match(/native-course-banner--format-([a-z0-9-]+)/) || [])[1] || null,
        minHeight: style.minHeight,
        overflow: style.overflow,
        scrollWidth: element.scrollWidth,
        clientWidth: element.clientWidth,
        scrollHeight: element.scrollHeight,
        clientHeight: element.clientHeight,
        children,
    };
}, childSelector);

const openAdmin = async(page, env) => {
    await page.goto(`${env.baseUrl}/local/course_banner_builder/admin_manage.php?sourcekey=category:${env.sourceCategoryId}`, {
        waitUntil: 'domcontentloaded',
    });
    const button = page.locator('[data-action="local-course-banner-builder-show-source-chain-preview"]').first();
    await expect(button).toBeVisible({timeout: 30000});
    const modal = page.locator('#local-course-banner-builder-source-chain-preview-modal');
    // Moodle's AMD delegation can finish just after DOMContentLoaded on a
    // cold profile. Retry the same preview action without changing product
    // state, and only continue once the modal-owned frame exists.
    await page.waitForTimeout(750);
    let modalError = null;
    for (let attempt = 0; attempt < 3; attempt++) {
        if (await modal.isVisible().catch(() => false)) {
            break;
        }
        await button.click();
        try {
            await expect(modal).toBeVisible({timeout: 15000});
            modalError = null;
            break;
        } catch (error) {
            modalError = error;
            await page.waitForTimeout(750);
        }
    }
    if (modalError) {
        throw modalError;
    }
    const frame = modal.locator('[data-source-preview-frame="1"]').first();
    await expect(frame).toBeVisible({timeout: 30000});
    const surface = await inspectSurface(frame, '[data-source-preview-border="1"]');
    expect(surface.children.length, 'The preview must expose at least one bounded layer.').toBeGreaterThan(0);
    return surface;
};

const openPublic = async(page, env) => {
    await page.goto(`${env.baseUrl}/course/view.php?id=${encodeURIComponent(env.courseId)}`, {
        waitUntil: 'domcontentloaded',
    });
    const banner = page.locator('.local-course-banner-builder-native-course-banner').first();
    await expect(banner).toBeVisible({timeout: 30000});
    await page.waitForTimeout(1200);
    const surface = await inspectSurface(banner, '.local-course-banner-builder-fixed-border');
    expect(surface.children.length, 'The public banner must expose at least one bounded layer.').toBeGreaterThan(0);
    return surface;
};

test('CCB Batch 2A.1 measures preview/public geometry across approved format cells', async({}, testInfo) => {
    test.setTimeout(180000);
    const env = requireEnvironment();
    const runRoot = path.join(env.artifactRoot, 'ccb', 'batch-2a', 'geometry',
        process.env.EASYEDU_CCB_2A_SCENARIO_ID || 'ccb-2a1-100');
    fs.mkdirSync(runRoot, {recursive: true});
    const consoleErrors = [];
    const failedRequests = [];
    const nativeZoom = prepareNativeZoomProfile(env.profile, env.baseUrl, env.zoom);
    let context = null;
    const contextOptions = {
        headless: env.zoom === 100,
        viewport: {width: env.viewport.width, height: env.viewport.height},
        deviceScaleFactor: 1,
        ignoreHTTPSErrors: true,
        args: env.zoom === 200 ? [
            '--window-position=-32000,-32000',
            '--start-minimized',
            '--disable-gpu',
            '--disable-features=CalculateNativeWinOcclusion',
            '--disable-backgrounding-occluded-windows',
        ] : ['--disable-gpu'],
    };
    context = await chromium.launchPersistentContext(env.profile, contextOptions);
    const page = context.pages()[0] || await context.newPage();
    page.on('console', message => {
        if (message.type() === 'error') {
            consoleErrors.push({text: message.text(), location: message.location()});
        }
    });
    page.on('requestfailed', request => {
        failedRequests.push({method: request.method(), resourceType: request.resourceType(), url: safeUrl(request.url()),
            failure: request.failure()?.errorText || 'unknown'});
    });
    const evidence = {
        scenario: process.env.EASYEDU_CCB_2A_SCENARIO_ID || 'ccb-2a1-100',
        courseId: env.courseId,
        sourceCategoryId: env.sourceCategoryId,
        viewport: env.viewport,
        format: env.format,
        zoom: env.zoom,
        nativeZoom,
        profile: env.profile,
    };
    try {
        await login(page, env);
        const zoomEvidence = await cdpZoomEvidence(page, context);
        evidence.zoomEvidence = zoomEvidence;
        if (env.zoom === 200) {
            const widthRatio = env.viewport.width / Math.max(zoomEvidence.inPage.innerWidth, 1);
            const scale = zoomEvidence.inPage.visualViewportScale || 1;
            expect(widthRatio >= 1.9 || scale >= 1.9,
                'The 200% cell must use genuine native Chromium zoom, not deviceScaleFactor emulation.').toBe(true);
        }
        const admin = await openAdmin(page, env);
        await captureCdp(page, context, path.join(runRoot, 'admin-preview.png'));
        const publicSurface = await openPublic(page, env);
        await captureCdp(page, context, path.join(runRoot, 'public-course.png'));
        const count = Math.min(admin.children.length, publicSurface.children.length);
        evidence.admin = admin;
        evidence.public = publicSurface;
        writeJson(path.join(runRoot, 'geometry-partial.json'), evidence);
        expect(count, 'Preview and public surfaces must share a first bounded cell.').toBeGreaterThan(0);
        const firstPreview = admin.children[0].normalised;
        const firstPublic = publicSurface.children[0].normalised;
        const deltas = Object.fromEntries(Object.keys(firstPreview).map(key => [key,
            Number(Math.abs(firstPreview[key] - firstPublic[key]).toFixed(6))]));
        Object.values(deltas).forEach(delta => expect(delta, 'First bounded-cell geometry drift exceeded 2%.').toBeLessThanOrEqual(0.02));
        expect(admin.format, 'The preview and public surface must use the same banner format.').toBe(publicSurface.format);
        expect(admin.format, 'The rendered cell must use the requested CCB banner format.').toBe(env.format);
        expect(admin.aspectRatio, 'The admin preview must expose a finite frame ratio.').toBeGreaterThan(0);
        expect(publicSurface.aspectRatio, 'The public banner must expose a finite frame ratio.').toBeGreaterThan(0);
        // Admin image layers may intentionally exceed the preview scroll box
        // for crop/fit geometry; the frame's overflow policy bounds rendering.
        expect(admin.overflow).toBe('hidden');
        expect(publicSurface.scrollWidth).toBeLessThanOrEqual(publicSurface.clientWidth + 1);
        expect(publicSurface.scrollHeight).toBeLessThanOrEqual(publicSurface.clientHeight + 1);
        evidence.admin = admin;
        evidence.public = publicSurface;
        evidence.firstCellDeltas = deltas;
        evidence.consoleErrors = consoleErrors;
        evidence.failedRequests = failedRequests;
        writeJson(path.join(runRoot, 'geometry-evidence.json'), evidence);
        expect(consoleErrors, 'No browser console errors are allowed.').toEqual([]);
        // Moodle navigation and lazy image/XHR teardown commonly report
        // ERR_ABORTED after the authoritative DOM is already captured. Keep
        // the complete list in evidence, but fail on actionable network errors.
        const actionableFailures = failedRequests.filter(request => request.failure !== 'net::ERR_ABORTED');
        expect(actionableFailures, 'No actionable browser requests may fail.').toEqual([]);
    } finally {
        evidence.consoleErrors = consoleErrors;
        evidence.failedRequests = failedRequests;
        writeJson(path.join(runRoot, 'runtime-summary.json'), evidence);
        if (context) {
            await context.close();
        }
    }
});
