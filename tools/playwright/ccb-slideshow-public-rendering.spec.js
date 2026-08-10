const {test, expect, chromium} = require('@playwright/test');
const {execFileSync, spawn} = require('child_process');
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

const wait = milliseconds => new Promise(resolve => setTimeout(resolve, milliseconds));

const connectOverCdp = async(port) => {
    const endpoint = 'http://127.0.0.1:' + port;
    let lastError = null;
    for (let attempt = 0; attempt < 40; attempt++) {
        try {
            return await chromium.connectOverCDP(endpoint);
        } catch (error) {
            lastError = error;
            await wait(250);
        }
    }
    throw lastError || new Error('Dedicated Chrome did not expose CDP.');
};

const stopOwnedChromeProcessTree = process => {
    if (!process || !process.pid) {
        return;
    }
    try {
        execFileSync('taskkill.exe', ['/PID', String(process.pid), '/T', '/F'], {
            stdio: 'ignore', windowsHide: true,
        });
    } catch (error) {
        // Chrome may already have exited after the CDP connection closed.
    }
};

const nativeZoom = (pid, operation) => {
    const count = operation === 'reset' ? 1 : 5;
    const command = `$ErrorActionPreference='Stop'; $root=${Number(pid)}; $deadline=(Get-Date).AddSeconds(10); $window=$null; ` +
        `do {$ids=[Collections.Generic.HashSet[int]]::new(); [void]$ids.Add($root); $processes=Get-CimInstance Win32_Process; ` +
        `do {$countBefore=$ids.Count; foreach($process in $processes){if($ids.Contains([int]$process.ParentProcessId)){[void]$ids.Add([int]$process.ProcessId)}}}while($ids.Count -gt $countBefore); ` +
        `$window=$ids | ForEach-Object {Get-Process -Id $_ -ErrorAction SilentlyContinue} | Where-Object {$_.MainWindowHandle -ne 0} | Select-Object -First 1; ` +
        `if(-not $window){Start-Sleep -Milliseconds 250}}while(-not $window -and (Get-Date) -lt $deadline); ` +
        `if(-not $window){throw 'Dedicated Chrome has no visible window.'}; ` +
        `Add-Type -TypeDefinition 'using System; using System.Runtime.InteropServices; namespace EasyEdu { public static class NativeInput { [DllImport("user32.dll")] public static extern bool SetForegroundWindow(IntPtr hWnd); [DllImport("user32.dll")] public static extern void keybd_event(byte bVk, byte bScan, uint dwFlags, UIntPtr dwExtraInfo); } }'; ` +
        `if(-not [EasyEdu.NativeInput]::SetForegroundWindow($window.MainWindowHandle)){throw 'Dedicated Chrome activation failed.'}; ` +
        `function Send-Key([byte]$key){[EasyEdu.NativeInput]::keybd_event($key,0,0,[UIntPtr]::Zero); Start-Sleep -Milliseconds 40; [EasyEdu.NativeInput]::keybd_event($key,0,2,[UIntPtr]::Zero)}; ` +
        `Start-Sleep -Milliseconds 350; 1..${count} | ForEach-Object {[EasyEdu.NativeInput]::keybd_event(0x11,0,0,[UIntPtr]::Zero); Send-Key ($(if('${operation}' -eq 'reset'){0x30}else{0xBB})); [EasyEdu.NativeInput]::keybd_event(0x11,0,2,[UIntPtr]::Zero); Start-Sleep -Milliseconds 200}; ` +
        `Start-Sleep -Milliseconds 700; Write-Output ($window.Id.ToString()+'|'+$window.MainWindowHandle.ToString())`;
    return execFileSync('powershell.exe', ['-NoProfile', '-NonInteractive', '-Command', command], {
        encoding: 'utf8', windowsHide: true,
    }).trim();
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
    const zoom = Number(process.env.EASYEDU_CCB_SLIDESHOW_PUBLIC_ZOOM || '100');
    ensure([100, 200].includes(zoom), 'Public Slideshow zoom must be 100 or 200.');
    if (zoom === 200) {
        required.push(
            'EASYEDU_CCB_SLIDESHOW_PUBLIC_CHROME_EXECUTABLE',
            'EASYEDU_CCB_SLIDESHOW_PUBLIC_CDP_PORT'
        );
    }
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
    const parityConfigPath = process.env.EASYEDU_CCB_SLIDESHOW_PUBLIC_PARITY_CONFIG || '';
    let parityConfig = null;
    if (parityConfigPath) {
        const resolvedParityConfigPath = path.resolve(parityConfigPath);
        ensure(resolvedParityConfigPath.toLowerCase().startsWith((artifactRoot + path.sep).toLowerCase()),
            'Parity configuration proof must be owned by the external artifact root.');
        parityConfig = JSON.parse(fs.readFileSync(resolvedParityConfigPath, 'utf8'));
        ensure(parityConfig.savedCourseConfig?.context === 'course',
            'Parity fixture did not preserve a Course Slideshow configuration.');
        ensure(parityConfig.forcedRuntimeValues?.forums === 1,
            'Parity fixture did not enable its real Forum source.');
    }
    return {
        announcementTitle: process.env.EASYEDU_CCB_SLIDESHOW_PUBLIC_ANNOUNCEMENT_TITLE,
        artifactRoot,
        baseUrl: String(process.env.EASYEDU_MOODLE_URL).replace(/\/$/, ''),
        courseId: Number(process.env.EASYEDU_CCB_SLIDESHOW_PUBLIC_COURSE_ID),
        cdpPort: Number(process.env.EASYEDU_CCB_SLIDESHOW_PUBLIC_CDP_PORT || '0'),
        chromeExecutable: process.env.EASYEDU_CCB_SLIDESHOW_PUBLIC_CHROME_EXECUTABLE || '',
        discussionId: Number(process.env.EASYEDU_CCB_SLIDESHOW_PUBLIC_DISCUSSION_ID),
        password: process.env.EASYEDU_MOODLE_PASSWORD,
        profile,
        username: process.env.EASYEDU_MOODLE_USERNAME,
        zoom,
        parityConfig,
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

const waitForSettledModal = async(modal) => {
    await expect(modal).toBeVisible();
    await expect.poll(async() => modal.evaluate(node => {
        const dialog = node.querySelector('.modal-dialog');
        const content = node.querySelector('.modal-content');
        const animations = node.getAnimations({subtree: true});
        return !!dialog && !!content && node.classList.contains('show') &&
            dialog.getBoundingClientRect().width > 0 &&
            window.getComputedStyle(content).opacity === '1' &&
            !animations.some(animation => animation.playState === 'pending' || animation.playState === 'running');
    }), {timeout: 5000}).toBeTruthy();
};

const previewEvidence = async(preview) => preview.evaluate(node => {
    const bounds = node.getBoundingClientRect();
    const sample = (name, selector) => {
        const element = node.querySelector(selector);
        if (!element) {
            return {name, present: false};
        }
        const rect = element.getBoundingClientRect();
        const style = window.getComputedStyle(element);
        return {
            name,
            present: true,
            left: rect.left - bounds.left,
            top: rect.top - bounds.top,
            width: rect.width,
            height: rect.height,
            color: style.color,
            fontSize: style.fontSize,
            fontWeight: style.fontWeight,
            textAlign: style.textAlign,
            textTransform: style.textTransform,
        };
    };
    return {
        width: bounds.width,
        height: bounds.height,
        styleVariables: Array.from(node.style)
            .filter(name => name.startsWith('--local-course-banner-builder-slideshow-'))
            .reduce((values, name) => ({...values, [name]: node.style.getPropertyValue(name)}), {}),
        samples: [
            sample('label', '.local-course-banner-builder-slideshow-label--forums'),
            sample('title', '.local-course-banner-builder-slideshow-title'),
            sample('body', '.local-course-banner-builder-slideshow-body'),
            sample('action', '.local-course-banner-builder-slideshow-action'),
        ],
    };
});

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
        documentClientWidth: document.documentElement.clientWidth,
        documentScrollWidth: document.documentElement.scrollWidth,
        hostHeight: hostBounds.height,
        hostWidth: hostBounds.width,
        samples,
        viewportScale: window.visualViewport ? window.visualViewport.scale : 1,
    };
});

