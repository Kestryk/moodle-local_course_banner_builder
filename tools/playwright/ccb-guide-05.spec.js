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

const readEnvironment = () => {
    const required = [
        'EASYEDU_MOODLE_URL', 'EASYEDU_MOODLE_USERNAME', 'EASYEDU_MOODLE_PASSWORD',
        'EASYEDU_CCB_GUIDE05_ADMIN_PATH', 'EASYEDU_CCB_GUIDE05_PROFILE',
        'EASYEDU_CCB_GUIDE05_ARTIFACT_ROOT',
    ];
    const missing = required.filter(name => !process.env[name]);
    ensure(!missing.length, 'Missing GUIDE-05 environment values: ' + missing.join(', '));
    const artifactRoot = path.resolve(process.env.EASYEDU_CCB_GUIDE05_ARTIFACT_ROOT);
    const profile = path.resolve(process.env.EASYEDU_CCB_GUIDE05_PROFILE);
    ensure(path.isAbsolute(artifactRoot) && path.isAbsolute(profile), 'GUIDE-05 paths must be absolute.');
    ensure(profile.toLowerCase().startsWith((artifactRoot + path.sep).toLowerCase()),
        'The GUIDE-05 Chromium profile must remain inside the external artifact root.');
    ensure(!artifactRoot.toLowerCase().includes(path.sep + 'local' + path.sep + 'course_banner_builder'),
        'GUIDE-05 artifacts must remain outside the CCB repository.');
    const adminPath = process.env.EASYEDU_CCB_GUIDE05_ADMIN_PATH;
    ensure(adminPath.startsWith('/') && !adminPath.includes('://'), 'The Guide admin route must be relative.');
    return {
        baseUrl: String(process.env.EASYEDU_MOODLE_URL).replace(/\/$/, ''),
        username: process.env.EASYEDU_MOODLE_USERNAME,
        password: process.env.EASYEDU_MOODLE_PASSWORD,
        adminPath,
        profile,
        artifactRoot,
    };
};

const captureCdp = async(page, context, file) => {
    const cdp = await context.newCDPSession(page);
    try {
        const screenshot = await cdp.send('Page.captureScreenshot', {
            format: 'png', fromSurface: true, captureBeyondViewport: false,
        });
        fs.mkdirSync(path.dirname(file), {recursive: true});
        fs.writeFileSync(file, Buffer.from(screenshot.data, 'base64'));
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

test('GUIDE-05 opens disclosures in order, highlights its target, and keeps return usable', async() => {
    const env = readEnvironment();
    const runRoot = path.join(env.artifactRoot, 'guide-05');
    const evidenceFile = path.join(runRoot, 'guide-05-evidence.json');
    const screenshotFile = path.join(runRoot, 'guide-05-highlight.png');
    const consoleErrors = [];
    const failedRequests = [];
    const context = await chromium.launchPersistentContext(env.profile, {
        headless: true,
        viewport: {width: 1440, height: 960},
        ignoreHTTPSErrors: true,
        args: ['--disable-gpu'],
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
            failedRequests.push({method: request.method(), resourceType: request.resourceType(),
                url: safeUrl(request.url()), failure});
        }
    });

    const evidence = {consoleErrors, failedRequests};
    try {
        await login(page, env);
        await page.goto(new URL(env.adminPath, env.baseUrl + '/').toString(), {waitUntil: 'domcontentloaded'});
        // The Guide root is deliberately portalled and may have no own box.
        // Interact with the visible launcher/modal controls, not the root.
        const modal = page.locator('[data-easyedu-guide-modal]').first();
        const launcher = page.locator('[data-easyedu-guide-open="1"]:visible').first();
        await expect(launcher).toBeVisible();
        await launcher.click();
        await expect(modal).toBeVisible();

        await page.evaluate(() => {
            document.querySelector('[data-guide05-surface]')?.remove();
            window.__ccbGuide05Events = [];
            const surface = document.createElement('section');
            surface.dataset.guide05Surface = '1';
            surface.innerHTML = '<button id="guide05-first" type="button" aria-expanded="false">First accordion</button>' +
                '<div id="guide05-first-panel" hidden>First content</div>' +
                '<button id="guide05-second" type="button" aria-expanded="false">Second accordion</button>' +
                '<div id="guide05-second-panel" hidden>Second content</div>' +
                '<div id="guide05-target" tabindex="-1">GUIDE-05 target</div>';
            const toggle = (buttonId, panelId) => {
                document.getElementById(buttonId).addEventListener('click', () => {
                    const button = document.getElementById(buttonId);
                    const panel = document.getElementById(panelId);
                    button.setAttribute('aria-expanded', 'true');
                    panel.hidden = false;
                    window.__ccbGuide05Events.push({id: buttonId, at: performance.now()});
                });
            };
            toggle('guide05-first', 'guide05-first-panel');
            toggle('guide05-second', 'guide05-second-panel');
            document.body.appendChild(surface);
        });

        const show = page.locator('[data-easyedu-guide-slide]:not([hidden]) [data-easyedu-guide-show-target]:visible').first();
        await expect(show).toBeVisible();
        await show.evaluate(button => {
            button.setAttribute('data-easyedu-guide-show-target', '#guide05-target');
            button.setAttribute('data-easyedu-guide-show-open', '#guide05-first');
            button.setAttribute('data-easyedu-guide-show-open-delay', '120');
            button.setAttribute('data-easyedu-guide-show-after-open', '#guide05-second');
            button.setAttribute('data-easyedu-guide-show-after-open-delay', '180');
        });
        await show.click();
        await expect(modal).toBeHidden();
        await page.waitForTimeout(70);
        expect(await page.evaluate(() => window.__ccbGuide05Events)).toEqual([]);
        await expect(page.locator('#guide05-first')).toHaveAttribute('aria-expanded', 'true');
        await page.waitForTimeout(80);
        await expect(page.locator('#guide05-second')).toHaveAttribute('aria-expanded', 'false');
        await expect(page.locator('#guide05-second')).toHaveAttribute('aria-expanded', 'true');
        await expect(page.locator('#guide05-target')).toHaveClass(/is-easyedu-guide-highlight-target/);

        evidence.events = await page.evaluate(() => window.__ccbGuide05Events);
        expect(evidence.events).toHaveLength(2);
        expect(evidence.events[1].at - evidence.events[0].at).toBeGreaterThanOrEqual(150);
        evidence.highlight = await page.locator('[data-easyedu-guide-highlight]').evaluate(node => ({
            hidden: node.hidden,
            width: node.style.width,
            height: node.style.height,
        }));
        expect(evidence.highlight.hidden).toBe(false);
        await page.locator('#guide05-target').scrollIntoViewIfNeeded();
        await captureCdp(page, context, screenshotFile);

        const returnButton = page.locator('[data-easyedu-guide-interface-return-button]:visible').first();
        await expect(returnButton).toBeVisible();
        await returnButton.click();
        await expect(modal).toBeVisible();
        evidence.returnUsable = true;
        evidence.consoleErrors = consoleErrors;
        evidence.failedRequests = failedRequests;
        writeJson(evidenceFile, evidence);
        expect(consoleErrors).toEqual([]);
        expect(failedRequests).toEqual([]);
    } catch (error) {
        evidence.error = String(error && error.stack || error);
        evidence.consoleErrors = consoleErrors;
        evidence.failedRequests = failedRequests;
        writeJson(evidenceFile, evidence);
        throw error;
    } finally {
        await context.close();
    }
});
