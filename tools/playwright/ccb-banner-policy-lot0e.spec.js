const {test, expect, chromium} = require('@playwright/test');
const fs = require('fs');
const path = require('path');
const os = require('os');
const crypto = require('crypto');
const {spawn, execFileSync} = require('child_process');

const protectedSpec = path.resolve(__dirname, 'ccb-banner-geometry.spec.js');
const required = [
    'EASYEDU_MOODLE_URL', 'EASYEDU_MOODLE_USERNAME', 'EASYEDU_MOODLE_PASSWORD',
    'EASYEDU_CCB_FIXTURE_COURSE_ID', 'EASYEDU_CCB_FIXTURE_COURSE_SHORTNAME',
    'EASYEDU_CCB_LOT0E_SCENARIO_ID', 'EASYEDU_CCB_LOT0E_VIEWPORT_WIDTH',
    'EASYEDU_CCB_LOT0E_VIEWPORT_HEIGHT', 'EASYEDU_CCB_LOT0E_ZOOM',
    'EASYEDU_CCB_LOT0E_FLOOR', 'EASYEDU_CCB_ARTIFACT_ROOT',
    'EASYEDU_CCB_LOT0E_ORIGINAL_CATEGORY', 'EASYEDU_CCB_LOT0E_TEMPORARY_CATEGORY',
    'EASYEDU_CCB_LOT0E_FORMAT',
];

const environment = () => {
    const missing = required.filter(name => !process.env[name]);
    if (missing.length) {
        throw new Error(`Missing Lot 0E environment values: ${missing.join(', ')}`);
    }
    const numeric = name => {
        const value = Number(process.env[name]);
        if (!Number.isFinite(value) || value <= 0) {
            throw new Error(`${name} must be a positive number.`);
        }
        return value;
    };
    const zoom = numeric('EASYEDU_CCB_LOT0E_ZOOM');
    const floor = numeric('EASYEDU_CCB_LOT0E_FLOOR');
    if (![100, 200].includes(zoom)) throw new Error('EASYEDU_CCB_LOT0E_ZOOM must be 100 or 200.');
    if (![128, 136, 144, 152].includes(floor)) throw new Error('EASYEDU_CCB_LOT0E_FLOOR must be 128, 136, 144 or 152.');
    const viewport = {width: numeric('EASYEDU_CCB_LOT0E_VIEWPORT_WIDTH'), height: numeric('EASYEDU_CCB_LOT0E_VIEWPORT_HEIGHT')};
    const scenarioId = process.env.EASYEDU_CCB_LOT0E_SCENARIO_ID;
    if (!/^[a-z0-9][a-z0-9-]{2,80}$/.test(scenarioId)) throw new Error('Scenario id must be a simple lowercase slug.');
    if (String(process.env.EASYEDU_CCB_FIXTURE_COURSE_ID) !== '2') throw new Error('Lot 0E is restricted to course id 2.');
    return {
        baseUrl: process.env.EASYEDU_MOODLE_URL.replace(/\/$/, ''),
        username: process.env.EASYEDU_MOODLE_USERNAME,
        password: process.env.EASYEDU_MOODLE_PASSWORD,
        courseId: process.env.EASYEDU_CCB_FIXTURE_COURSE_ID,
        shortname: process.env.EASYEDU_CCB_FIXTURE_COURSE_SHORTNAME,
        scenarioId, viewport, zoom, floor,
        originalCategory: process.env.EASYEDU_CCB_LOT0E_ORIGINAL_CATEGORY,
        temporaryCategory: process.env.EASYEDU_CCB_LOT0E_TEMPORARY_CATEGORY,
        format: process.env.EASYEDU_CCB_LOT0E_FORMAT,
        artifactRoot: path.resolve(process.env.EASYEDU_CCB_ARTIFACT_ROOT),
        port: Number(process.env.EASYEDU_CCB_LOT0E_PORT || 9351),
    };
};

const sha256 = file => crypto.createHash('sha256').update(fs.readFileSync(file)).digest('hex');
const writeAtomic = (file, value) => {
    fs.mkdirSync(path.dirname(file), {recursive: true});
    const temporary = `${file}.${process.pid}.tmp`;
    fs.writeFileSync(temporary, JSON.stringify(value, null, 2));
    fs.renameSync(temporary, file);
};
const rect = element => {
    if (!element) return null;
    const box = element.getBoundingClientRect();
    return {x: box.x, y: box.y, width: box.width, height: box.height, right: box.right, bottom: box.bottom};
};