const expectPublicGeometry = geometry => {
    expect(geometry.hostWidth, 'public Slideshow host is too narrow').toBeGreaterThan(240);
    expect(geometry.hostHeight, 'public Slideshow host has no height').toBeGreaterThan(40);
    expect(geometry.documentScrollWidth, 'public Slideshow causes horizontal page overflow').toBeLessThanOrEqual(
        geometry.documentClientWidth + 1
    );
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

test('CCB Slideshow public Course fixture renders a real forum announcement at the requested browser zoom', async() => {
    const env = environment();
    const consoleErrors = [];
    const requestFailures = [];
    let collectRuntimeFailures = false;
    let browser = null;
    let chromeProcess = null;
    let context = null;
    let page = null;
    let parityEvidence = null;
    const zoomCleanup = {
        requested: env.zoom,
        zoomAttempted: false,
        zoomApplied: false,
        zoomRestored: env.zoom === 100,
    };
    if (env.zoom === 200) {
        chromeProcess = spawn(env.chromeExecutable, [
            `--remote-debugging-port=${env.cdpPort}`,
            '--remote-debugging-address=127.0.0.1',
            `--user-data-dir=${env.profile}`,
            '--no-first-run',
            '--no-default-browser-check',
            '--disable-save-password-bubble',
            '--new-window',
            '--window-size=1600,900',
            `${env.baseUrl}/login/index.php`,
        ], {stdio: 'ignore', windowsHide: false});
        try {
            browser = await connectOverCdp(env.cdpPort);
            context = browser.contexts()[0];
            ensure(context, 'Dedicated Chrome did not expose a browser context.');
            page = context.pages()[0] || await context.newPage();
        } catch (error) {
            stopOwnedChromeProcessTree(chromeProcess);
            throw error;
        }
    } else {
        context = await chromium.launchPersistentContext(env.profile, {headless: true});
        page = await context.newPage();
    }
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
        if (env.zoom === 100) {
            await page.setViewportSize({width: 1600, height: 900});
        }
        await login(page, env);
        if (env.parityConfig) {
            await page.goto(env.baseUrl + '/local/course_banner_builder/admin_slideshow.php', {waitUntil: 'networkidle'});
            const courseCard = page.locator('form.local-course-banner-builder-slideshow-card').filter({
                has: page.locator('input[name="context"][value="course"]'),
            });
            await expect(courseCard).toHaveCount(1);
            await courseCard.locator('.local-course-banner-builder-slideshow-edit-appearance-button').click();
            const modal = page.locator('.local-course-banner-builder-slideshow-preview-modal.show');
            await expect(modal).toHaveCount(1);
            await waitForSettledModal(modal);
            const preview = modal.locator('[data-slideshow-overlay-preview="1"][data-slideshow-preview-editor="1"]');
            await expect(preview).toHaveCount(1);
            parityEvidence = {
                savedCourseConfig: env.parityConfig.savedCourseConfig,
                forcedRuntimeValues: env.parityConfig.forcedRuntimeValues,
                adminPreview: await previewEvidence(preview),
            };
            await captureCdp(page, context,
                path.join(env.artifactRoot, 'slideshow-admin-course-parity-' + env.zoom + '.png'));
            await modal.locator('[data-bs-dismiss="modal"]').click();
            await expect(modal).toHaveCount(0);
        }
        await page.goto(env.baseUrl + '/course/view.php?id=' + env.courseId, {waitUntil: 'networkidle'});
        consoleErrors.length = 0;
        requestFailures.length = 0;
        collectRuntimeFailures = true;

        const root = page.locator('[data-course-banner-builder-slideshow="1"]');
        await expect(root).toHaveCount(1);
        await expect(root).toHaveAttribute('role', 'region');
        await expect(root).toHaveAttribute('aria-label', /\S+/);
        await waitForSettledSlide(root, 0);

        let zoomEvidence = null;
        if (env.zoom === 200) {
            const before = await page.evaluate(() => ({innerWidth, innerHeight, devicePixelRatio}));
            zoomEvidence = nativeZoom(chromeProcess.pid, 'zoom');
            zoomCleanup.zoomAttempted = true;
            let after = before;
            let achieved = false;
            for (let attempt = 0; attempt < 20 && !achieved; attempt++) {
                await wait(250);
                after = await page.evaluate(() => ({innerWidth, innerHeight, devicePixelRatio}));
                const devicePixelRatioRatio = after.devicePixelRatio / Math.max(before.devicePixelRatio, 1);
                const widthRatio = before.innerWidth / Math.max(after.innerWidth, 1);
                achieved = devicePixelRatioRatio >= 1.9 && devicePixelRatioRatio <= 2.1 &&
                    widthRatio >= 1.9 && widthRatio <= 2.1;
            }
            zoomEvidence = {
                before,
                after,
                chromeWindow: zoomEvidence,
                devicePixelRatioRatio: after.devicePixelRatio / Math.max(before.devicePixelRatio, 1),
                widthRatio: before.innerWidth / Math.max(after.innerWidth, 1),
            };
            writeJson(path.join(env.artifactRoot, 'slideshow-public-course-forum-200-zoom-probe.json'), {
                achieved,
                zoomEvidence,
            });
            zoomCleanup.zoomApplied = achieved;
            expect(achieved, 'Chrome did not apply a genuine 200 percent browser zoom.').toBeTruthy();
        }

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
        const evidenceBase = 'slideshow-public-course-forum-' + env.zoom;
        await captureCdp(page, context, path.join(env.artifactRoot, evidenceBase + '.png'));
        writeJson(path.join(env.artifactRoot, evidenceBase + '.json'), {
            announcementTitle: env.announcementTitle,
            consoleErrors,
            courseId: env.courseId,
            discussionId: env.discussionId,
            geometry,
            requestFailures,
            scope: {
                source: 'real-course-forum-announcement',
                requestedBrowserZoom: env.zoom,
                parityMode: !!env.parityConfig,
            },
            parity: parityEvidence,
            zoomEvidence,
        });
        expect(consoleErrors).toEqual([]);
        expect(requestFailures).toEqual([]);
    } finally {
        collectRuntimeFailures = false;
        if (env.zoom === 200 && chromeProcess && zoomCleanup.zoomAttempted) {
            try {
                zoomCleanup.chromeWindow = nativeZoom(chromeProcess.pid, 'reset');
                zoomCleanup.zoomRestored = true;
            } catch (error) {
                zoomCleanup.error = String(error && error.stack || error);
            }
        } else if (env.zoom === 200) {
            zoomCleanup.zoomRestored = true;
            zoomCleanup.resetNotRequired = true;
        }
        if (browser) {
            await browser.close().catch(() => {});
        } else if (context) {
            await context.close().catch(() => {});
        }
        stopOwnedChromeProcessTree(chromeProcess);
        try {
            fs.rmSync(env.profile, {recursive: true, force: true, maxRetries: 40, retryDelay: 250});
        } catch (error) {
            zoomCleanup.error = zoomCleanup.error || String(error && error.stack || error);
        }
        zoomCleanup.profileRemoved = !fs.existsSync(env.profile);
        writeJson(path.join(env.artifactRoot, 'slideshow-public-course-forum-' + env.zoom + '-browser-cleanup.json'), zoomCleanup);
        if (env.zoom === 200) {
            ensure(zoomCleanup.zoomRestored, 'Dedicated Chrome browser zoom was not restored.');
        }
    }
});
