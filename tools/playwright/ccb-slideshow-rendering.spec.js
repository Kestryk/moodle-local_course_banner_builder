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
        {
            name: 'labels',
            selector: '.local-course-banner-builder-slideshow-labels',
            textSelector: '.local-course-banner-builder-slideshow-label',
        },
        {
            name: 'title',
            selector: '.local-course-banner-builder-slideshow-title-block',
            textSelector: '.local-course-banner-builder-slideshow-title',
        },
        {
            name: 'body',
            selector: '.local-course-banner-builder-slideshow-body-block',
            textSelector: '.local-course-banner-builder-slideshow-body',
        },
        {
            name: 'action',
            selector: '.local-course-banner-builder-slideshow-action-wrap',
            textSelector: '.local-course-banner-builder-slideshow-action',
        },
    ];
    const samples = elements.map(({name, selector, textSelector}) => {
        const child = element.querySelector(selector);
        if (!child) {
            return {name, selector, present: false};
        }
        const rect = child.getBoundingClientRect();
        const text = child.querySelector(textSelector);
        const textStyle = text ? window.getComputedStyle(text) : null;
        return {
            name,
            selector,
            present: true,
            width: rect.width,
            height: rect.height,
            left: rect.left - bounds.left,
            right: rect.right - bounds.left,
            top: rect.top - bounds.top,
            bottom: rect.bottom - bounds.top,
            fontSize: textStyle ? Number.parseFloat(textStyle.fontSize) : 0,
            computedSize: name === 'action' && textStyle ? {
                height: textStyle.height,
                minHeight: textStyle.minHeight,
                minWidth: textStyle.minWidth,
                width: textStyle.width,
            } : null,
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

const expectMobileReadability = (geometry) => {
    expect(geometry.height, 'mobile preview is too shallow').toBeGreaterThanOrEqual(200);
    const thresholds = {labels: 12, title: 16, body: 13, action: 13};
    for (const [name, minimum] of Object.entries(thresholds)) {
        const sample = geometry.samples.find(item => item.name === name);
        expect(sample, name + ' sample is missing').toBeDefined();
        expect(sample.fontSize, name + ' text is too small at mobile').toBeGreaterThanOrEqual(minimum);
    }
    const labels = geometry.samples.find(item => item.name === 'labels');
    expect(labels.left, 'labels escape the mobile preview on the left').toBeGreaterThanOrEqual(0);
    expect(labels.top, 'labels escape the mobile preview at the top').toBeGreaterThanOrEqual(0);
    expect(labels.right, 'labels escape the mobile preview on the right').toBeLessThanOrEqual(geometry.width);
    expect(labels.bottom, 'labels escape the mobile preview at the bottom').toBeLessThanOrEqual(geometry.height);
};

const expectCompactActionReadability = (geometry, viewportName) => {
    const action = geometry.samples.find(item => item.name === 'action');
    expect(action, viewportName + ' action sample is missing').toBeDefined();
    const computedSize = JSON.stringify(action.computedSize);
    expect(action.width, viewportName + ' action is too narrow to operate: ' + computedSize).toBeGreaterThanOrEqual(96);
    expect(action.height, viewportName + ' action is too short to operate: ' + computedSize).toBeGreaterThanOrEqual(36);
    expect(action.fontSize, viewportName + ' action text is too small').toBeGreaterThanOrEqual(13);
};

const waitForSettledModal = async(page, modal) => {
    await expect(modal).toBeVisible();
    await expect.poll(async() => modal.evaluate((node) => {
        const dialog = node.querySelector('.modal-dialog');
        const content = node.querySelector('.modal-content');
        if (!dialog || !content || !node.classList.contains('show')) {
            return false;
        }
        const dialogRect = dialog.getBoundingClientRect();
        const contentStyle = window.getComputedStyle(content);
        const activeAnimations = node.getAnimations({subtree: true}).some((animation) =>
            animation.playState === 'pending' || animation.playState === 'running');
        return dialogRect.width > 0 && dialogRect.height > 0 &&
            contentStyle.opacity === '1' && !activeAnimations;
    }), {timeout: 3000}).toBeTruthy();
    await page.evaluate(() => document.fonts.ready);
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
                await waitForSettledModal(page, modal);
                const preview = modal.locator('[data-slideshow-overlay-preview="1"][data-slideshow-preview-editor="1"]');
                await expect(preview).toHaveCount(1);
                const geometry = await previewGeometry(preview);
                await captureCdp(page, context,
                    path.join(env.artifactRoot, `slideshow-admin-${viewport.name}-${index === 0 ? 'course' : 'site'}.png`));
                expectRenderableGeometry(geometry);
                if (viewport.name === 'tablet' || viewport.name === 'mobile') {
                    expectCompactActionReadability(geometry, viewport.name);
                }
                if (viewport.name === 'mobile') {
                    expectMobileReadability(geometry);
                }
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
