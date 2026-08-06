const {test, expect, chromium} = require('@playwright/test');
const fs = require('fs');
const path = require('path');
const {spawn, execFileSync} = require('child_process');
const {metadata: {ccbArtifactsRoot}} = require('./playwright.config');

const OWNERSHIP_MARKER = 'local_course_banner_builder:batch-2f-a:public-title-accessibility';

const ensure = (condition, message) => {
    if (!condition) {
        throw new Error(message);
    }
};

const positiveEnvironmentInteger = name => {
    const value = String(process.env[name] || '').trim();
    ensure(/^[1-9]\d*$/.test(value), `${name} must be a positive integer for the local QA source category.`);
    return value;
};

const SENSITIVE_ARTIFACT_KEY = /(sesskey|password|token|cookie|authorization|storage|session)/i;

const sanitiseArtifactString = value => String(value)
    .replace(/([?&](?:sesskey|password|token|session(?:id)?|auth(?:orization)?)=)[^&#\s"']*/gi, '$1[redacted]')
    .replace(/(bearer\s+)[^\s"']+/gi, '$1[redacted]')
    .replace(/https?:\/\/[^\s"'<>]+/gi, url => {
        try {
            const parsed = new URL(url);
            parsed.search = '';
            return parsed.toString();
        } catch (error) {
            return url;
        }
    });

const sanitiseArtifactValue = (value, key = '') => {
    if (SENSITIVE_ARTIFACT_KEY.test(key)) {
        return '[redacted]';
    }
    if (typeof value === 'string') {
        return sanitiseArtifactString(value);
    }
    if (Array.isArray(value)) {
        return value.map(item => sanitiseArtifactValue(item));
    }
    if (value && typeof value === 'object') {
        return Object.fromEntries(Object.entries(value)
            .map(([childKey, childValue]) => [childKey, sanitiseArtifactValue(childValue, childKey)]));
    }
    return value;
};

const sanitiseRequestSummary = request => ({
    method: request.method(),
    resourceType: request.resourceType(),
    url: sanitiseArtifactString(request.url()),
    failure: sanitiseArtifactString(request.failure()?.errorText || 'unknown request failure'),
});

const writeJson = (file, value) => {
    fs.mkdirSync(path.dirname(file), {recursive: true});
    fs.writeFileSync(file, JSON.stringify(sanitiseArtifactValue(value), null, 2));
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

// Playwright config owns the shared resolver and rejects repository-internal roots.
const resolveArtifactsRoot = () => {
    ensure(typeof ccbArtifactsRoot === 'string' && path.isAbsolute(ccbArtifactsRoot),
        'Playwright configuration did not provide a valid external artifacts root.');
    return ccbArtifactsRoot;
};

const runWorkspace = (artifactRoot, runId) => {
    const date = new Date().toISOString().slice(0, 10);
    const root = path.join(artifactRoot, 'ccb', 'public-title-accessibility', 'test-results', date, runId);
    const profile = path.join(artifactRoot, 'ccb', 'public-title-accessibility', 'profiles', runId);
    fs.mkdirSync(root, {recursive: true});
    return {root, profile, artifact: name => path.join(root, name)};
};

if (process.env.EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT_PROBE === '1') {
    try {
        process.stdout.write(`${resolveArtifactsRoot()}\n`);
        process.exit(0);
    } catch (error) {
        process.stderr.write(`${error.message}\n`);
        process.exit(1);
    }
}

const environment = () => {
    const required = ['EASYEDU_MOODLE_URL', 'EASYEDU_MOODLE_USERNAME', 'EASYEDU_MOODLE_PASSWORD'];
    const missing = required.filter(name => !process.env[name]);
    ensure(!missing.length, `Missing Batch 2F-A environment values: ${missing.join(', ')}.`);
    const courseId = String(process.env.EASYEDU_CCB_2FA_FIXTURE_COURSE_ID || '').trim();
    ensure(/^[1-9]\d*$/.test(courseId), 'EASYEDU_CCB_2FA_FIXTURE_COURSE_ID must be a positive integer for the stable QA fixture.');
    const activityCmid = String(process.env.EASYEDU_CCB_2FA_ACTIVITY_CMID || '').trim();
    ensure(/^[1-9]\d*$/.test(activityCmid), 'EASYEDU_CCB_2FA_ACTIVITY_CMID must be a positive integer for the target activity.');
    const sourceCategoryId = positiveEnvironmentInteger('EASYEDU_CCB_2FA_SOURCE_CATEGORY_ID');
    const scenarioId = process.env.EASYEDU_CCB_2FA_SCENARIO_ID || 'public-title-a11y';
    ensure(/^[a-z0-9][a-z0-9-]{2,80}$/.test(scenarioId), 'Scenario id must be a lowercase slug.');
    const artifactRoot = resolveArtifactsRoot();
    console.log(`[CCB QA] External artifacts root: ${artifactRoot}`);
    const zoom = Number(process.env.EASYEDU_CCB_2FA_ZOOM || 100);
    ensure([100, 200].includes(zoom), 'EASYEDU_CCB_2FA_ZOOM must be 100 or 200.');
    return {
        baseUrl: process.env.EASYEDU_MOODLE_URL.replace(/\/$/, ''),
        username: process.env.EASYEDU_MOODLE_USERNAME,
        password: process.env.EASYEDU_MOODLE_PASSWORD,
        courseId,
        activityCmid,
        sourceCategoryId,
        scenarioId,
        artifactRoot,
        zoom,
        port: Number(process.env.EASYEDU_CCB_2FA_PORT || (9650 + (process.pid % 200))),
    };
};

const repositoryState = () => {
    const pluginRoot = path.resolve(__dirname, '../../../course_banner_builder');
    const run = args => execFileSync('git', args, {cwd: pluginRoot, encoding: 'utf8'}).trim();
    return {pluginRoot, branch: run(['branch', '--show-current']), head: run(['rev-parse', 'HEAD'])};
};

const protectedPublicPolicy = pluginRoot => {
    const runtime = fs.readFileSync(path.join(pluginRoot, 'classes', 'hook_callbacks.php'), 'utf8');
    const nativeCore = fs.readFileSync(path.join(pluginRoot, 'scss', 'components', '_native-banner-core.scss'), 'utf8');
    const required = [
        'min-height: 128px;',
        'max-height: 280px;',
        'max-height: 360px;',
        'max-height: 210px;',
        'max-height: 300px;',
    ];
    required.forEach(value => {
        ensure(runtime.includes(value), `Runtime public sizing policy is missing ${value}.`);
        ensure(nativeCore.includes(value), `Compiled public sizing policy is missing ${value}.`);
    });
    ensure(!runtime.includes("'aria-hidden' => 'true',\n            'style' => $contentstyle"),
        'The server-rendered public banner must not hide its whole subtree.');
    ensure(runtime.includes("$titleattributes['data-course-banner-builder-semantic-title'] = 'secondary'"),
        'The public title semantic contract is absent from the PHP renderer.');
    return {runtimePolicy: required, semanticContract: true};
};

const login = async(page, env) => {
    await page.goto(`${env.baseUrl}/login/index.php`, {waitUntil: 'domcontentloaded'});
    await page.locator('#username').fill(env.username);
    await page.locator('#password').fill(env.password);
    await page.locator('#loginbtn').click({noWaitAfter: true});
    await expect(page).not.toHaveURL(/\/login\//, {timeout: 30000});
};

const adminUrl = env => `${env.baseUrl}/local/course_banner_builder/admin_manage.php`;
const courseUrl = env => `${env.baseUrl}/course/view.php?id=${env.courseId}`;

const openCourseEdit = async(page, env) => {
    await page.goto(courseUrl(env), {waitUntil: 'commit'});
    const href = await page.locator('a').evaluateAll(links => {
        const link = links.find(item => (item.textContent || '').trim() === 'Settings' &&
            item.href.includes('/course/edit.php'));
        return link ? link.href : null;
    });
    ensure(href, 'Moodle course Settings link was not found for fixture restoration.');
    await page.goto(href, {waitUntil: 'commit'});
};

const courseState = async(page, env) => {
    await openCourseEdit(page, env);
    const fullname = page.locator('#id_fullname');
    const category = page.locator('#id_category');
    await expect(fullname).toHaveCount(1, {timeout: 15000});
    await expect(category).toHaveCount(1, {timeout: 15000});
    return {fullname: await fullname.inputValue(), category: await category.inputValue()};
};

const updateFullname = async(page, env, fullname) => {
    await openCourseEdit(page, env);
    await page.locator('#id_fullname').fill(fullname);
    await page.locator('#id_saveanddisplay').click();
    await expect.poll(async() => (await courseState(page, env)).fullname, {timeout: 30000})
        .toBe(fullname);
};

const updateCategory = async(page, env, categoryId) => {
    await openCourseEdit(page, env);
    await page.locator('#id_category').evaluate((field, value) => {
        field.value = String(value);
        field.dispatchEvent(new Event('change', {bubbles: true}));
    }, categoryId);
    await page.locator('#id_saveanddisplay').click();
    await expect.poll(async() => String((await courseState(page, env)).category), {timeout: 30000})
        .toBe(String(categoryId));
};

// The stable fixture course is restored in place. Only the generated source category
// is deleted, through Moodle's supported category-management form.
const deleteDisposableSourceCategory = async(page, env) => {
    await page.goto(`${env.baseUrl}/course/management.php?categoryid=${env.sourceCategoryId}`, {waitUntil: 'domcontentloaded'});
    const deleteHref = await page.locator('a[href*="action=deletecategory"]').evaluateAll((links, categoryId) => {
        const link = links.find(item => new URL(item.href).searchParams.get('categoryid') === String(categoryId));
        return link ? link.href : null;
    }, env.sourceCategoryId);
    ensure(deleteHref, `Disposable source category ${env.sourceCategoryId} delete action was not found.`);
    await page.goto(deleteHref, {waitUntil: 'domcontentloaded'});
    const form = page.locator('form').filter({has: page.locator('input[name="fulldelete"]')}).first();
    await expect(form).toHaveCount(1, {timeout: 15000});
    await form.locator('input[name="fulldelete"][value="1"]').check({force: true});
    await form.locator('input[type="submit"], button[type="submit"]').last().click();
    await page.waitForLoadState('domcontentloaded').catch(() => {});
    await expect(page.locator('body')).not.toContainText(`categoryid=${env.sourceCategoryId}`);
};

const titleForm = async(page, env) => {
    await page.goto(adminUrl(env), {waitUntil: 'domcontentloaded'});
    const form = page.locator('form[data-banner-title-editor]').first();
    await expect(form).toHaveCount(1, {timeout: 15000});
    return form;
};

const formValues = form => form.evaluate(element => Object.fromEntries(Array.from(element.elements)
    .filter(control => control.name)
    .map(control => [control.name, control.type === 'checkbox' ? (control.checked ? '1' : '0') : control.value])));

const sanitiseTitleValues = values => Object.fromEntries(Object.entries(values)
    .map(([name, value]) => [name, /sesskey/i.test(name) ? '[redacted]' : value]));

const setFormValues = (form, values) => form.evaluate((element, next) => {
    Object.entries(next).forEach(([name, value]) => {
        const control = Array.from(element.elements).find(item => item.name === name);
        if (!control) {
            return;
        }
        const toggle = control.id
            ? Array.from(element.querySelectorAll('[data-target-input]'))
                .find(button => button.getAttribute('data-target-input') === `#${control.id}`)
            : null;
        if (toggle) {
            const enabled = value === '1' || value === true;
            if ((toggle.getAttribute('aria-pressed') === 'true') !== enabled) {
                toggle.click();
            }
            return;
        }
        if (control.type === 'checkbox') {
            control.checked = value === '1' || value === true;
        } else {
            control.value = String(value);
        }
        control.dispatchEvent(new Event('change', {bubbles: true}));
    });
}, values);

const saveForm = async(form, page) => {
    const navigation = page.waitForNavigation({waitUntil: 'domcontentloaded', timeout: 30000});
    await form.evaluate(element => element.requestSubmit());
    await navigation;
    await page.waitForTimeout(300);
};

const normalise = value => String(value || '').replace(/\s+/g, ' ').trim().toLocaleLowerCase();

const publicSnapshot = async(page, env, label, artifact) => {
    await page.goto(courseUrl(env), {waitUntil: 'domcontentloaded'});
    await page.setViewportSize({width: 1600, height: 900});
    await page.waitForTimeout(1900);
    const snapshot = await page.locator('body').ariaSnapshot();
    const dom = await page.evaluate(() => {
        const banner = document.querySelector('.local-course-banner-builder-native-course-banner');
        const visualTitle = document.querySelector('.local-course-banner-builder-banner-title-overlay');
        const heading = document.querySelector('#page-header h1, h1');
        const bannerBox = banner && banner.getBoundingClientRect();
        const titleBox = visualTitle && visualTitle.getBoundingClientRect();
        const hiddenAncestor = node => {
            let current = node;
            while (current) {
                if (current.getAttribute && current.getAttribute('aria-hidden') === 'true') {
                    return true;
                }
                current = current.parentElement;
            }
            return false;
        };
        const focusable = banner ? Array.from(banner.querySelectorAll(
            'a[href], button, input, select, textarea, [tabindex]:not([tabindex="-1"])'
        )).map(node => ({
            tag: node.tagName,
            name: (node.getAttribute('aria-label') || node.textContent || '').trim(),
            hiddenAncestor: hiddenAncestor(node),
        })) : [];
        const h1s = Array.from(document.querySelectorAll('h1')).map(node => ({
            text: (node.textContent || '').trim(),
            display: getComputedStyle(node).display,
            visibility: getComputedStyle(node).visibility,
            hiddenAncestor: hiddenAncestor(node),
        }));
        return {
            banner: banner && {
                ariaHidden: banner.getAttribute('aria-hidden'),
                width: bannerBox.width,
                height: bannerBox.height,
                overlayCount: banner.querySelectorAll('.local-course-banner-builder-fixed-overlay').length,
                borderCount: banner.querySelectorAll('.local-course-banner-builder-fixed-border').length,
            },
            visualTitle: visualTitle && {
                tag: visualTitle.tagName,
                text: (visualTitle.textContent || '').trim(),
                ariaHidden: visualTitle.getAttribute('aria-hidden'),
                semantic: visualTitle.getAttribute('data-course-banner-builder-semantic-title'),
                decorative: visualTitle.getAttribute('data-course-banner-builder-decorative-title'),
                box: titleBox && {left: titleBox.left, top: titleBox.top, width: titleBox.width, height: titleBox.height},
                normalised: bannerBox && titleBox ? {
                    left: (titleBox.left - bannerBox.left) / bannerBox.width,
                    top: (titleBox.top - bannerBox.top) / bannerBox.height,
                    width: titleBox.width / bannerBox.width,
                    height: titleBox.height / bannerBox.height,
                } : null,
            },
            pageHeading: heading && {text: (heading.textContent || '').trim(), display: getComputedStyle(heading).display},
            h1s,
            focusable,
            document: {
                clientWidth: document.documentElement.clientWidth,
                scrollWidth: document.documentElement.scrollWidth,
            },
        };
    });
    const primaryHeadings = snapshot.split(/\r?\n/)
        .filter(line => /\bheading\b.*\[level=1\]/.test(line));
    const result = {label, snapshot, primaryHeadings, dom};
    writeJson(artifact(`a11y-${label}.json`), result);
    await page.screenshot({path: artifact(`public-${label}.png`), fullPage: true});
    return result;
};

const injectAndExerciseFutureControl = async(page, artifact) => {
    await page.evaluate(() => {
        const banner = document.querySelector('.local-course-banner-builder-native-course-banner');
        if (!banner) {
            throw new Error('Public banner is unavailable for future-control contract check.');
        }
        const control = document.createElement('button');
        control.type = 'button';
        control.id = 'ccb-2fa-future-control';
        control.className = 'btn btn-primary';
        control.textContent = 'Batch 2F-A control probe';
        control.setAttribute('aria-label', 'Batch 2F-A control probe');
        control.setAttribute('aria-pressed', 'false');
        control.addEventListener('click', () => control.setAttribute('aria-pressed', 'true'));
        banner.appendChild(control);
    });
    const control = page.locator('#ccb-2fa-future-control');
    await control.focus();
    await expect(control).toBeFocused();
    await control.press('Space');
    await expect(control).toHaveAttribute('aria-pressed', 'true');
    const result = await control.evaluate(node => ({
        name: node.getAttribute('aria-label'),
        pressed: node.getAttribute('aria-pressed'),
        underAriaHidden: !!node.closest('[aria-hidden="true"]'),
        focusVisible: node.matches(':focus-visible'),
        display: getComputedStyle(node).display,
        visibility: getComputedStyle(node).visibility,
    }));
    writeJson(artifact('future-control-keyboard.json'), result);
    await page.screenshot({path: artifact('future-control-focus.png'), fullPage: true});
    return result;
};

const nativeZoom = (pid, operation, screenshot) => {
    const keys = operation === 'reset' ? "'^0'" : "'^{+}'";
    const count = operation === 'reset' ? 1 : 6;
    const safe = screenshot.replace(/'/g, "''");
    const command = String.raw`$ErrorActionPreference='Stop'
Add-Type @"
using System;
using System.Runtime.InteropServices;
public static class CcbWindow {
    [DllImport("user32.dll")] public static extern bool SetForegroundWindow(IntPtr h);
    [DllImport("user32.dll")] public static extern bool ShowWindow(IntPtr h, int n);
    [DllImport("user32.dll")] public static extern IntPtr GetForegroundWindow();
}
"@
$window=Get-Process -Id ${pid} -ErrorAction Stop
if($window.MainWindowHandle -eq 0){throw 'Dedicated Chrome has no visible window.'}
$handle=[IntPtr]$window.MainWindowHandle
[CcbWindow]::ShowWindow($handle,5) | Out-Null
$shell=New-Object -ComObject WScript.Shell
$activated=$false
1..8 | ForEach-Object {
    [CcbWindow]::SetForegroundWindow($handle) | Out-Null
    $shell.AppActivate($window.Id) | Out-Null
    Start-Sleep -Milliseconds 150
    if([CcbWindow]::GetForegroundWindow() -eq $handle){$activated=$true; break}
}
if(-not $activated){throw 'Dedicated Chrome activation failed.'}
Start-Sleep -Milliseconds 350
1..${count} | ForEach-Object {
    [CcbWindow]::SetForegroundWindow($handle) | Out-Null
    $shell.SendKeys(${keys})
    Start-Sleep -Milliseconds 200
}
Start-Sleep -Milliseconds 700
Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName System.Drawing
$b=[System.Windows.Forms.Screen]::PrimaryScreen.Bounds
$bmp=New-Object System.Drawing.Bitmap $b.Width,$b.Height
$g=[System.Drawing.Graphics]::FromImage($bmp)
$g.CopyFromScreen($b.Location,[System.Drawing.Point]::Empty,$b.Size)
$bmp.Save('${safe}')
$g.Dispose(); $bmp.Dispose()
Write-Output ($window.Id.ToString()+'|'+$window.MainWindowHandle.ToString())`;
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
        // Chrome may have already closed with the CDP connection.
    }
};

const startDedicatedChrome = (profile, env, port) => spawn('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', [
    `--remote-debugging-port=${port}`, '--remote-debugging-address=127.0.0.1', `--user-data-dir=${profile}`,
    '--no-first-run', '--no-default-browser-check', '--disable-save-password-bubble', '--disable-gpu',
    '--disable-features=CalculateNativeWinOcclusion', '--disable-backgrounding-occluded-windows', '--new-window',
    `${env.baseUrl}/login/index.php`,
], {stdio: 'ignore', windowsHide: false});

const connectDedicatedChrome = async port => {
    let browser = null;
    for (let attempt = 0; attempt < 45 && !browser; attempt++) {
        try { browser = await chromium.connectOverCDP(`http://127.0.0.1:${port}`); } catch (error) {
            await new Promise(resolve => setTimeout(resolve, 250));
        }
    }
    ensure(browser, 'Dedicated Chrome CDP endpoint did not start.');
    return browser;
};

const withTimeout = (operation, milliseconds, label) => Promise.race([
    operation,
    new Promise((resolve, reject) => setTimeout(() => reject(new Error(`${label} timed out after ${milliseconds}ms.`)), milliseconds)),
]);

const cdpZoomEvidence = async(page, context) => {
    const cdp = await context.newCDPSession(page);
    try {
        const [layout, inPage, window] = await Promise.all([
            cdp.send('Page.getLayoutMetrics'),
            page.evaluate(() => ({
                innerWidth: window.innerWidth,
                clientWidth: document.documentElement.clientWidth,
                scrollWidth: document.documentElement.scrollWidth,
                devicePixelRatio: window.devicePixelRatio,
                visualViewportWidth: window.visualViewport?.width ?? null,
                visualViewportScale: window.visualViewport?.scale ?? null,
            })),
            cdp.send('Browser.getWindowForTarget').catch(() => null),
        ]);
        return {inPage, layout, window};
    } finally {
        await cdp.detach().catch(() => {});
    }
};

const captureCdpPage = async(page, context, file) => {
    const cdp = await context.newCDPSession(page);
    try {
        const capture = await withTimeout(cdp.send('Page.captureScreenshot', {
            format: 'png', fromSurface: true, captureBeyondViewport: false,
        }), 15000, 'CDP page capture');
        fs.writeFileSync(file, Buffer.from(capture.data, 'base64'));
    } finally {
        await cdp.detach().catch(() => {});
    }
};

const prepareNativeZoomProfile = (profile, baseUrl, percentage) => {
    const parsedUrl = new URL(baseUrl);
    const origin = parsedUrl.origin;
    const host = parsedUrl.hostname;
    const zoomLevel = Math.log(percentage / 100) / Math.log(1.2);
    // Chrome persists native host zoom under the profile's partition key. A
    // scalar value directly under per_host_zoom_levels is ignored by Chrome.
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
    return {origin, host, percentage, zoomLevel};
};

const deleteDisposableSourceCategoryBackend = (pluginRoot, categoryId) => {
    const moodleRoot = path.resolve(pluginRoot, '../..');
    const php = path.resolve(moodleRoot, '../php/php.exe');
    const config = path.join(moodleRoot, 'config.php').replace(/\\/g, '/').replace(/'/g, "\\'");
    ensure(fs.existsSync(php), 'Moodle PHP executable is unavailable for disposable fixture cleanup.');
    const code = `define('CLI_SCRIPT', true); require '${config}'; ` +
        `$categoryid=${Number(categoryId)}; $category=core_course_category::get($categoryid, MUST_EXIST); ` +
        `if(count($category->get_courses(['recursive'=>true,'summary'=>false])) !== 0){throw new moodle_exception('Disposable category still contains courses.');} ` +
        `\\local_course_banner_builder\\manager::delete_category_content($categoryid, true); $category->delete_full(false); ` +
        `global $DB; if($DB->record_exists('course_categories',['id'=>$categoryid]) || $DB->count_records('local_course_banner_builder_elements',['categoryid'=>$categoryid]) !== 0){throw new moodle_exception('Disposable category cleanup verification failed.');} ` +
        `echo json_encode(['categoryid'=>$categoryid,'removed'=>true]);`;
    return JSON.parse(execFileSync(php, ['-r', code], {encoding: 'utf8', windowsHide: true}).trim());
};

test('CCB Batch 2F-A keeps one primary public heading and isolates decoration', async() => {
    test.setTimeout(Number(process.env.EASYEDU_CCB_2FA_TIMEOUT || 300000));
    const env = environment();
    const runId = `${env.scenarioId}-${new Date().toISOString().replace(/[:.]/g, '-')}-${process.pid}`;
    const workspace = runWorkspace(env.artifactRoot, runId);
    const {root, profile, artifact} = workspace;
    const repository = repositoryState();
    const publicPolicy = protectedPublicPolicy(repository.pluginRoot);
    writeJson(artifact('ownership.json'), {marker: OWNERSHIP_MARKER, runId, repository, publicPolicy, profile});

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
    let originalCourse = null;
    let originalTitle = null;
    let tracing = false;
    const cleanup = {courseRestored: false, categoryRestored: false, titleSettingsRestored: false, zoomRestored: env.zoom !== 200,
        profileRemoved: false, error: null};
    try {
        let lastError = null;
        for (let attempt = 0; attempt < 45 && !browser; attempt++) {
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
        page.setDefaultTimeout(30000);
        page.setDefaultNavigationTimeout(45000);
        await context.tracing.start({screenshots: true, snapshots: true, sources: true});
        tracing = true;
        await login(page, env);
        originalCourse = await courseState(page, env);
        ensure(String(originalCourse.category) === '3',
            `Batch 2F-A expected original category 3, found ${originalCourse.category}.`);
        originalTitle = await formValues(await titleForm(page, env));
        writeJson(artifact('original-state.json'), {course: originalCourse, title: sanitiseTitleValues(originalTitle)});

        const longTitle = 'Accessibilite des apprentissages : cooperation internationale, inclusion numerique et reussite durable';
        await updateCategory(page, env, env.sourceCategoryId);
        expect(String((await courseState(page, env)).category)).toBe(env.sourceCategoryId);
        await updateFullname(page, env, longTitle);

        let editor = await titleForm(page, env);
        await setFormValues(editor, {enabled: '0', replacemoodletitle: '0'});
        await saveForm(editor, page);
        const disabled = await publicSnapshot(page, env, 'title-disabled', artifact);
        expect(disabled.primaryHeadings).toHaveLength(1);
        expect(disabled.dom.visualTitle).toBeNull();
        expect(disabled.dom.banner.ariaHidden).toBeNull();

        editor = await titleForm(page, env);
        await setFormValues(editor, {enabled: '1', replacemoodletitle: '0', frameenabled: '1', frametype: 'box'});
        await saveForm(editor, page);
        const duplicateVisible = await publicSnapshot(page, env, 'replacement-disabled', artifact);
        expect(duplicateVisible.primaryHeadings).toHaveLength(1);
        expect(normalise(duplicateVisible.dom.pageHeading.text)).toBe(normalise(longTitle));
        expect(duplicateVisible.dom.visualTitle.tag).toBe('DIV');
        expect(duplicateVisible.dom.visualTitle.ariaHidden).toBe('true');
        expect(duplicateVisible.dom.visualTitle.decorative).toBe('1');
        expect(duplicateVisible.dom.focusable.every(node => !node.hiddenAncestor)).toBe(true);

        editor = await titleForm(page, env);
        await setFormValues(editor, {enabled: '1', replacemoodletitle: '1'});
        await saveForm(editor, page);
        const replacementEnabled = await publicSnapshot(page, env, 'replacement-enabled-long-title', artifact);
        expect(replacementEnabled.primaryHeadings).toHaveLength(1);
        expect(normalise(replacementEnabled.dom.pageHeading.text)).toBe(normalise(longTitle));
        expect(replacementEnabled.dom.h1s.some(heading => heading.display === 'none')).toBe(false);
        expect(replacementEnabled.dom.visualTitle.ariaHidden).toBe('true');
        expect(replacementEnabled.dom.document.scrollWidth).toBe(replacementEnabled.dom.document.clientWidth);
        expect(replacementEnabled.dom.banner.overlayCount).toBe(duplicateVisible.dom.banner.overlayCount);
        expect(replacementEnabled.dom.banner.borderCount).toBe(duplicateVisible.dom.banner.borderCount);
        ['left', 'top', 'width', 'height'].forEach(key => {
            expect(Math.abs(replacementEnabled.dom.visualTitle.normalised[key] -
                duplicateVisible.dom.visualTitle.normalised[key])).toBeLessThanOrEqual(0.01);
        });

        const futureControl = await injectAndExerciseFutureControl(page, artifact);
        expect(futureControl.underAriaHidden).toBe(false);
        expect(futureControl.name).toBe('Batch 2F-A control probe');
        expect(futureControl.pressed).toBe('true');
        expect(futureControl.focusVisible).toBe(true);

        if (env.zoom === 200) {
            const before = await page.evaluate(() => ({innerWidth, devicePixelRatio}));
            const evidence = nativeZoom(browserProcess.pid, 'zoom', artifact('zoom-200-chrome.png'));
            await page.waitForTimeout(900);
            const after = await page.evaluate(() => ({innerWidth, devicePixelRatio}));
            const widthRatio = before.innerWidth / Math.max(after.innerWidth, 1);
            const achieved = after.devicePixelRatio / Math.max(before.devicePixelRatio, 1) >= 1.9 || widthRatio >= 1.9;
            writeJson(artifact('zoom-200.json'), {before, after, widthRatio, achieved, evidence});
            expect(achieved).toBe(true);
        }
    } catch (error) {
        if (page) {
            await page.screenshot({path: artifact('failure.png'), fullPage: true}).catch(() => {});
        }
        writeJson(artifact('failure.json'), {error: String(error && error.stack || error)});
        throw error;
    } finally {
        if (page && originalTitle) {
            try {
                const editor = await titleForm(page, env);
                await setFormValues(editor, originalTitle);
                await saveForm(editor, page);
                cleanup.titleSettingsRestored = true;
            } catch (error) {
                cleanup.error = String(error && error.stack || error);
            }
        }
        if (page && originalCourse) {
            try {
                await updateFullname(page, env, originalCourse.fullname);
                await updateCategory(page, env, originalCourse.category);
                const restored = await courseState(page, env);
                cleanup.courseRestored = restored.fullname === originalCourse.fullname;
                cleanup.categoryRestored = String(restored.category) === String(originalCourse.category);
            } catch (error) {
                cleanup.error = cleanup.error || String(error && error.stack || error);
            }
        }
        if (env.zoom === 200) {
            try {
                nativeZoom(browserProcess.pid, 'reset', artifact('cleanup-100-chrome.png'));
                cleanup.zoomRestored = true;
            } catch (error) {
                cleanup.error = cleanup.error || String(error && error.stack || error);
            }
        }
        if (tracing && browser) {
            await browser.contexts()[0].tracing.stop({path: artifact('trace.zip')}).catch(error => {
                cleanup.error = cleanup.error || String(error && error.stack || error);
            });
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
            complete: !cleanup.error && cleanup.courseRestored && cleanup.categoryRestored && cleanup.titleSettingsRestored &&
                cleanup.zoomRestored && cleanup.profileRemoved,
            artifactBytes: directoryBytes(root),
            profileRemaining: fs.existsSync(profile),
        });
        if (!cleanup.courseRestored || !cleanup.categoryRestored || !cleanup.titleSettingsRestored ||
                !cleanup.zoomRestored || !cleanup.profileRemoved) {
            throw new Error(`Fixture/browser cleanup incomplete: ${JSON.stringify(cleanup)}.`);
        }
    }
});

const activityUrl = env => `${env.baseUrl}/mod/forum/view.php?id=${env.activityCmid}`;

const contextualTitleForm = async(page, env, context) => {
    await page.goto(adminUrl(env), {waitUntil: 'domcontentloaded'});
    const form = page.locator(`form[data-banner-title-editor][data-title-current-context="${context}"]`);
    await expect(form).toHaveCount(1, {timeout: 15000});
    return form;
};

const activityBannersForm = async(page, env) => {
    await page.goto(adminUrl(env), {waitUntil: 'domcontentloaded'});
    const form = page.locator('form').filter({has: page.locator('input[name="updatecourseactivitybanners"]')});
    await expect(form).toHaveCount(1, {timeout: 15000});
    return form;
};

const activityBannersEnabled = form => form.locator('input[name="coursebanneractivitiesenabled"]')
    .inputValue().then(value => value === '0');

const setActivityBannersEnabled = async(form, page, enabled) => {
    await form.locator('input[name="coursebanneractivitiesenabled"]').evaluate((field, value) => {
        field.value = value;
    }, enabled ? '1' : '0');
    const navigation = page.waitForNavigation({waitUntil: 'domcontentloaded', timeout: 30000});
    await form.evaluate(element => element.requestSubmit());
    await navigation;
    await page.waitForTimeout(300);
};

const activityState = async(page, env) => {
    await page.goto(activityUrl(env), {waitUntil: 'domcontentloaded'});
    await page.setViewportSize({width: 900, height: 900});
    await page.waitForTimeout(1500);
    return page.evaluate(() => {
        const hiddenAncestor = node => !!node.closest('[aria-hidden="true"]');
        const box = node => {
            const rect = node.getBoundingClientRect();
            const style = getComputedStyle(node);
            return {text: (node.textContent || '').trim(), tag: node.tagName, hiddenAncestor: hiddenAncestor(node),
                ariaHidden: node.getAttribute('aria-hidden'), display: style.display, visibility: style.visibility,
                semantic: node.getAttribute('data-course-banner-builder-semantic-title'),
                overflow: style.overflow, left: rect.left, top: rect.top, width: rect.width, height: rect.height,
                lineCount: new Set(Array.from(document.createRange().getClientRects()).map(rect => rect.top)).size};
        };
        const headings = Array.from(document.querySelectorAll('h1, h2')).map(box);
        const banner = document.querySelector('.local-course-banner-builder-native-course-banner');
        const bannerRect = banner && banner.getBoundingClientRect();
        const pageHeader = document.getElementById('page-header');
        const visualTitle = document.querySelector('.local-course-banner-builder-banner-title-overlay');
        const documentElement = document.documentElement;
        const visibleElements = Array.from(document.querySelectorAll('*')).map(node => {
            const style = getComputedStyle(node);
            const rect = node.getBoundingClientRect();
            return {node, style, rect};
        }).filter(({style, rect}) => style.display !== 'none' && style.visibility !== 'hidden' &&
            rect.width > 0 && rect.height > 0);
        const describeElement = ({node, style, rect}) => ({
            tag: node.tagName,
            id: node.id || null,
            className: typeof node.className === 'string' ? node.className : '',
            left: rect.left,
            right: rect.right,
            top: rect.top,
            bottom: rect.bottom,
            width: rect.width,
            height: rect.height,
            overflowX: style.overflowX,
            overflowY: style.overflowY,
        });
        const widestVisibleElement = visibleElements.reduce((widest, candidate) =>
            !widest || candidate.rect.width > widest.rect.width ? candidate : widest, null);
        const overflowingElements = visibleElements.filter(({rect}) =>
            rect.right > documentElement.clientWidth + 1 || rect.left < -1).map(describeElement);
        const hiddenFocusables = banner ? Array.from(banner.querySelectorAll('[aria-hidden="true"] a[href], [aria-hidden="true"] button, [aria-hidden="true"] input, [aria-hidden="true"] select, [aria-hidden="true"] textarea, [aria-hidden="true"] [tabindex]:not([tabindex="-1"])'))
            .map(node => ({tag: node.tagName, text: (node.textContent || '').trim()})) : [];
        return {headings, hiddenFocusables, banner: banner && {width: bannerRect.width, height: bannerRect.height,
            overlays: banner.querySelectorAll('.local-course-banner-builder-fixed-overlay').length,
            borders: banner.querySelectorAll('.local-course-banner-builder-fixed-border').length,
            ariaHidden: banner.getAttribute('aria-hidden'),
            format: banner.closest('#local-course-banner-builder-native-shell')?.getAttribute('data-banner-format') || null},
        pageHeader: pageHeader && {titleReplaced: [pageHeader, document.documentElement, document.body]
            .some(node => node.classList.contains('local-course-banner-builder-native-course-title-replaced'))},
        visualTitle: visualTitle && {ariaHidden: visualTitle.getAttribute('aria-hidden'),
            decorative: visualTitle.getAttribute('data-course-banner-builder-decorative-title')},
        semanticTitles: Array.from(document.querySelectorAll('h2[data-course-banner-builder-semantic-title="secondary"]')).map(box),
        document: {clientWidth: documentElement.clientWidth, scrollWidth: documentElement.scrollWidth,
            innerWidth, outerWidth, bodyClientWidth: document.body.clientWidth, bodyScrollWidth: document.body.scrollWidth,
            visualViewportWidth: window.visualViewport?.width ?? null, visualViewportScale: window.visualViewport?.scale ?? null,
            documentOverflowX: getComputedStyle(documentElement).overflowX,
            documentOverflowY: getComputedStyle(documentElement).overflowY,
            bodyOverflowX: getComputedStyle(document.body).overflowX,
            bodyOverflowY: getComputedStyle(document.body).overflowY,
            verticalScrollbarWidth: innerWidth - documentElement.clientWidth,
            horizontalOverflow: documentElement.scrollWidth > documentElement.clientWidth + 1,
            devicePixelRatio,
            widestVisibleElement: widestVisibleElement ? describeElement(widestVisibleElement) : null,
            overflowingElements}};
    });
};

test('CCB Batch 2F-A.1 preserves activity h1 and exposes contextual h2 at 100 percent', async() => {
    test.setTimeout(Number(process.env.EASYEDU_CCB_2FA_TIMEOUT || 300000));
    const env = environment();
    ensure(env.zoom === 100, 'Batch 2F-A.1 basic activity validation requires 100 percent browser zoom.');
    const runId = `${env.scenarioId}-${new Date().toISOString().replace(/[:.]/g, '-')}-${process.pid}`;
    const workspace = runWorkspace(env.artifactRoot, runId);
    const {root, profile, artifact} = workspace;
    const repository = repositoryState();
    const publicPolicy = protectedPublicPolicy(repository.pluginRoot);
    writeJson(artifact('ownership.json'), {marker: `${OWNERSHIP_MARKER}:2fa1`, runId, repository, publicPolicy, profile});
    const browserProcess = spawn('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', [
        `--remote-debugging-port=${env.port}`, '--remote-debugging-address=127.0.0.1', `--user-data-dir=${profile}`,
        '--no-first-run', '--no-default-browser-check', '--disable-save-password-bubble', '--disable-gpu',
        '--disable-features=CalculateNativeWinOcclusion', '--disable-backgrounding-occluded-windows', '--new-window',
        `${env.baseUrl}/login/index.php`,
    ], {stdio: 'ignore', windowsHide: false});
    let browser = null;
    let page = null;
    let originalCourse = null;
    let originalCourseTitle = null;
    let originalActivityTitle = null;
    let originalActivityBannersEnabled = null;
    let tracing = false;
    const consoleErrors = [];
    const cleanup = {courseRestored: false, categoryRestored: false, courseTitleRestored: false,
        activityBannersRestored: false, activityTitleRestored: false, zoomRestored: false,
        profileRemoved: false, error: null};
    try {
        for (let attempt = 0; attempt < 45 && !browser; attempt++) {
            try { browser = await chromium.connectOverCDP(`http://127.0.0.1:${env.port}`); } catch (error) {
                await new Promise(resolve => setTimeout(resolve, 250));
            }
        }
        ensure(browser, 'Dedicated Chrome CDP endpoint did not start.');
        const context = browser.contexts()[0];
        page = context.pages()[0] || await context.newPage();
        page.setDefaultTimeout(30000);
        page.setDefaultNavigationTimeout(45000);
        await context.tracing.start({screenshots: true, snapshots: true, sources: true});
        tracing = true;
        page.on('console', message => {
            if (message.type() === 'error') {
                consoleErrors.push(message.text());
            }
        });
        page.on('pageerror', error => consoleErrors.push(String(error)));
        await login(page, env);
        originalCourse = await courseState(page, env);
        if (String(originalCourse.category) !== '3') {
            ensure(String(originalCourse.category) === env.sourceCategoryId,
                `Unexpected fixture category ${originalCourse.category}.`);
            await updateCategory(page, env, '3');
            originalCourse = await courseState(page, env);
        }
        ensure(String(originalCourse.category) === '3', `Expected fixture category 3, found ${originalCourse.category}.`);
        originalCourseTitle = await formValues(await contextualTitleForm(page, env, 'course'));
        originalActivityTitle = await formValues(await contextualTitleForm(page, env, 'activity'));
        originalActivityBannersEnabled = await activityBannersEnabled(await activityBannersForm(page, env));
        writeJson(artifact('original-state.json'), {course: originalCourse,
            courseTitle: sanitiseTitleValues(originalCourseTitle), activityTitle: sanitiseTitleValues(originalActivityTitle),
            activityBannersEnabled: originalActivityBannersEnabled, zoom: 100});

        const longCourseTitle = 'Course context for accessible activity title continuity';
        await updateCategory(page, env, env.sourceCategoryId);
        await updateFullname(page, env, longCourseTitle);
        let activityBanners = await activityBannersForm(page, env);
        await setActivityBannersEnabled(activityBanners, page, true);
        expect(await activityBannersEnabled(await activityBannersForm(page, env))).toBe(true);
        const layerBaseline = await activityState(page, env);
        expect(layerBaseline.banner).not.toBeNull();

        let form = await contextualTitleForm(page, env, 'course');
        await setFormValues(form, {enabled: '1', replacemoodletitle: '1', frameenabled: '1', frametype: 'box'});
        await saveForm(form, page);
        const courseFixture = await formValues(await contextualTitleForm(page, env, 'course'));
        expect(courseFixture.enabled).toBe('1');
        expect(courseFixture.replacemoodletitle).toBe('1');
        form = await contextualTitleForm(page, env, 'activity');
        await setFormValues(form, {enabled: '0'});
        await saveForm(form, page);
        const activityFixture = await formValues(await contextualTitleForm(page, env, 'activity'));
        expect(activityFixture.enabled).toBe('0');

        consoleErrors.length = 0;
        const state = await activityState(page, env);
        const aria = await page.locator('body').ariaSnapshot();
        writeJson(artifact('activity-h1-h2-100.json'), {state, aria, consoleErrors});
        await page.screenshot({path: artifact('activity-h1-h2-100.png'), fullPage: true});
        const h1s = state.headings.filter(heading => heading.tag === 'H1' && !heading.hiddenAncestor &&
            heading.display !== 'none' && heading.visibility !== 'hidden');
        expect(h1s).toHaveLength(1);
        expect(h1s[0].text).not.toBe('');
        expect(state.pageHeader).not.toBeNull();
        expect(state.pageHeader.titleReplaced).toBe(false);
        expect(h1s[0].ariaHidden).toBeNull();
        const h2s = state.semanticTitles.filter(heading => !heading.hiddenAncestor && heading.display !== 'none' &&
            heading.visibility !== 'hidden');
        expect(h2s).toHaveLength(1);
        expect(h2s[0].semantic).toBe('secondary');
        expect(h2s[0].text).not.toBe('');
        expect(normalise(h2s[0].text)).toBe(normalise(`${longCourseTitle} ${h1s[0].text}`));
        expect(normalise(h2s[0].text)).not.toBe(normalise(h1s[0].text));
        expect(state.headings.findIndex(heading => heading.tag === 'H1')).toBeLessThan(
            state.headings.findIndex(heading => heading.tag === 'H2' && heading.semantic === 'secondary')
        );
        expect(aria.split(/\r?\n/).filter(line => /heading.*\[level=1\]/.test(line))).toHaveLength(1);
        expect(aria.split(/\r?\n/).filter(line => /heading.*\[level=2\]/.test(line) &&
            normalise(line).includes(normalise(longCourseTitle)))).toHaveLength(1);
        expect(state.headings.filter(heading => heading.tag === 'H2' && !heading.hiddenAncestor &&
            heading.display !== 'none' && normalise(heading.text).includes(normalise(longCourseTitle)))).toHaveLength(1);
        expect(state.banner).not.toBeNull();
        expect(state.banner.ariaHidden).toBeNull();
        expect(state.banner.overlays).toBe(layerBaseline.banner.overlays);
        expect(state.banner.borders).toBe(layerBaseline.banner.borders);
        expect(state.document.scrollWidth).toBeLessThanOrEqual(state.document.clientWidth + 1);
        expect(state.document.horizontalOverflow).toBe(false);
        expect(state.document.overflowingElements).toHaveLength(0);
        expect(state.hiddenFocusables).toHaveLength(0);
        expect(state.visualTitle).not.toBeNull();
        expect(state.visualTitle.ariaHidden).toBe('true');
        expect(state.visualTitle.decorative).toBe('1');
        expect(consoleErrors).toHaveLength(0);

        await page.goto(courseUrl(env), {waitUntil: 'domcontentloaded'});
        await page.waitForTimeout(1200);
        const courseSemanticTitles = await page.locator('h2[data-course-banner-builder-semantic-title="secondary"]').count();
        expect(courseSemanticTitles).toBe(0);
        expect(consoleErrors).toHaveLength(0);
    } catch (error) {
        if (page) { await page.screenshot({path: artifact('failure.png'), fullPage: true}).catch(() => {}); }
        writeJson(artifact('failure.json'), {error: String(error && error.stack || error), consoleErrors});
        throw error;
    } finally {
        if (page && originalActivityTitle) {
            try { const form = await contextualTitleForm(page, env, 'activity'); await setFormValues(form, originalActivityTitle);
                await saveForm(form, page); cleanup.activityTitleRestored = true; } catch (error) { cleanup.error = String(error); }
        }
        if (page && originalActivityBannersEnabled !== null) {
            try { const form = await activityBannersForm(page, env); await setActivityBannersEnabled(form, page, originalActivityBannersEnabled);
                cleanup.activityBannersRestored = true; } catch (error) { cleanup.error = cleanup.error || String(error); }
        }
        if (page && originalCourseTitle) {
            try { const form = await contextualTitleForm(page, env, 'course'); await setFormValues(form, originalCourseTitle);
                await saveForm(form, page); cleanup.courseTitleRestored = true; } catch (error) { cleanup.error = cleanup.error || String(error); }
        }
        if (page && originalCourse) {
            try { await updateFullname(page, env, originalCourse.fullname); await updateCategory(page, env, originalCourse.category); const restored = await courseState(page, env);
                cleanup.courseRestored = restored.fullname === originalCourse.fullname;
                cleanup.categoryRestored = String(restored.category) === String(originalCourse.category); } catch (error) { cleanup.error = cleanup.error || String(error); }
        }
        try { nativeZoom(browserProcess.pid, 'reset', artifact('cleanup-100-chrome.png')); cleanup.zoomRestored = true; } catch (error) { cleanup.error = cleanup.error || String(error); }
        if (tracing && browser) { await browser.contexts()[0].tracing.stop({path: artifact('trace.zip')}).catch(error => { cleanup.error = cleanup.error || String(error); }); }
        stopOwnedChromeProcessTree(browserProcess.pid);
        if (browser) { await browser.close().catch(() => {}); }
        try { fs.rmSync(profile, {recursive: true, force: true, maxRetries: 40, retryDelay: 250}); } catch (error) { cleanup.error = cleanup.error || String(error); }
        cleanup.profileRemoved = !fs.existsSync(profile);
        cleanup.completedAt = new Date().toISOString();
        writeJson(artifact('cleanup.json'), cleanup);
        if (Object.values(cleanup).some(value => value === false) || cleanup.error) { throw new Error(`Fixture/browser cleanup incomplete: ${JSON.stringify(cleanup)}.`); }
    }
});

test('CCB Batch 2F-B.1 exposes contextual h2 at narrow genuine 200 percent zoom', async() => {
    test.setTimeout(Number(process.env.EASYEDU_CCB_2FA_TIMEOUT || 300000));
    const env = environment();
    const runId = `${env.scenarioId}-${new Date().toISOString().replace(/[:.]/g, '-')}-${process.pid}`;
    const workspace = runWorkspace(env.artifactRoot, runId);
    const {root, profile, artifact} = workspace;
    const zoomProfile = `${profile}-zoom`;
    const repository = repositoryState();
    const publicPolicy = protectedPublicPolicy(repository.pluginRoot);
    writeJson(artifact('ownership.json'), {marker: `${OWNERSHIP_MARKER}:2fb1`, runId, repository, publicPolicy, profile});
    let browserProcess = spawn('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', [
        `--remote-debugging-port=${env.port}`, '--remote-debugging-address=127.0.0.1', `--user-data-dir=${profile}`,
        '--no-first-run', '--no-default-browser-check', '--disable-save-password-bubble', '--disable-gpu',
        '--disable-features=CalculateNativeWinOcclusion', '--disable-backgrounding-occluded-windows', '--new-window',
        `${env.baseUrl}/login/index.php`,
    ], {stdio: 'ignore', windowsHide: false});
    let browser = null;
    let page = null;
    let originalCourse = null;
    let originalCourseTitle = null;
    let originalActivityTitle = null;
    let originalActivityBannersEnabled = null;
    const runtimeErrors = [];
    const failedRequests = [];
    const ignoredFailedRequests = [];
    const cleanup = {courseRestored: false, categoryRestored: false, courseTitleRestored: false, activityBannersRestored: false,
        activityTitleRestored: false, sourceCategoryRemoved: false, zoomRestored: false, profileRemoved: false, error: null};
    try {
        for (let attempt = 0; attempt < 45 && !browser; attempt++) {
            try { browser = await chromium.connectOverCDP(`http://127.0.0.1:${env.port}`); } catch (error) {
                await new Promise(resolve => setTimeout(resolve, 250));
            }
        }
        ensure(browser, 'Dedicated Chrome CDP endpoint did not start.');
        let context = browser.contexts()[0];
        page = context.pages()[0] || await context.newPage();
        page.setDefaultTimeout(30000);
        page.setDefaultNavigationTimeout(45000);
        page.on('console', message => {
            if (message.type() === 'error') {
                runtimeErrors.push({type: 'console', text: sanitiseArtifactString(message.text())});
            }
        });
        page.on('pageerror', error => runtimeErrors.push({type: 'pageerror', text: sanitiseArtifactString(String(error))}));
        page.on('requestfailed', request => {
            const summary = sanitiseRequestSummary(request);
            const expectedSaveAbort = summary.failure === 'net::ERR_ABORTED' && summary.method === 'POST' &&
                /\/local\/course_banner_builder\/admin_manage\.php$/i.test(summary.url);
            if (expectedSaveAbort) {
                ignoredFailedRequests.push({...summary, reason: 'Moodle form save uses noWaitAfter; navigation abort is expected.'});
            } else {
                failedRequests.push(summary);
            }
        });
        await login(page, env);
        originalCourse = await courseState(page, env);
        if (String(originalCourse.category) !== '3') {
            ensure(String(originalCourse.category) === env.sourceCategoryId,
                `Unexpected fixture category ${originalCourse.category}.`);
            await updateCategory(page, env, '3');
            originalCourse = await courseState(page, env);
        }
        ensure(String(originalCourse.category) === '3', `Expected fixture category 3, found ${originalCourse.category}.`);
        originalCourseTitle = await formValues(await contextualTitleForm(page, env, 'course'));
        originalActivityTitle = await formValues(await contextualTitleForm(page, env, 'activity'));
        originalActivityBannersEnabled = await activityBannersEnabled(await activityBannersForm(page, env));
        writeJson(artifact('original-state.json'), {course: originalCourse,
            courseTitle: sanitiseTitleValues(originalCourseTitle), activityTitle: sanitiseTitleValues(originalActivityTitle),
            activityBannersEnabled: originalActivityBannersEnabled});

        const longCourseTitle = 'Contexte pédagogique détaillé pour validation accessibilité et continuité des apprentissages';
        await updateCategory(page, env, env.sourceCategoryId);
        expect(String((await courseState(page, env)).category)).toBe(env.sourceCategoryId);
        await updateFullname(page, env, longCourseTitle);
        let activityBanners = await activityBannersForm(page, env);
        await setActivityBannersEnabled(activityBanners, page, true);
        expect(await activityBannersEnabled(await activityBannersForm(page, env))).toBe(true);
        let form = await contextualTitleForm(page, env, 'course');
        await setFormValues(form, {enabled: '1', replacemoodletitle: '0', frameenabled: '1', frametype: 'box'});
        await saveForm(form, page);
        expect((await formValues(await contextualTitleForm(page, env, 'course'))).enabled).toBe('1');
        form = await contextualTitleForm(page, env, 'activity');
        await setFormValues(form, {enabled: '1', activitytitlemode: 'both', frameenabled: '1', frametype: 'box'});
        await saveForm(form, page);
        const activityFixture = await formValues(await contextualTitleForm(page, env, 'activity'));
        expect(activityFixture.enabled).toBe('1');
        expect(activityFixture.activitytitlemode).toBe('both');

        const before = await activityState(page, env);
        const ariaBefore = await page.locator('body').ariaSnapshot();
        const baselineEvidence = await cdpZoomEvidence(page, context);
        writeJson(artifact('contextual-h2-100.json'), {state: before, aria: ariaBefore, baselineEvidence, runtimeErrors, failedRequests, ignoredFailedRequests});
        await captureCdpPage(page, context, artifact('contextual-h2-100.png'));
        const h1s = before.headings.filter(heading => heading.tag === 'H1' && !heading.hiddenAncestor && heading.display !== 'none');
        const h2s = before.headings.filter(heading => heading.tag === 'H2' && !heading.hiddenAncestor && heading.display !== 'none');
        expect(h1s).toHaveLength(1);
        expect(h1s[0].text).not.toBe('');
        expect(h2s).toHaveLength(1);
        expect(normalise(h2s[0].text)).toBe(normalise(`${longCourseTitle} ${h1s[0].text}`));
        expect(h2s[0].ariaHidden).toBeNull();
        expect(h2s[0].visibility).not.toBe('hidden');
        expect(before.headings.findIndex(heading => heading.tag === 'H1')).toBeLessThan(before.headings.findIndex(heading => heading.tag === 'H2'));
        expect(ariaBefore.split(/\r?\n/).filter(line => /heading.*\[level=1\]/.test(line))).toHaveLength(1);
        expect(ariaBefore.split(/\r?\n/).filter(line => /heading.*\[level=2\]/.test(line) && normalise(line).includes(normalise(longCourseTitle)))).toHaveLength(1);
        expect(before.hiddenFocusables).toHaveLength(0);

        await page.locator('body').press('Tab');
        const firstFocus = await page.evaluate(() => ({tag: document.activeElement.tagName, text: (document.activeElement.textContent || '').trim(),
            hidden: !!document.activeElement.closest('[aria-hidden="true"]'), visible: document.activeElement.matches(':focus-visible')}));
        await page.locator('body').press('Shift+Tab');
        const reverseFocus = await page.evaluate(() => ({tag: document.activeElement.tagName, hidden: !!document.activeElement.closest('[aria-hidden="true"]')}));
        writeJson(artifact('keyboard-contextual.json'), {firstFocus, reverseFocus});
        await captureCdpPage(page, context, artifact('keyboard-contextual-focus.png'));
        expect(firstFocus.hidden).toBe(false);
        expect(firstFocus.visible).toBe(true);
        expect(reverseFocus.hidden).toBe(false);

        await browser.close().catch(() => {});
        stopOwnedChromeProcessTree(browserProcess.pid);
        browser = null;
        prepareNativeZoomProfile(zoomProfile, env.baseUrl, env.zoom);
        browserProcess = startDedicatedChrome(zoomProfile, env, env.port + 1);
        browser = await connectDedicatedChrome(env.port + 1);
        context = browser.contexts()[0];
        page = context.pages()[0] || await context.newPage();
        page.setDefaultTimeout(30000);
        page.setDefaultNavigationTimeout(45000);
        page.on('console', message => { if (message.type() === 'error') { runtimeErrors.push({type: 'console', text: sanitiseArtifactString(message.text())}); } });
        page.on('pageerror', error => runtimeErrors.push({type: 'pageerror', text: sanitiseArtifactString(String(error))}));
        page.on('requestfailed', request => {
            const summary = sanitiseRequestSummary(request);
            const expectedSaveAbort = summary.failure === 'net::ERR_ABORTED' && summary.method === 'POST' && /\/local\/course_banner_builder\/admin_manage\.php$/i.test(summary.url);
            (expectedSaveAbort ? ignoredFailedRequests : failedRequests).push(expectedSaveAbort ? {...summary, reason: 'Moodle form save uses noWaitAfter; navigation abort is expected.'} : summary);
        });
        await login(page, env);
        const zoomed = await activityState(page, env);
        const zoomAria = await page.locator('body').ariaSnapshot();
        const zoomH1s = zoomed.headings.filter(heading => heading.tag === 'H1' && !heading.hiddenAncestor && heading.display !== 'none');
        const zoomH2s = zoomed.headings.filter(heading => heading.tag === 'H2' && !heading.hiddenAncestor && heading.display !== 'none');
        const zoomedEvidence = await cdpZoomEvidence(page, context);
        const widthRatio = baselineEvidence.inPage.innerWidth / Math.max(zoomedEvidence.inPage.innerWidth, 1);
        const dprRatio = zoomedEvidence.inPage.devicePixelRatio / Math.max(baselineEvidence.inPage.devicePixelRatio, 1);
        writeJson(artifact('contextual-h2-narrow-200.json'), {state: zoomed, aria: zoomAria,
            zoom: {baselineEvidence, zoomedEvidence, widthRatio, dprRatio}, runtimeErrors, failedRequests, ignoredFailedRequests});
        await captureCdpPage(page, context, artifact('contextual-h2-narrow-200.png'));
        expect(widthRatio >= 1.9 || dprRatio >= 1.9).toBe(true);
        expect(zoomH1s).toHaveLength(1);
        expect(zoomH2s).toHaveLength(1);
        expect(normalise(zoomH2s[0].text)).toBe(normalise(h2s[0].text));
        expect(zoomed.document.scrollWidth).toBeLessThanOrEqual(zoomed.document.clientWidth + 1);
        expect(zoomed.document.horizontalOverflow).toBe(false);
        expect(zoomed.document.overflowingElements).toHaveLength(0);
        expect(zoomH2s[0].width).toBeGreaterThan(0);
        expect(zoomH2s[0].height).toBeGreaterThan(0);
        expect(zoomH2s[0].visibility).not.toBe('hidden');
        expect(zoomed.banner.height).toBeGreaterThanOrEqual(128);
        expect(zoomed.banner.overlays).toBe(before.banner.overlays);
        expect(zoomed.banner.borders).toBe(before.banner.borders);
        expect(zoomAria.split(/\r?\n/).filter(line => /heading.*\[level=1\]/.test(line))).toHaveLength(1);
        expect(zoomAria.split(/\r?\n/).filter(line => /heading.*\[level=2\]/.test(line) && normalise(line).includes(normalise(longCourseTitle)))).toHaveLength(1);
        expect(runtimeErrors).toHaveLength(0);
        expect(failedRequests).toHaveLength(0);
    } catch (error) {
        writeJson(artifact('failure.json'), {error: String(error && error.stack || error), runtimeErrors, failedRequests, ignoredFailedRequests});
        throw error;
    } finally {
        if (page && originalActivityTitle) {
            try { const form = await contextualTitleForm(page, env, 'activity'); await setFormValues(form, originalActivityTitle);
                await saveForm(form, page); cleanup.activityTitleRestored = true; } catch (error) { cleanup.error = String(error); }
        }
        if (page && originalActivityBannersEnabled !== null) {
            try { const form = await activityBannersForm(page, env); await setActivityBannersEnabled(form, page, originalActivityBannersEnabled);
                cleanup.activityBannersRestored = true; } catch (error) { cleanup.error = cleanup.error || String(error); }
        }
        if (page && originalCourseTitle) {
            try { const form = await contextualTitleForm(page, env, 'course'); await setFormValues(form, originalCourseTitle);
                await saveForm(form, page); cleanup.courseTitleRestored = true; } catch (error) { cleanup.error = cleanup.error || String(error); }
        }
        if (page && originalCourse) {
            try { await updateFullname(page, env, originalCourse.fullname); await updateCategory(page, env, originalCourse.category); const restored = await courseState(page, env);
                cleanup.courseRestored = restored.fullname === originalCourse.fullname;
                cleanup.categoryRestored = String(restored.category) === String(originalCourse.category); } catch (error) { cleanup.error = cleanup.error || String(error); }
        }
        try { deleteDisposableSourceCategoryBackend(repository.pluginRoot, env.sourceCategoryId); cleanup.sourceCategoryRemoved = true; }
        catch (error) { cleanup.error = cleanup.error || String(error); }
        stopOwnedChromeProcessTree(browserProcess.pid);
        if (browser) { await browser.close().catch(() => {}); }
        try { fs.rmSync(profile, {recursive: true, force: true, maxRetries: 40, retryDelay: 250}); fs.rmSync(zoomProfile, {recursive: true, force: true, maxRetries: 40, retryDelay: 250}); } catch (error) { cleanup.error = cleanup.error || String(error); }
        cleanup.profileRemoved = !fs.existsSync(profile) && !fs.existsSync(zoomProfile);
        cleanup.zoomRestored = cleanup.profileRemoved;
        cleanup.completedAt = new Date().toISOString();
        writeJson(artifact('cleanup.json'), cleanup);
        writeJson(artifact('artifact-summary.json'), {
            marker: `${OWNERSHIP_MARKER}:2fb1`,
            traceGenerated: false,
            sanitisation: 'central before write',
            runtimeErrors: runtimeErrors.length,
            failedRequests: failedRequests.length,
            ignoredExpectedSaveAborts: ignoredFailedRequests.length,
            cleanupComplete: !cleanup.error && Object.values(cleanup).every(value => value !== false),
            artifactBytes: directoryBytes(root),
        });
        if (Object.values(cleanup).some(value => value === false) || cleanup.error) { throw new Error(`Fixture/browser cleanup incomplete: ${JSON.stringify(cleanup)}.`); }
    }
});