const login = async(page, env) => {
    await page.goto(`${env.baseUrl}/login/index.php`, {waitUntil: 'domcontentloaded'});
    await page.locator('#username').fill(env.username);
    await page.locator('#password').fill(env.password);
    await page.locator('#loginbtn').click();
    await expect(page).not.toHaveURL(/\/login\//, {timeout: 15000});
};

const openCourseEdit = async(page, env) => {
    await page.goto(`${env.baseUrl}/course/view.php?id=${env.courseId}`, {waitUntil: 'domcontentloaded'});
    const href = await page.locator('a').evaluateAll(links => {
        const link = links.find(item => (item.textContent || '').trim() === 'Settings' && item.href.includes('/course/edit.php'));
        return link ? link.href : null;
    });
    if (!href) throw new Error('Supported Moodle course Settings link was not found.');
    await page.goto(href, {waitUntil: 'domcontentloaded'});
};

const readCourseState = async(page, env) => {
    await openCourseEdit(page, env);
    const category = page.locator('#id_category');
    const fullname = page.locator('#id_fullname');
    await expect(category).toHaveCount(1);
    await expect(fullname).toHaveCount(1);
    return {id: await category.inputValue(), label: await category.locator('option:checked').textContent(), fullname: await fullname.inputValue()};
};

const updateCategory = async(page, env, categoryId) => {
    await openCourseEdit(page, env);
    const field = page.locator('#id_category');
    await field.evaluate((select, value) => {
        select.value = String(value);
        select.dispatchEvent(new Event('change', {bubbles: true}));
    }, categoryId);
    const submit = page.locator('#id_saveanddisplay');
    await expect(submit).toHaveCount(1);
    await Promise.all([
        page.waitForURL(url => url.pathname.endsWith('/course/view.php'), {timeout: 15000}).catch(() => null),
        submit.evaluate(button => button.form.requestSubmit(button)),
    ]);
    await page.waitForTimeout(500);
};

const updateFullname = async(page, env, fullname) => {
    await openCourseEdit(page, env);
    await page.locator('#id_fullname').fill(fullname);
    await page.locator('#id_saveanddisplay').evaluate(button => button.form.requestSubmit(button));
    await page.waitForTimeout(500);
};

const openTitleForm = async(page, env) => {
    await page.goto(`${env.baseUrl}/local/course_banner_builder/admin_manage.php`, {waitUntil: 'domcontentloaded'});
    const form = page.locator('form[data-banner-title-editor]').first();
    await expect(form).toHaveCount(1);
    return form;
};

const namedValues = async form => form.evaluate(element => Object.fromEntries(Array.from(element.elements)
    .filter(control => control.name)
    .map(control => [control.name, control.type === 'checkbox' ? (control.checked ? '1' : '0') : control.value])));

const setNamedValues = async(form, values) => form.evaluate((element, next) => {
    Object.entries(next).forEach(([name, value]) => {
        const control = Array.from(element.elements).find(item => item.name === name);
        if (!control) return;
        if (control.type === 'checkbox') control.checked = value === '1' || value === true;
        else control.value = String(value);
        control.dispatchEvent(new Event('change', {bubbles: true}));
    });
}, values);

const saveForm = async(form, page) => {
    await form.evaluate(element => element.requestSubmit());
    await page.waitForTimeout(700);
};

const getFormat = async(page, env) => {
    await page.goto(`${env.baseUrl}/local/course_banner_builder/admin_manage.php`, {waitUntil: 'domcontentloaded'});
    await page.locator('.local-course-banner-builder-admin-format-button').first().click();
    const modal = page.locator('.modal.show').last();
    await expect(modal).toBeVisible();
    return modal.locator('input[name="bannerformat"]:checked').inputValue();
};

const setFormat = async(page, env, format) => {
    await page.goto(`${env.baseUrl}/local/course_banner_builder/admin_manage.php`, {waitUntil: 'domcontentloaded'});
    await page.locator('.local-course-banner-builder-admin-format-button').first().click();
    const modal = page.locator('.modal.show').last();
    await expect(modal).toBeVisible();
    const input = modal.locator(`input[name="bannerformat"][value="${format}"]`);
    await expect(input).toHaveCount(1);
    await input.evaluate(element => element.click());
    await modal.locator('button[type="submit"]').click();
    await expect(modal).toBeHidden({timeout: 15000});
};

const nativeZoom = (pid, operation, screenshot) => {
    const keys = operation === 'reset' ? "'^0'" : "'^{+}'";
    const count = operation === 'reset' ? 1 : 6;
    const safe = screenshot.replace(/'/g, "''");
    const command = `$ErrorActionPreference='Stop'; $window=Get-Process -Id ${pid} -ErrorAction Stop; if($window.MainWindowHandle -eq 0){throw 'Dedicated Chrome has no visible window.'}; ` +
        `$shell=New-Object -ComObject WScript.Shell; if(-not $shell.AppActivate($window.Id)){throw 'Dedicated Chrome activation failed.'}; ` +
        `Start-Sleep -Milliseconds 300; 1..${count} | ForEach-Object {$shell.SendKeys(${keys}); Start-Sleep -Milliseconds 180}; Start-Sleep -Milliseconds 600; ` +
        `Add-Type -AssemblyName System.Windows.Forms; Add-Type -AssemblyName System.Drawing; $b=[System.Windows.Forms.Screen]::PrimaryScreen.Bounds; $bmp=New-Object System.Drawing.Bitmap $b.Width,$b.Height; ` +
        `$g=[System.Drawing.Graphics]::FromImage($bmp); $g.CopyFromScreen($b.Location,[System.Drawing.Point]::Empty,$b.Size); $bmp.Save('${safe}'); $g.Dispose(); $bmp.Dispose(); Write-Output ($window.Id.ToString()+'|'+$window.MainWindowHandle.ToString()+'|'+$window.MainWindowTitle)`;
    return execFileSync('powershell.exe', ['-NoProfile', '-NonInteractive', '-Command', command], {encoding: 'utf8', windowsHide: true}).trim();
};

const themeMetrics = page => page.evaluate(() => ({
    innerWidth: window.innerWidth, innerHeight: window.innerHeight, outerWidth: window.outerWidth, outerHeight: window.outerHeight,
    devicePixelRatio: window.devicePixelRatio,
    visualViewport: window.visualViewport ? {width: window.visualViewport.width, height: window.visualViewport.height, scale: window.visualViewport.scale} : null,
}));

test('CCB Lot 0E isolated policy scenario', async({}, testInfo) => {
    test.setTimeout(Number(process.env.EASYEDU_CCB_LOT0E_TIMEOUT || 240000));
    const env = environment();
    if (env.originalCategory !== '3' || env.temporaryCategory !== '8') throw new Error('Lot 0E category parameters must be original 3 and temporary 8.');
    if (!['contentwide', 'fullwidthtop', 'fullwidthtopcompact', 'fullwidthtopinset'].includes(env.format)) throw new Error('Lot 0E format must be a non-standard CCB format.');
    const formatRatio = {contentwide: 5, fullwidthtop: 5, fullwidthtopcompact: 8, fullwidthtopinset: 6.1}[env.format];
    const runRoot = path.join(env.artifactRoot, env.scenarioId);
    if (fs.existsSync(runRoot)) throw new Error(`Scenario artifact directory already exists: ${runRoot}`);
    fs.mkdirSync(runRoot, {recursive: true});
    const checkpointRoot = path.join(runRoot, 'checkpoints');
    const artifact = name => path.join(runRoot, name);
    const fileHash = () => ({protectedSpec: sha256(protectedSpec), batchSpec: sha256(__filename)});
    const viewportName = env.viewport.width === 390 ? 'mobile' : 'desktop';
    const checkpoint = async(stage, page, extra = {}) => {
        let surface = {url: page ? page.url() : null, wrapperCount: null, overlayCount: null};
        if (page) surface = await page.evaluate(() => ({
            url: location.href,
            wrapperCount: document.querySelectorAll('.local-course-banner-builder-native-course-banner').length,
            overlayCount: document.querySelectorAll('.local-course-banner-builder-fixed-overlay').length,
        })).catch(() => surface);
        writeAtomic(path.join(checkpointRoot, `${stage}.json`), {
            timestamp: new Date().toISOString(), scenarioId: env.scenarioId, viewport: env.viewport, requestedZoom: env.zoom,
            floorCandidate: env.floor, currentUrl: surface.url, fixtureCategory: extra.fixtureCategory ?? null,
            bannerWrapperCount: surface.wrapperCount, overlayCount: surface.overlayCount, hashes: fileHash(), artifacts: {root: runRoot},
            ...extra,
        });
    };
    const browserProcess = spawn('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', [
        `--remote-debugging-port=${env.port}`, '--remote-debugging-address=127.0.0.1', `--user-data-dir=${fs.mkdtempSync(path.join(os.tmpdir(), `ccb-lot0e-${env.scenarioId}-`))}`,
        '--no-first-run', '--no-default-browser-check', '--disable-gpu',
        '--disable-features=CalculateNativeWinOcclusion', '--disable-backgrounding-occluded-windows',
        '--new-window', `${env.baseUrl}/login/index.php`,
    ], {stdio: 'ignore', windowsHide: false});
    let browser = null;
    let page = null;
    let original = null;
    let originalTitleSettings = null;
    let originalFormat = null;
    let traceStarted = false;
    const cleanup = {zoom100: false, category3: false, title: false, titleSettings: false, format: false, error: null};
    try {
        await checkpoint('started', null, {fixtureCategory: null});
        let lastError = null;
        for (let attempt = 0; attempt < 40 && !browser; attempt++) {
            try { browser = await chromium.connectOverCDP(`http://127.0.0.1:${env.port}`); } catch (error) { lastError = error; await new Promise(resolve => setTimeout(resolve, 250)); }
        }
        if (!browser) throw lastError || new Error('Dedicated Chrome CDP endpoint did not start.');
        const context = browser.contexts()[0];
        page = context.pages()[0] || await context.newPage();
        await context.tracing.start({screenshots: true, snapshots: true, sources: true});
        traceStarted = true;
        await login(page, env);
        await checkpoint('authenticated', page);
        original = await readCourseState(page, env);
        originalTitleSettings = await namedValues(await openTitleForm(page, env));
        originalFormat = await getFormat(page, env);
        writeAtomic(artifact('original-state.json'), {course: original, titleSettings: Object.fromEntries(Object.entries(originalTitleSettings).map(([name, value]) => [name, name === 'sesskey' ? '[redacted]' : value])), format: originalFormat});
        await checkpoint('original-state-captured', page, {fixtureCategory: original.id, original});
        if (String(original.id) !== env.originalCategory) { await updateCategory(page, env, env.originalCategory); }
        await updateCategory(page, env, env.temporaryCategory);
        await checkpoint('category-move-submitted', page, {fixtureCategory: env.temporaryCategory});
        const selected = await readCourseState(page, env);
        if (String(selected.id) !== env.temporaryCategory) throw new Error(`Category 8 was not selected; found ${selected.id}.`);
        await checkpoint('category-8-confirmed', page, {fixtureCategory: env.temporaryCategory, selected});
        await updateFullname(page, env, 'Evaluation pedagogique inclusive : cooperation internationale, accessibilite numerique et reussite durable des apprenants');
        const titleForm = await openTitleForm(page, env);
        await setNamedValues(titleForm, {enabled: '1', replacemoodletitle: '0', frameenabled: '1', frametype: 'box', framepadding: '18', fontsize: '100', lineheight: '110'});
        await saveForm(titleForm, page);
        await setFormat(page, env, env.format);
        await page.goto(`${env.baseUrl}/course/view.php?id=${env.courseId}`, {waitUntil: 'domcontentloaded'});
        const banner = page.locator('.local-course-banner-builder-native-course-banner').first();
        const overlays = page.locator('.local-course-banner-builder-fixed-overlay');
        await expect(banner).toBeVisible({timeout: 15000});
        await expect(overlays.first()).toBeVisible({timeout: 15000});
        const publicGate = await banner.evaluate(element => { const r = element.getBoundingClientRect(); return {width: r.width, height: r.height, className: element.className}; });
        if (publicGate.width <= 0 || publicGate.height <= 0) throw new Error('Public CCB banner rectangle is empty.');
        await checkpoint('public-page-loaded', page, {fixtureCategory: env.temporaryCategory, publicGate});
        await page.screenshot({path: artifact('public-wrapper-baseline.png'), fullPage: true});
        await checkpoint('public-wrapper-confirmed', page, {fixtureCategory: env.temporaryCategory});
        await checkpoint('public-overlays-confirmed', page, {fixtureCategory: env.temporaryCategory, overlayCount: await overlays.count()});
        await page.setViewportSize(env.viewport);
        const baselineMetrics = await themeMetrics(page);
        const baselineScreenshot = artifact('baseline-100.png');
        await page.screenshot({path: baselineScreenshot, fullPage: true});
        await checkpoint('baseline-captured', page, {fixtureCategory: env.temporaryCategory, baselineMetrics, screenshot: baselineScreenshot});
        if (env.zoom === 200) {
            const before = await themeMetrics(page);
            const nativeEvidence = nativeZoom(browserProcess.pid, 'zoom', artifact('zoom-200-chrome.png'));
            await checkpoint('zoom-command-sent', page, {fixtureCategory: env.temporaryCategory, nativeEvidence});
            await page.waitForTimeout(700);
            const after = await themeMetrics(page);
            const widthRatio = before.innerWidth / Math.max(after.innerWidth, 1);
            const achieved = after.devicePixelRatio / Math.max(before.devicePixelRatio, 1) >= 1.9 || widthRatio >= 1.9;
            writeAtomic(artifact('zoom-200-metrics.json'), {before, after, widthRatio, achieved});
            await page.screenshot({path: artifact('zoom-200-page.png'), fullPage: true});
            await checkpoint('zoom-confirmed', page, {fixtureCategory: env.temporaryCategory, before, after, widthRatio, achieved});
            if (!achieved) throw new Error('Native 200% zoom was not proven on the controlled CCB window.');
        } else {
            await checkpoint('zoom-command-sent', page, {fixtureCategory: env.temporaryCategory, skipped: 'requested zoom is 100%'});
            await checkpoint('zoom-confirmed', page, {fixtureCategory: env.temporaryCategory, achieved: true, zoom: 100});
        }
        const injection = `height = max(width / ${formatRatio}, ${env.floor}px); min-height: 0; max-height: none;`;
        const baselineOverlayGeometry = await page.evaluate(() => {
            const banner = document.querySelector('.local-course-banner-builder-native-course-banner');
            const box = banner.getBoundingClientRect();
            return Array.from(document.querySelectorAll('.local-course-banner-builder-fixed-overlay')).map(element => {
                const rect = element.getBoundingClientRect();
                return {left: (rect.left - box.left) / box.width, top: (rect.top - box.top) / box.height, width: rect.width / box.width, height: rect.height / box.height};
            });
        });
        const measurement = await banner.evaluate((element, input) => {
            const width = element.getBoundingClientRect().width;
            const naturalHeight = width / input.ratio;
            const height = Math.max(naturalHeight, input.floor);
            element.style.setProperty('min-height', '0px', 'important');
            element.style.setProperty('max-height', 'none', 'important');
            element.style.setProperty('height', `${height}px`, 'important');
            return {width, naturalHeight, height};
        }, {floor: env.floor, ratio: formatRatio});
        await checkpoint('candidate-applied', page, {fixtureCategory: env.temporaryCategory, injection, measurement});
        await page.waitForTimeout(100);
        const measured = await page.evaluate((baselineOverlayGeometry) => {
            const banner = document.querySelector('.local-course-banner-builder-native-course-banner');
            const title = document.querySelector('.local-course-banner-builder-banner-title-overlay');
            const frame = title ? title.querySelector(':scope > span') : null;
            const bannerBox = banner.getBoundingClientRect();
            const titleBox = title ? title.getBoundingClientRect() : null;
            const frameBox = frame ? frame.getBoundingClientRect() : null;
            const overlays = Array.from(document.querySelectorAll('.local-course-banner-builder-fixed-overlay')).map(element => {
                const box = element.getBoundingClientRect();
                const style = getComputedStyle(element);
                const image = element.querySelector('img');
                const clippingAncestors = [];
                let ancestor = element.parentElement;
                while (ancestor && clippingAncestors.length < 8) {
                    const ancestorStyle = getComputedStyle(ancestor);
                    if (/(hidden|clip|auto|scroll)/.test(`${ancestorStyle.overflow} ${ancestorStyle.overflowX} ${ancestorStyle.overflowY}`)) {
                        const ancestorBox = ancestor.getBoundingClientRect();
                        clippingAncestors.push({className: ancestor.className || null, overflow: ancestorStyle.overflow, box: {x: ancestorBox.x, y: ancestorBox.y, width: ancestorBox.width, height: ancestorBox.height}});
                    }
                    ancestor = ancestor.parentElement;
                }
                return {box: {x: box.x, y: box.y, width: box.width, height: box.height}, normalised: {left: (box.left - bannerBox.left) / bannerBox.width, top: (box.top - bannerBox.top) / bannerBox.height, width: box.width / bannerBox.width, height: box.height / bannerBox.height}, opacity: style.opacity, zIndex: style.zIndex, overflow: style.overflow, clippingAncestors, image: image ? {objectPosition: getComputedStyle(image).objectPosition, objectFit: getComputedStyle(image).objectFit} : null};
            });
            const navigation = document.querySelector('#page-navbar, nav[aria-label*="breadcrumb" i]');
            const navigationBox = navigation ? navigation.getBoundingClientRect() : null;
            const titleEdges = titleBox ? {top: titleBox.top - bannerBox.top, bottom: bannerBox.bottom - titleBox.bottom, left: titleBox.left - bannerBox.left, right: bannerBox.right - titleBox.right} : null;
            const range = title ? document.createRange() : null;
            if (range) range.selectNodeContents(title);
            const tops = range ? Array.from(range.getClientRects()).map(rect => Math.round(rect.top * 10) / 10).filter((top, i, all) => all.indexOf(top) === i) : [];
            const overflow = Math.max(0, document.documentElement.scrollWidth - document.documentElement.clientWidth);
            return {banner: {box: {x: bannerBox.x, y: bannerBox.y, width: bannerBox.width, height: bannerBox.height}, effectiveRatio: bannerBox.width / bannerBox.height, viewportShare: bannerBox.height / window.innerHeight, overflow: getComputedStyle(banner).overflow, minHeight: getComputedStyle(banner).minHeight, maxHeight: getComputedStyle(banner).maxHeight}, title: titleBox ? {box: {x: titleBox.x, y: titleBox.y, width: titleBox.width, height: titleBox.height}, frame: frameBox ? {x: frameBox.x, y: frameBox.y, width: frameBox.width, height: frameBox.height} : null, lineCount: tops.length, clientWidth: title.clientWidth, scrollWidth: title.scrollWidth, clientHeight: title.clientHeight, scrollHeight: title.scrollHeight, lineHeight: getComputedStyle(title).lineHeight, edges: titleEdges, navigationCollision: !!(navigationBox && titleBox && titleBox.top < navigationBox.bottom && titleBox.bottom > navigationBox.top)} : null, overlays, baselineOverlayGeometry, document: {clientWidth: document.documentElement.clientWidth, scrollWidth: document.documentElement.scrollWidth, horizontalOverflow: overflow, viewportHeight: window.innerHeight, navigation: navigationBox ? {x: navigationBox.x, y: navigationBox.y, width: navigationBox.width, height: navigationBox.height} : null}};
        }, baselineOverlayGeometry);
        writeAtomic(artifact('measurement.json'), {scenarioId: env.scenarioId, viewport: env.viewport, zoom: env.zoom, floor: env.floor, injection, baselineMetrics, measured});
        await checkpoint('measurements-written', page, {fixtureCategory: env.temporaryCategory, measurement: measured});
    } catch (error) {
        writeAtomic(artifact('failure.json'), {error: String(error && error.stack || error), hashes: fileHash()});
        await checkpoint('failure', page, {error: String(error && error.stack || error)}).catch(() => {});
        throw error;
    } finally {
        await checkpoint('cleanup-started', page, {fixtureCategory: env.temporaryCategory}).catch(() => {});
        if (page) {
            try { nativeZoom(browserProcess.pid, 'reset', artifact('cleanup-100-chrome.png')); cleanup.zoom100 = true; } catch (error) { cleanup.error = String(error); }
            await checkpoint('zoom-100-restored', page, {cleanup}).catch(() => {});
            try {
                if (originalTitleSettings) { const form = await openTitleForm(page, env); await setNamedValues(form, originalTitleSettings); await saveForm(form, page); cleanup.titleSettings = true; }
                if (original && original.fullname) { await updateFullname(page, env, original.fullname); cleanup.title = true; }
                if (originalFormat) { await setFormat(page, env, originalFormat); cleanup.format = await getFormat(page, env) === originalFormat; }
                await updateCategory(page, env, env.originalCategory);
                cleanup.category3 = String((await readCourseState(page, env)).id) === env.originalCategory;
            } catch (error) { cleanup.error = String(error && error.stack || error); }
            await checkpoint('category-3-restored', page, {fixtureCategory: env.originalCategory, cleanup}).catch(() => {});
        }
        if (traceStarted && browser) {
            try { await browser.contexts()[0].tracing.stop({path: artifact('trace.zip')}); } catch (error) { cleanup.error = String(error); }
        }
        writeAtomic(artifact('cleanup.json'), cleanup);
        await checkpoint('trace-written', page, {cleanup, trace: artifact('trace.zip')}).catch(() => {});
        await checkpoint('completed', page, {fixtureCategory: env.originalCategory, cleanup, hashes: fileHash()}).catch(() => {});
        if (browser) await browser.close().catch(() => {});
        if (!browserProcess.killed) browserProcess.kill();
    }
});
