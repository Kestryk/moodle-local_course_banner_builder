const {test, expect, chromium} = require('@playwright/test');
const fs = require('fs');
const path = require('path');
const {spawn, execFileSync} = require('child_process');

const POLICY = {
    standard: {ratio: 4, min: 0, max: null},
    contentwide: {ratio: 5, min: 128, max: 280},
    fullwidthtop: {ratio: 5, min: 128, max: 360},
    fullwidthtopcompact: {ratio: 8, min: 128, max: 210},
    fullwidthtopinset: {ratio: 6.1, min: 128, max: 300},
};
const DEFAULT_FORMATS = Object.keys(POLICY);
const DEFAULT_VIEWPORTS = {
    desktop: {width: 1600, height: 900},
    laptop: {width: 1024, height: 768},
    narrow: {width: 768, height: 1024},
    mobile: {width: 390, height: 844},
};
const OWNERSHIP_MARKER = 'local_course_banner_builder:batch-2e-b:admin-mobile-preview';

const ensure = (condition, message) => {
    if (!condition) {
        throw new Error(message);
    }
};

const environment = () => {
    const required = ['EASYEDU_MOODLE_URL', 'EASYEDU_MOODLE_USERNAME', 'EASYEDU_MOODLE_PASSWORD',
        'EASYEDU_CCB_2EB_ARTIFACT_ROOT', 'EASYEDU_CCB_2EB_SCENARIO_ID'];
    const missing = required.filter(name => !process.env[name]);
    ensure(!missing.length, `Missing Batch 2E-B environment values: ${missing.join(', ')}.`);

    const formats = String(process.env.EASYEDU_CCB_2EB_FORMATS || DEFAULT_FORMATS.join(','))
        .split(',').map(value => value.trim()).filter(Boolean);
    const viewports = String(process.env.EASYEDU_CCB_2EB_VIEWPORTS || Object.keys(DEFAULT_VIEWPORTS).join(','))
        .split(',').map(value => value.trim()).filter(Boolean);
    formats.forEach(format => ensure(Object.hasOwn(POLICY, format), `Unsupported CCB format: ${format}.`));
    viewports.forEach(viewport => ensure(Object.hasOwn(DEFAULT_VIEWPORTS, viewport), `Unsupported viewport: ${viewport}.`));
    const scenarioId = process.env.EASYEDU_CCB_2EB_SCENARIO_ID;
    ensure(/^[a-z0-9][a-z0-9-]{2,80}$/.test(scenarioId), 'EASYEDU_CCB_2EB_SCENARIO_ID must be a lowercase slug.');
    const artifactRoot = path.resolve(process.env.EASYEDU_CCB_2EB_ARTIFACT_ROOT);
    ensure(artifactRoot.toLowerCase().startsWith('d:\\easyedu\\artifacts\\'),
        'EASYEDU_CCB_2EB_ARTIFACT_ROOT must stay under D:\\EasyEdu\\artifacts.');
    const zoom = Number(process.env.EASYEDU_CCB_2EB_ZOOM || 100);
    ensure([100, 200].includes(zoom), 'EASYEDU_CCB_2EB_ZOOM must be 100 or 200.');
    return {
        baseUrl: process.env.EASYEDU_MOODLE_URL.replace(/\/$/, ''),
        username: process.env.EASYEDU_MOODLE_USERNAME,
        password: process.env.EASYEDU_MOODLE_PASSWORD,
        sourceUrl: process.env.EASYEDU_CCB_2EB_SOURCE_URL || '',
        scenarioId,
        artifactRoot,
        formats,
        viewports,
        zoom,
        retainPassingScreenshots: process.env.EASYEDU_CCB_2EB_RETAIN_PASSING_SCREENSHOTS === '1',
        port: Number(process.env.EASYEDU_CCB_2EB_PORT || (9450 + (process.pid % 200))),
    };
};

const writeJson = (file, value) => {
    fs.mkdirSync(path.dirname(file), {recursive: true});
    fs.writeFileSync(file, JSON.stringify(value, null, 2));
};

