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

const safeUrl = value => String(value).replace(/([?&](?:sesskey|token|password|session(?:id)?|auth)=[^&\s]+)/gi, '$1[redacted]');

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

const matrixViewports = [
    {name: 'desktop', width: 1600, height: 900},
    {name: 'tablet', width: 768, height: 1024},
    {name: 'mobile', width: 390, height: 844},
];

const previewGeometry = async(preview) => preview.evaluate((element) => {
    const bounds = element.getBoundingClientRect();
    const elements = [
        '.local-course-banner-builder-slideshow-labels',
        '.local-course-banner-builder-slideshow-title-block',
        '.local-course-banner-builder-slideshow-body-block',
        '.local-course-banner-builder-slideshow-action-wrap',
    ];
    const samples = elements.map((selector) => {
        const child = element.querySelector(selector);
        if (!child) {
            return {selector, present: false};
        }
        const rect = child.getBoundingClientRect();
        return {
            selector,
            present: true,
            width: rect.width,
            height: rect.height,
            left: rect.left - bounds.left,
            right: rect.right - bounds.left,
            top: rect.top - bounds.top,
            bottom: rect.bottom - bounds.top,
        };
    });
    return {width: bounds.width, height: bounds.height, samples};
});

const expectRenderableGeometry = (geometry) => {
    expect(geometry.width).toBeGreaterThan(0);
    expect(geometry.height).toBeGreaterThan(0);
    for (const sample of geometry.samples) {
        expect(sample.present, sample.selector + ' is missing').toBeTruthy();
        expect(sample.width, sample.selector + ' has no width').toBeGreaterThan(0);
        expect(sample.height, sample.selector + ' has no height').toBeGreaterThan(0);
        expect(sample.left, sample.selector + ' escapes the preview on the left').toBeGreaterThanOrEqual(-1);
        expect(sample.top, sample.selector + ' escapes the preview at the top').toBeGreaterThanOrEqual(-1);
        expect(sample.right, sample.selector + ' escapes the preview on the right').toBeLessThanOrEqual(geometry.width + 2);
        expect(sample.bottom, sample.selector + ' escapes the preview at the bottom').toBeLessThanOrEqual(geometry.height + 2);
    }
};

const environment = () => {
    const required = [
        'EASYEDU_MOODLE_URL',
        'EASYEDU_MOODLE_USERNAME',
        'EASYEDU_MOODLE_PASSWORD',
        'EASYEDU_CCB_SLIDESHOW_COURSE_ID',
        'EASYEDU_CCB_SLIDESHOW_PROFILE',
        'EASYEDU_CCB_SLIDESHOW_ARTIFACT_ROOT',
    ];
    const missing = required.filter(name => !process.env[name]);
    ensure(missing.length === 0, 'Missing Slideshow validation values: ' + missing.join(', '));
    const artifactRoot = path.resolve(process.env.EASYEDU_CCB_SLIDESHOW_ARTIFACT_ROOT);
    const profile = path.resolve(process.env.EASYEDU_CCB_SLIDESHOW_PROFILE);
    ensure(path.isAbsolute(artifactRoot) && path.isAbsolute(profile), 'Slideshow artifact paths must be absolute.');
    ensure(profile.toLowerCase().startsWith((artifactRoot + path.sep).toLowerCase()),
        'Slideshow profile must be owned by the external artifact root.');
    ensure(/^\d+$/.test(process.env.EASYEDU_CCB_SLIDESHOW_COURSE_ID), 'Fixture course ID must be numeric.');
    return {
        artifactRoot,
        baseUrl: String(process.env.EASYEDU_MOODLE_URL).replace(/\/$/, ''),
        password: process.env.EASYEDU_MOODLE_PASSWORD,
        profile,
        username: process.env.EASYEDU_MOODLE_USERNAME,
    };
};

