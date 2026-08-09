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

const safeUrl = value => String(value).replace(
    /([?&](?:sesskey|token|password|session(?:id)?|auth)=[^&\s]+)/gi,
    '$1[redacted]'
);

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

const environment = () => {
    const required = [
        'EASYEDU_MOODLE_URL',
        'EASYEDU_MOODLE_USERNAME',
        'EASYEDU_MOODLE_PASSWORD',
        'EASYEDU_CCB_SLIDESHOW_PUBLIC_COURSE_ID',
        'EASYEDU_CCB_SLIDESHOW_PUBLIC_DISCUSSION_ID',
        'EASYEDU_CCB_SLIDESHOW_PUBLIC_ANNOUNCEMENT_TITLE',
        'EASYEDU_CCB_SLIDESHOW_PUBLIC_PROFILE',
        'EASYEDU_CCB_SLIDESHOW_PUBLIC_ARTIFACT_ROOT',
    ];
    const missing = required.filter(name => !process.env[name]);
    ensure(missing.length === 0, 'Missing public Slideshow validation values: ' + missing.join(', '));
    const artifactRoot = path.resolve(process.env.EASYEDU_CCB_SLIDESHOW_PUBLIC_ARTIFACT_ROOT);
    const profile = path.resolve(process.env.EASYEDU_CCB_SLIDESHOW_PUBLIC_PROFILE);
    ensure(path.isAbsolute(artifactRoot) && path.isAbsolute(profile),
        'Public Slideshow artifact paths must be absolute.');
    ensure(profile.toLowerCase().startsWith((artifactRoot + path.sep).toLowerCase()),
        'Public Slideshow profile must be owned by the external artifact root.');
    for (const name of [
        'EASYEDU_CCB_SLIDESHOW_PUBLIC_COURSE_ID',
        'EASYEDU_CCB_SLIDESHOW_PUBLIC_DISCUSSION_ID',
    ]) {
        ensure(/^\d+$/.test(process.env[name]), name + ' must be numeric.');
    }
    return {
        announcementTitle: process.env.EASYEDU_CCB_SLIDESHOW_PUBLIC_ANNOUNCEMENT_TITLE,
        artifactRoot,
        baseUrl: String(process.env.EASYEDU_MOODLE_URL).replace(/\/$/, ''),
        courseId: Number(process.env.EASYEDU_CCB_SLIDESHOW_PUBLIC_COURSE_ID),
        discussionId: Number(process.env.EASYEDU_CCB_SLIDESHOW_PUBLIC_DISCUSSION_ID),
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

const waitForSettledSlide = async(root, index) => {
    await expect.poll(async() => root.evaluate((node, expectedIndex) => {
        const active = node.querySelector('[data-slideshow-slide].is-active');
        const animations = node.getAnimations({subtree: true});
        const hasActiveAnimation = animations.some(animation =>
            animation.playState === 'pending' || animation.playState === 'running');
        return !!active && active.getAttribute('data-slideshow-slide') === String(expectedIndex) &&
            active.getAttribute('aria-hidden') === 'false' && !hasActiveAnimation;
    }, index), {timeout: 5000}).toBeTruthy();
};

const publicGeometry = async(host) => host.evaluate(hostNode => {
    const slideNode = hostNode.querySelector('[data-slideshow-slide="1"].is-active');
    if (!slideNode) {
        return {hostHeight: 0, hostWidth: 0, samples: [], viewportScale: 0};
    }
    const hostBounds = hostNode.getBoundingClientRect();
    const samples = [
        ['label', '.local-course-banner-builder-slideshow-label--forums'],
        ['title', '.local-course-banner-builder-slideshow-title'],
        ['action', '.local-course-banner-builder-slideshow-action'],
    ].map(([name, selector]) => {
        const element = slideNode.querySelector(selector);
        if (!element) {
            return {name, present: false};
        }
        const bounds = element.getBoundingClientRect();
        return {
            name,
            present: true,
            left: bounds.left - hostBounds.left,
            right: bounds.right - hostBounds.left,
            top: bounds.top - hostBounds.top,
            bottom: bounds.bottom - hostBounds.top,
            width: bounds.width,
            height: bounds.height,
        };
    });
    return {
        hostHeight: hostBounds.height,
        hostWidth: hostBounds.width,
        samples,
        viewportScale: window.visualViewport ? window.visualViewport.scale : 1,
    };
});

const expectPublicGeometry = geometry => {
    expect(geometry.hostWidth, 'public Slideshow host is too narrow').toBeGreaterThan(240);
    expect(geometry.hostHeight, 'public Slideshow host has no height').toBeGreaterThan(40);
    expect(geometry.viewportScale, 'public scenario must remain at the default 100 percent zoom').toBe(1);
    for (const sample of geometry.samples) {
        expect(sample.present, sample.name + ' is missing from the real forum slide').toBeTruthy();
        expect(sample.width, sample.name + ' has no visible width').toBeGreaterThan(0);
        expect(sample.height, sample.name + ' has no visible height').toBeGreaterThan(0);
        expect(sample.left, sample.name + ' escapes the banner on the left').toBeGreaterThanOrEqual(-1);
        expect(sample.top, sample.name + ' escapes the banner at the top').toBeGreaterThanOrEqual(-1);
        expect(sample.right, sample.name + ' escapes the banner on the right').toBeLessThanOrEqual(geometry.hostWidth + 1);
        expect(sample.bottom, sample.name + ' escapes the banner at the bottom').toBeLessThanOrEqual(geometry.hostHeight + 1);
    }
};

test('CCB Slideshow public Course fixture renders a real forum announcement at 100 percent', async() => {
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
        await page.setViewportSize({width: 1600, height: 900});
        await login(page, env);
        await page.goto(env.baseUrl + '/course/view.php?id=' + env.courseId, {waitUntil: 'networkidle'});
        consoleErrors.length = 0;
        requestFailures.length = 0;
        collectRuntimeFailures = true;

        const root = page.locator('[data-course-banner-builder-slideshow="1"]');
        await expect(root).toHaveCount(1);
        await expect(root).toHaveAttribute('role', 'region');
        await expect(root).toHaveAttribute('aria-label', /\S+/);
        await waitForSettledSlide(root, 0);

        const next = root.locator('.local-course-banner-builder-slideshow-nav--next');
        await expect(next).toHaveCount(1);
        await next.click();
        await waitForSettledSlide(root, 1);

        const activeSlide = root.locator('[data-slideshow-slide="1"].is-active');
        await expect(activeSlide).toHaveCount(1);
        await expect(activeSlide).toHaveAttribute('aria-hidden', 'false');
        await expect(activeSlide.locator('.local-course-banner-builder-slideshow-label--forums')).toHaveCount(1);
        await expect(activeSlide.locator('.local-course-banner-builder-slideshow-title')).toHaveText(env.announcementTitle);
        const action = activeSlide.locator('.local-course-banner-builder-slideshow-action');
        await expect(action).toHaveCount(1);
        await expect(action).toHaveAttribute(
            'href',
            new RegExp('(?:^|/)mod/forum/discuss\\.php\\?d=' + env.discussionId + '(?:#|$)')
        );

        const host = root.locator('xpath=..');
        const geometry = await publicGeometry(host);
        expectPublicGeometry(geometry);
        await page.evaluate(() => document.fonts.ready);
        await captureCdp(page, context, path.join(env.artifactRoot, 'slideshow-public-course-forum-100.png'));
        writeJson(path.join(env.artifactRoot, 'slideshow-public-course-forum-100.json'), {
            announcementTitle: env.announcementTitle,
            consoleErrors,
            courseId: env.courseId,
            discussionId: env.discussionId,
            geometry,
            requestFailures,
            scope: {
                source: 'real-course-forum-announcement',
                viewport: {width: 1600, height: 900},
                zoom: 100,
            },
        });
        expect(consoleErrors).toEqual([]);
        expect(requestFailures).toEqual([]);
    } finally {
        collectRuntimeFailures = false;
        await context.close();
    }
});