const directoryBytes = directory => {
    if (!fs.existsSync(directory)) {
        return 0;
    }
    return fs.readdirSync(directory, {withFileTypes: true}).reduce((total, entry) => {
        const target = path.join(directory, entry.name);
        return total + (entry.isDirectory() ? directoryBytes(target) : fs.statSync(target).size);
    }, 0);
};

const repositoryState = () => {
    const pluginRoot = path.resolve(__dirname, '../../../course_banner_builder');
    const run = args => execFileSync('git', args, {cwd: pluginRoot, encoding: 'utf8'}).trim();
    return {pluginRoot, branch: run(['branch', '--show-current']), head: run(['rev-parse', 'HEAD'])};
};

const assertPublicRuntimePolicy = pluginRoot => {
    const runtime = fs.readFileSync(path.join(pluginRoot, 'classes', 'hook_callbacks.php'), 'utf8');
    const nativeCore = fs.readFileSync(path.join(pluginRoot, 'scss', 'components', '_native-banner-core.scss'), 'utf8');
    const requiredRuntime = [
        'protected static function get_course_banner_runtime_css(): string',
        'min-height: 128px;',
        'max-height: 280px;',
        'max-height: 360px;',
        'max-height: 210px;',
        'max-height: 300px;',
    ];
    const requiredNativeCore = [
        '.local-course-banner-builder-native-course-banner--format-contentwide,',
        'min-height: 128px;',
        'max-height: 280px;',
        'max-height: 360px;',
        'max-height: 210px;',
        'max-height: 300px;',
    ];
    requiredRuntime.forEach(value => ensure(runtime.includes(value), `Public runtime CSS policy is missing ${value}.`));
    requiredNativeCore.forEach(value => ensure(nativeCore.includes(value), `Native banner core policy is missing ${value}.`));
    return {runtimeChecked: requiredRuntime, nativeCoreChecked: requiredNativeCore};
};