const login = async(page, env) => {
    await page.goto(env.baseUrl + '/login/index.php', {waitUntil: 'domcontentloaded'});
    await page.locator('#username').fill(env.username);
    await page.locator('#password').fill(env.password);
    await page.locator('#loginbtn').click({noWaitAfter: true});
    await expect(page).not.toHaveURL(/\/login\//, {timeout: 30000});
};

test('CCB Slideshow rendering matrix keeps course and site previews readable', async() => {
    const env = environment();
    const consoleErrors = [];
    const requestFailures = [];
    let collectRuntimeFailures = false;
    const context = await chromium.launchPersistentContext(env.profile, {headless: true});
    const page = await context.newPage();
    page.on('console', message => {
        if (message.type() === 'error') {
            consoleErrors.push(message.text());
        }
    });
    page.on('requestfailed', request => {
        const failure = request.failure();
        if (collectRuntimeFailures && failure?.errorText !== 'net::ERR_ABORTED') {
            requestFailures.push({url: safeUrl(request.url()), error: failure?.errorText || 'unknown'});
        }
    });

    try {
        await login(page, env);
        await page.goto(env.baseUrl + '/local/course_banner_builder/admin_slideshow.php', {waitUntil: 'networkidle'});
        // Moodle starts dashboard/background calls during login and navigation.
        // They do not belong to the Slideshow page; start the failure window only
        // after the target page has reached its network-idle state.
        consoleErrors.length = 0;
        requestFailures.length = 0;
        collectRuntimeFailures = true;
        await page.waitForTimeout(1000);
        const cards = page.locator('form.local-course-banner-builder-slideshow-card');
        await expect(cards).toHaveCount(2);
        await expect(cards.filter({has: page.locator('input[name="context"][value="course"]')})).toHaveCount(1);
        await expect(cards.filter({has: page.locator('input[name="context"][value="site"]')})).toHaveCount(1);
        const roots = page.locator('[data-slideshow-overlay-settings="1"]');
        await expect(roots).toHaveCount(2);
        await expect(page.locator('input[name="maxslides"]')).toHaveCount(2);
        await expect(page.locator('input[name="siteannouncementdays"]')).toHaveCount(2);
        await expect(page.locator('[data-slideshow-side-panel-target]')).toHaveCount(18);
        await expect(page.locator('[data-slideshow-side-panel]')).toHaveCount(18);

        const matrix = [];
        for (const viewport of matrixViewports) {
            await page.setViewportSize({width: viewport.width, height: viewport.height});
            await page.waitForTimeout(200);
            const view = {viewport: viewport.name, previews: []};
            const editButtons = page.locator('.local-course-banner-builder-slideshow-edit-appearance-button');
            await expect(editButtons).toHaveCount(2);
            for (let index = 0; index < await editButtons.count(); index++) {
                await editButtons.nth(index).click();
                const modal = page.locator('.local-course-banner-builder-slideshow-preview-modal.show');
                await expect(modal).toHaveCount(1);
                const preview = modal.locator('[data-slideshow-overlay-preview="1"][data-slideshow-preview-editor="1"]');
                await expect(preview).toHaveCount(1);
                const geometry = await previewGeometry(preview);
                await captureCdp(page, context,
                    path.join(env.artifactRoot, `slideshow-admin-${viewport.name}-${index === 0 ? 'course' : 'site'}.png`));
                expectRenderableGeometry(geometry);
                view.previews.push(geometry);
                await modal.locator('[data-bs-dismiss="modal"]').click();
                await expect(modal).toHaveCount(0);
            }
            matrix.push(view);
        }
        writeJson(path.join(env.artifactRoot, 'slideshow-rendering-matrix.json'), {
            cards: await cards.count(),
            consoleErrors,
            requestFailures,
            fixtureCourseId: Number(process.env.EASYEDU_CCB_SLIDESHOW_COURSE_ID),
            matrix,
            coverage: {
                contexts: ['course', 'site'],
                viewports: matrixViewports.map(viewport => viewport.name),
                previewElements: ['labels', 'title', 'body', 'action'],
                sidePanelsPerContext: 9,
            },
        });
        expect(consoleErrors).toEqual([]);
        expect(requestFailures).toEqual([]);
    } finally {
        collectRuntimeFailures = false;
        await context.close();
    }
});
