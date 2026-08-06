const {test, expect, chromium} = require('@playwright/test');
const fs = require('fs');
const path = require('path');
const {metadata: {ccbArtifactsRoot}} = require('./playwright.config');

const requireEnvironment = () => {
    const required = [
        'EASYEDU_MOODLE_URL', 'EASYEDU_MOODLE_USERNAME', 'EASYEDU_MOODLE_PASSWORD',
        'EASYEDU_CCB_FIXTURE_COURSE_ID', 'EASYEDU_CCB_2A_SOURCE_CATEGORY_ID',
        'EASYEDU_CCB_2A_PROFILE',
    ];
    const missing = required.filter(name => !process.env[name]);
    if (missing.length) {
        throw new Error(`Missing Batch 2A.2 environment values: ${missing.join(', ')}`);
    }
    const courseId = String(process.env.EASYEDU_CCB_FIXTURE_COURSE_ID);
    const sourceCategoryId = String(process.env.EASYEDU_CCB_2A_SOURCE_CATEGORY_ID);
    if (courseId !== '11' || !/^\d+$/.test(sourceCategoryId)) {
        throw new Error('Batch 2A.2 requires course 11 and a numeric source category.');
    }
    const profile = path.resolve(process.env.EASYEDU_CCB_2A_PROFILE);
    const artifactRoot = path.resolve(process.env.EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT || ccbArtifactsRoot);
    return {
        baseUrl: String(process.env.EASYEDU_MOODLE_URL).replace(/\/$/, ''),
        username: process.env.EASYEDU_MOODLE_USERNAME,
        password: process.env.EASYEDU_MOODLE_PASSWORD,
        courseId,
        sourceCategoryId,
        profile,
        artifactRoot,
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
            (String(element.className).match(/format-([a-z0-9-]+)/) || [])[1] || null,
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
    await button.click();
    const frame = page.locator('[data-source-preview-frame="1"]').first();
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

const getCourseFormat = async(page, env) => {
    await page.goto(`${env.baseUrl}/local/course_banner_builder/admin_manage.php`, {waitUntil: 'domcontentloaded'});
    await page.locator('.local-course-banner-builder-admin-format-button').first().click();
    const modal = page.locator('.modal.show').last();
    await expect(modal).toBeVisible({timeout: 30000});
    const format = await modal.locator('input[name="bannerformat"]:checked').inputValue();
    await modal.locator('[data-dismiss="modal"], [data-bs-dismiss="modal"]').first().click();
    await expect(modal).toBeHidden({timeout: 10000});
    return format;
};

const setCourseFormat = async(page, env, format) => {
    await page.goto(`${env.baseUrl}/local/course_banner_builder/admin_manage.php`, {waitUntil: 'domcontentloaded'});
    await page.locator('.local-course-banner-builder-admin-format-button').first().click();
    const modal = page.locator('.modal.show').last();
    await expect(modal).toBeVisible({timeout: 30000});
    const formatInput = modal.locator(`input[name="bannerformat"][value="${format}"]`);
    await formatInput.evaluate(input => input.click());
    await expect(formatInput).toBeChecked();
    await modal.locator('button[type="submit"]').click();
    await expect(modal).toBeHidden({timeout: 10000});
    await page.reload({waitUntil: 'domcontentloaded'});
};

test('CCB Batch 2A.2 measures preview/public geometry at tablet contentwide format', async() => {
    test.setTimeout(180000);
    const env = requireEnvironment();
    const format = 'contentwide';
    const expectedAspectRatio = 5;
    const viewport = {name: 'tablet', width: 1024, height: 768};
    const scenario = 'ccb-2a2-contentwide-tablet';
    const runRoot = path.join(env.artifactRoot, 'ccb', 'batch-2a', 'geometry', scenario);
    fs.mkdirSync(runRoot, {recursive: true});
    const consoleErrors = [];
    const failedRequests = [];
    const context = await chromium.launchPersistentContext(env.profile, {
        headless: true,
        viewport: {width: viewport.width, height: viewport.height},
        deviceScaleFactor: 1,
        ignoreHTTPSErrors: true,
    });
    const page = await context.newPage();
    let originalFormat = null;
    page.on('console', message => {
        if (message.type() === 'error') {
            consoleErrors.push({text: message.text(), location: message.location()});
        }
    });
    page.on('requestfailed', request => {
        failedRequests.push({method: request.method(), resourceType: request.resourceType(), url: safeUrl(request.url()),
            failure: request.failure()?.errorText || 'unknown'});
    });
    const evidence = {scenario, courseId: env.courseId, sourceCategoryId: env.sourceCategoryId, format,
        expectedAspectRatio, viewport, profile: env.profile};
    try {
        await login(page, env);
        originalFormat = await getCourseFormat(page, env);
        await setCourseFormat(page, env, format);
        const admin = await openAdmin(page, env);
        const publicSurface = await openPublic(page, env);
        evidence.admin = admin;
        evidence.public = publicSurface;
        const count = Math.min(admin.children.length, publicSurface.children.length);
        expect(count, 'Preview and public surfaces must share a first bounded cell.').toBeGreaterThan(0);
        const firstPreview = admin.children[0].normalised;
        const firstPublic = publicSurface.children[0].normalised;
        const deltas = Object.fromEntries(Object.keys(firstPreview).map(key => [key,
            Number(Math.abs(firstPreview[key] - firstPublic[key]).toFixed(6))]));
        Object.values(deltas).forEach(delta => expect(delta, 'First bounded-cell geometry drift exceeded 2%.').toBeLessThanOrEqual(0.02));
        expect(admin.format, 'The preview and public surface must use the same banner format.').toBe(publicSurface.format);
        expect(admin.format).toBe(format);
        expect(Math.abs(admin.aspectRatio - expectedAspectRatio) / expectedAspectRatio,
            'The admin preview must use the selected contentwide aspect ratio.').toBeLessThanOrEqual(0.02);
        expect(Math.abs(publicSurface.aspectRatio - expectedAspectRatio) / expectedAspectRatio,
            'The public banner must use the selected contentwide aspect ratio.').toBeLessThanOrEqual(0.02);
        expect(admin.aspectRatio).toBeGreaterThan(0);
        expect(publicSurface.aspectRatio).toBeGreaterThan(0);
        expect(admin.overflow).toBe('hidden');
        expect(publicSurface.scrollWidth).toBeLessThanOrEqual(publicSurface.clientWidth + 1);
        expect(publicSurface.scrollHeight).toBeLessThanOrEqual(publicSurface.clientHeight + 1);
        evidence.firstCellDeltas = deltas;
        evidence.consoleErrors = consoleErrors;
        evidence.failedRequests = failedRequests;
        writeJson(path.join(runRoot, 'geometry-evidence.json'), evidence);
        expect(consoleErrors, 'No browser console errors are allowed.').toEqual([]);
        const actionableFailures = failedRequests.filter(request => request.failure !== 'net::ERR_ABORTED');
        expect(actionableFailures, 'No actionable browser requests may fail.').toEqual([]);
    } finally {
        if (originalFormat) {
            await setCourseFormat(page, env, originalFormat);
            const restoredFormat = await getCourseFormat(page, env);
            if (restoredFormat !== originalFormat) {
                throw new Error(`CCB format restoration failed: expected ${originalFormat}, found ${restoredFormat}.`);
            }
        }
        evidence.consoleErrors = consoleErrors;
        evidence.failedRequests = failedRequests;
        writeJson(path.join(runRoot, 'runtime-summary.json'), evidence);
        await context.close();
    }
});