const login = async(page, env) => {
    await page.goto(`${env.baseUrl}/login/index.php`, {waitUntil: 'domcontentloaded'});
    await page.locator('#username').fill(env.username);
    await page.locator('#password').fill(env.password);
    await page.locator('#loginbtn').click();
    await expect(page).not.toHaveURL(/\/login\//, {timeout: 15000});
};

const adminUrl = env => `${env.baseUrl}/local/course_banner_builder/admin_manage.php`;

const resolveSourceEditor = async(page, env) => {
    if (env.sourceUrl) {
        await page.goto(new URL(env.sourceUrl, env.baseUrl).href, {waitUntil: 'domcontentloaded'});
    } else {
        await page.goto(adminUrl(env), {waitUntil: 'domcontentloaded'});
    }
    if (await page.locator('[data-source-visual-editor="1"]').count()) {
        return page.url();
    }
    const source = page.locator(
        '.local-course-banner-builder-configured-sources-table tbody a[href*="/local/course_banner_builder/admin_manage.php"]'
    ).first();
    await expect(source).toHaveCount(1, {timeout: 15000});
    const href = await source.getAttribute('href');
    ensure(href, 'Configured CCB source link has no href.');
    await page.goto(new URL(href, env.baseUrl).href, {waitUntil: 'domcontentloaded'});
    await expect(page.locator('[data-source-visual-editor="1"]')).toHaveCount(1, {timeout: 15000});
    return page.url();
};

const getEditor = page => page.locator('[data-source-visual-editor="1"]').first();

const verifySourceChainPreviewMode = async page => {
    const button = page.locator(
        '[data-action="local-course-banner-builder-show-source-chain-preview"][data-preview-url]:visible'
    ).first();
    await expect(button).toHaveCount(1, {timeout: 15000});
    await button.click();
    const modal = page.locator('#local-course-banner-builder-source-chain-preview-modal');
    await expect(modal).toBeVisible({timeout: 15000});
    const root = modal.locator('[data-source-visual-editor="1"]');
    await expect(root).toHaveCount(1, {timeout: 15000});
    await expect(root.locator('form')).toHaveCount(0);
    const mobile = root.locator('[data-source-preview-mode-value="mobile"]');
    const desktop = root.locator('[data-source-preview-mode-value="desktop"]');
    await mobile.focus();
    await mobile.press('Space');
    await expect(root).toHaveAttribute('data-source-preview-mode', 'mobile');
    await expect(mobile).toHaveAttribute('aria-pressed', 'true');
    await desktop.click();
    await expect(root).toHaveAttribute('data-source-preview-mode', 'desktop');
    await modal.locator('button[data-dismiss="modal"], button[data-bs-dismiss="modal"]').first().click();
    await expect(modal).toBeHidden({timeout: 15000});
};

const getFormat = async(page) => {
    const root = getEditor(page);
    await expect(root).toHaveCount(1, {timeout: 15000});
    return root.locator('[data-source-preview-frame="1"]').getAttribute('data-banner-format');
};

const setFormat = async(page, format) => {
    if (await getFormat(page) === format) {
        return;
    }
    await page.locator('.local-course-banner-builder-admin-format-button').first().click();
    const modal = page.locator('.modal.show').last();
    await expect(modal).toBeVisible({timeout: 15000});
    const input = modal.locator(`input[name="bannerformat"][value="${format}"]`);
    await expect(input).toHaveCount(1);
    await input.evaluate(element => element.click());
    await modal.locator('button[type="submit"]').click();
    await expect(modal).toBeHidden({timeout: 15000});
    await expect(getEditor(page)).toHaveCount(1, {timeout: 15000});
    await expect(getEditor(page).locator('[data-source-preview-frame="1"]')).toHaveAttribute('data-banner-format', format);
};

const expectedHeight = format => {
    const policy = POLICY[format];
    const natural = 390 / policy.ratio;
    return policy.max === null ? natural : Math.min(policy.max, Math.max(policy.min, natural));
};

const readMeasurement = async(page, mode) => page.evaluate(expectedMode => {
    const root = document.querySelector('[data-source-visual-editor="1"]');
    const frame = root && root.querySelector('[data-source-preview-frame="1"]');
    if (!root || !frame) {
        throw new Error('Source visual editor frame is unavailable.');
    }
    const rootBox = root.getBoundingClientRect();
    const box = frame.getBoundingClientRect();
    const style = getComputedStyle(frame);
    const contentBox = {
        left: box.left + (parseFloat(style.borderLeftWidth) || 0),
        top: box.top + (parseFloat(style.borderTopWidth) || 0),
        width: frame.clientWidth,
        height: frame.clientHeight,
    };
    const control = root.querySelector('[data-source-preview-mode-control="1"]');
    const controlBox = control && control.getBoundingClientRect();
    const sourceTable = document.querySelector('.local-course-banner-builder-configured-sources-table');
    const sourceTableShell = sourceTable && sourceTable.closest('.table-responsive');
    const readNodes = (selector, kind) => Array.from(root.querySelectorAll(selector)).map((node, index) => {
        const nodeBox = node.getBoundingClientRect();
        return {
            key: `${kind}:${index}`,
            kind,
            rect: {left: nodeBox.left, top: nodeBox.top, width: nodeBox.width, height: nodeBox.height},
            normalized: {
                left: (nodeBox.left - contentBox.left) / Math.max(contentBox.width, 1),
                top: (nodeBox.top - contentBox.top) / Math.max(contentBox.height, 1),
                width: nodeBox.width / Math.max(contentBox.width, 1),
                height: nodeBox.height / Math.max(contentBox.height, 1),
            },
            crop: {
                left: node.getAttribute('data-preview-crop-left'),
                top: node.getAttribute('data-preview-crop-top'),
                width: node.getAttribute('data-preview-crop-width'),
                height: node.getAttribute('data-preview-crop-height'),
            },
            state: {
                fitMode: node.getAttribute('data-preview-fitmode'),
                anchor: node.getAttribute('data-preview-anchor'),
                customWidth: node.getAttribute('data-preview-custom-width'),
                customHeight: node.getAttribute('data-preview-custom-height'),
                offsetTop: node.getAttribute('data-preview-offset-top'),
                offsetRight: node.getAttribute('data-preview-offset-right'),
                offsetBottom: node.getAttribute('data-preview-offset-bottom'),
                offsetLeft: node.getAttribute('data-preview-offset-left'),
            },
            opacity: node.getAttribute('data-preview-image-opacity') || getComputedStyle(node).opacity,
            zIndex: node.getAttribute('data-preview-zindex') || getComputedStyle(node).zIndex,
            clipped: nodeBox.left < contentBox.left - 1 || nodeBox.top < contentBox.top - 1 ||
                nodeBox.right > contentBox.left + contentBox.width + 1 ||
                nodeBox.bottom > contentBox.top + contentBox.height + 1,
        };
    });
    const sourceSettings = Array.from(root.querySelectorAll('input, select, textarea')).map(controlNode => {
        const name = controlNode.name || controlNode.id || controlNode.type;
        return {
            name,
            value: /sesskey/i.test(name) ? '[redacted]' :
                (controlNode.type === 'checkbox' ? controlNode.checked : controlNode.value),
        };
    });
    return {
        mode: root.getAttribute('data-source-preview-mode'),
        expectedMode,
        root: {left: rootBox.left, top: rootBox.top, width: rootBox.width, height: rootBox.height},
        frame: {
            format: frame.getAttribute('data-banner-format'),
            logicalWidth: Number(frame.getAttribute('data-source-preview-mobile-logical-width')),
            logicalHeight: Number(frame.getAttribute('data-source-preview-mobile-logical-height')),
            maxHeight: Number(frame.getAttribute('data-source-preview-mobile-max-height')),
            ratio: Number(frame.getAttribute('data-source-preview-format-ratio')),
            rect: {left: box.left, top: box.top, width: box.width, height: box.height},
            contentRect: contentBox,
            computed: {aspectRatio: style.aspectRatio, minHeight: style.minHeight, maxHeight: style.maxHeight},
        },
        controls: {
            rect: controlBox && {left: controlBox.left, top: controlBox.top, width: controlBox.width, height: controlBox.height},
            pressed: Array.from(root.querySelectorAll('[data-source-preview-mode-value]')).map(button => ({
                value: button.getAttribute('data-source-preview-mode-value'),
                pressed: button.getAttribute('aria-pressed'),
                active: button.classList.contains('is-active'),
                bound: button.dataset.sourcePreviewModeBound || '',
            })),
        },
        nodes: [
            ...readNodes('[data-source-preview-layer="1"]', 'image'),
            ...readNodes('[data-source-preview-title="1"]', 'title'),
            ...readNodes('[data-source-preview-overlay="1"]', 'overlay'),
            ...readNodes('[data-source-preview-border="1"]', 'border'),
        ],
        sourceSettings,
        document: {
            clientWidth: document.documentElement.clientWidth,
            scrollWidth: document.documentElement.scrollWidth,
            overflow: Math.max(0, document.documentElement.scrollWidth - document.documentElement.clientWidth),
            overflowElements: Array.from(document.querySelectorAll('body *')).map(node => {
                const nodeBox = node.getBoundingClientRect();
                return {tag: node.tagName, className: String(node.className || ''), id: node.id || '',
                    left: nodeBox.left, right: nodeBox.right, width: nodeBox.width};
            }).filter(node => node.left < -1 || node.right > document.documentElement.clientWidth + 1)
                .sort((first, second) => second.right - first.right).slice(0, 12),
            sourceTableShell: sourceTableShell && {
                rect: sourceTableShell.getBoundingClientRect().toJSON(),
                clientWidth: sourceTableShell.clientWidth,
                scrollWidth: sourceTableShell.scrollWidth,
                overflowX: getComputedStyle(sourceTableShell).overflowX,
            },
        },
        adminManageAssets: performance.getEntriesByType('resource').map(entry => entry.name)
            .filter(name => name.includes('course_banner_builder') && name.includes('admin_manage')),
    };
}, mode);

const normalizedStable = (before, after) => before.nodes.filter(node =>
    node.kind !== 'image' || node.state.fitMode === 'custom'
).every(node => {
    const next = after.nodes.find(candidate => candidate.key === node.key);
    if (!next) {
        return false;
    }
    return ['left', 'top', 'width', 'height'].every(key =>
        Math.abs(node.normalized[key] - next.normalized[key]) <= 0.02);
});

const sourceSettingsStable = (before, after) => JSON.stringify(before.sourceSettings) === JSON.stringify(after.sourceSettings);

const layerStateStable = (before, after) => before.nodes.every(node => {
    const next = after.nodes.find(candidate => candidate.key === node.key);
    return !!next && JSON.stringify(node.crop) === JSON.stringify(next.crop) &&
        JSON.stringify(node.state) === JSON.stringify(next.state) && node.opacity === next.opacity && node.zIndex === next.zIndex;
});

const nativeZoom = (pid, operation, screenshot) => {
    const keys = operation === 'reset' ? "'^0'" : "'^{+}'";
    const count = operation === 'reset' ? 1 : 6;
    const safe = screenshot.replace(/'/g, "''");
    const command = `$ErrorActionPreference='Stop'; $window=Get-Process -Id ${pid} -ErrorAction Stop; ` +
        `if($window.MainWindowHandle -eq 0){throw 'Dedicated Chrome has no visible window.'}; ` +
        `$shell=New-Object -ComObject WScript.Shell; if(-not $shell.AppActivate($window.Id)){throw 'Dedicated Chrome activation failed.'}; ` +
        `Start-Sleep -Milliseconds 300; 1..${count} | ForEach-Object {$shell.SendKeys(${keys}); Start-Sleep -Milliseconds 180}; ` +
        `Start-Sleep -Milliseconds 600; Add-Type -AssemblyName System.Windows.Forms; Add-Type -AssemblyName System.Drawing; ` +
        `$b=[System.Windows.Forms.Screen]::PrimaryScreen.Bounds; $bmp=New-Object System.Drawing.Bitmap $b.Width,$b.Height; ` +
        `$g=[System.Drawing.Graphics]::FromImage($bmp); $g.CopyFromScreen($b.Location,[System.Drawing.Point]::Empty,$b.Size); ` +
        `$bmp.Save('${safe}'); $g.Dispose(); $bmp.Dispose(); Write-Output ($window.Id.ToString()+'|'+$window.MainWindowHandle.ToString())`;
    return execFileSync('powershell.exe', ['-NoProfile', '-NonInteractive', '-Command', command],
        {encoding: 'utf8', windowsHide: true}).trim();
};

const stopOwnedChromeProcessTree = pid => {
    if (!pid) {
        return;
    }
    try {
        execFileSync('taskkill.exe', ['/PID', String(pid), '/T', '/F'], {stdio: 'ignore', windowsHide: true});
    } catch (error) {
        // Chrome may already have exited after the CDP connection closed.
    }
};

test('CCB Batch 2E-B admin mobile preview is transient and matches the public mobile contract', async() => {
    test.setTimeout(Number(process.env.EASYEDU_CCB_2EB_TIMEOUT || 600000));
    const env = environment();
    const runId = `${env.scenarioId}-${new Date().toISOString().replace(/[:.]/g, '-')}-${process.pid}`;
    const root = path.join(env.artifactRoot, runId);
    const artifact = name => path.join(root, name);
    const profile = path.join(root, 'chrome-profile');
    fs.mkdirSync(root, {recursive: true});
    const repository = repositoryState();
    const publicRuntimePolicy = assertPublicRuntimePolicy(repository.pluginRoot);
    writeJson(artifact('ownership.json'), {
        marker: OWNERSHIP_MARKER,
        scenarioId: env.scenarioId,
        runId,
        startedAt: new Date().toISOString(),
        repository,
        publicRuntimePolicy,
        profile,
    });

    const browserProcess = spawn('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', [
        `--remote-debugging-port=${env.port}`,
        '--remote-debugging-address=127.0.0.1',
        `--user-data-dir=${profile}`,
        '--no-first-run',
        '--no-default-browser-check',
        '--disable-gpu',
        '--disable-features=CalculateNativeWinOcclusion',
        '--disable-backgrounding-occluded-windows',
        '--disable-save-password-bubble',
        '--new-window',
        `${env.baseUrl}/login/index.php`,
    ], {stdio: 'ignore', windowsHide: env.zoom !== 200});
    let browser = null;
    let page = null;
    let originalFormat = null;
    let cleanup = {formatRestored: false, zoomRestored: env.zoom !== 200, profileRemoved: false, error: null};
    const results = [];
    try {
        let lastError = null;
        for (let attempt = 0; attempt < 40 && !browser; attempt++) {
            try {
                browser = await chromium.connectOverCDP(`http://127.0.0.1:${env.port}`);
            } catch (error) {
                lastError = error;
                await new Promise(resolve => setTimeout(resolve, 250));
            }
        }
        if (!browser) {
            throw lastError || new Error('Dedicated Chrome CDP endpoint did not start.');
        }
        const context = browser.contexts()[0];
        page = context.pages()[0] || await context.newPage();
        page.setDefaultTimeout(15000);
        await login(page, env);
        const sourceUrl = await resolveSourceEditor(page, env);
        await verifySourceChainPreviewMode(page);
        originalFormat = await getFormat(page);
        writeJson(artifact('original-state.json'), {sourceUrl, format: originalFormat, changedSettings: 'format only'});
        if (env.zoom === 200) {
            const before = await page.evaluate(() => ({innerWidth, devicePixelRatio}));
            const evidence = nativeZoom(browserProcess.pid, 'zoom', artifact('zoom-200.png'));
            await page.waitForTimeout(700);
            const after = await page.evaluate(() => ({innerWidth, devicePixelRatio}));
            const widthRatio = before.innerWidth / Math.max(after.innerWidth, 1);
            const achieved = after.devicePixelRatio / Math.max(before.devicePixelRatio, 1) >= 1.9 || widthRatio >= 1.9;
            writeJson(artifact('zoom-200.json'), {before, after, widthRatio, achieved, evidence});
            expect(achieved).toBe(true);
        }

        for (const format of env.formats) {
            await setFormat(page, format);
            await page.goto(sourceUrl, {waitUntil: 'domcontentloaded'});
            await expect(getEditor(page)).toHaveCount(1, {timeout: 15000});
            for (const viewportName of env.viewports) {
                const viewport = DEFAULT_VIEWPORTS[viewportName];
                await page.setViewportSize(viewport);
                await page.waitForTimeout(150);
                const desktop = await readMeasurement(page, 'desktop');
                writeJson(artifact(`measurement-${format}-${viewportName}-desktop.json`), desktop);
                expect(desktop.mode).toBe('desktop');
                expect(desktop.frame.format).toBe(format);
                expect(desktop.controls.pressed).toEqual([
                    {value: 'desktop', pressed: 'true', active: true, bound: '1'},
                    {value: 'mobile', pressed: 'false', active: false, bound: '1'},
                ]);

                const mobileButton = getEditor(page).locator('[data-source-preview-mode-value="mobile"]');
                await mobileButton.focus();
                await expect(mobileButton).toBeFocused();
                await mobileButton.press('ArrowLeft');
                await expect(getEditor(page).locator('[data-source-preview-mode-value="desktop"]')).toBeFocused();
                await getEditor(page).locator('[data-source-preview-mode-value="desktop"]').press('ArrowRight');
                await expect(mobileButton).toBeFocused();
                await mobileButton.press('Home');
                await expect(getEditor(page).locator('[data-source-preview-mode-value="desktop"]')).toBeFocused();
                await getEditor(page).locator('[data-source-preview-mode-value="desktop"]').press('End');
                await expect(mobileButton).toBeFocused();
                await mobileButton.press('Space');
                await expect(getEditor(page)).toHaveAttribute('data-source-preview-mode', 'mobile');
                const mobile = await readMeasurement(page, 'mobile');
                writeJson(artifact(`measurement-${format}-${viewportName}-mobile.json`), mobile);
                const expectedLogicalHeight = expectedHeight(format);
                const displayScale = mobile.frame.rect.width / mobile.frame.logicalWidth;
                const expectedDisplayedHeight = expectedLogicalHeight * displayScale;
                expect(mobile.frame.logicalWidth).toBe(390);
                expect(Math.abs(mobile.frame.logicalHeight - expectedLogicalHeight)).toBeLessThanOrEqual(0.01);
                expect(Math.abs(mobile.frame.rect.height - expectedDisplayedHeight)).toBeLessThanOrEqual(1);
                expect(mobile.controls.pressed).toEqual([
                    {value: 'desktop', pressed: 'false', active: false, bound: '1'},
                    {value: 'mobile', pressed: 'true', active: true, bound: '1'},
                ]);
                // The configured-source table has a pre-existing, independently scrollable 19px
                // mobile overflow. This check prevents the simulation from widening that baseline.
                expect(mobile.document.overflow).toBeLessThanOrEqual(desktop.document.overflow + 1);
                expect(sourceSettingsStable(desktop, mobile)).toBe(true);
                expect(layerStateStable(desktop, mobile)).toBe(true);
                expect(normalizedStable(desktop, mobile)).toBe(true);
                results.push({format, viewport: viewportName, expectedLogicalHeight, displayScale, desktop, mobile});
                if (env.retainPassingScreenshots && viewportName === 'desktop') {
                    await page.screenshot({path: artifact(`representative-${format}-${viewportName}.png`), fullPage: true});
                }
                const desktopButton = getEditor(page).locator('[data-source-preview-mode-value="desktop"]');
                await desktopButton.focus();
                await desktopButton.press('Space');
                await expect(getEditor(page)).toHaveAttribute('data-source-preview-mode', 'desktop');
            }
        }

        writeJson(artifact('measurements.json'), results);
    } catch (error) {
        if (page) {
            await page.screenshot({path: artifact('failure.png'), fullPage: true}).catch(() => {});
        }
        writeJson(artifact('failure.json'), {error: String(error && error.stack || error)});
        throw error;
    } finally {
        try {
            if (page && originalFormat) {
                await page.goto(adminUrl(env), {waitUntil: 'domcontentloaded'});
                await resolveSourceEditor(page, env);
                await setFormat(page, originalFormat);
                cleanup.formatRestored = await getFormat(page) === originalFormat;
            }
            if (env.zoom === 200) {
                nativeZoom(browserProcess.pid, 'reset', artifact('cleanup-100.png'));
                cleanup.zoomRestored = true;
            }
        } catch (error) {
            cleanup.error = String(error && error.stack || error);
        }
        stopOwnedChromeProcessTree(browserProcess.pid);
        if (browser) {
            await browser.close().catch(() => {});
        }
        try {
            fs.rmSync(profile, {recursive: true, force: true, maxRetries: 40, retryDelay: 250});
        } catch (error) {
            cleanup.error = cleanup.error || String(error && error.stack || error);
        }
        cleanup.profileRemoved = !fs.existsSync(profile);
        cleanup.completedAt = new Date().toISOString();
        cleanup.artifactBytes = directoryBytes(root);
        writeJson(artifact('cleanup.json'), cleanup);
        writeJson(artifact('artifact-summary.json'), {
            marker: OWNERSHIP_MARKER,
            runId,
            complete: !cleanup.error && cleanup.formatRestored && cleanup.zoomRestored && cleanup.profileRemoved,
            artifactBytes: directoryBytes(root),
            profileRemaining: fs.existsSync(profile),
        });
        if (!cleanup.formatRestored || !cleanup.zoomRestored || !cleanup.profileRemoved) {
            throw new Error(`Fixture/browser cleanup incomplete: ${JSON.stringify(cleanup)}.`);
        }
    }
});
