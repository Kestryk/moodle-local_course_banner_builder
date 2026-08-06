const {test, expect, chromium} = require('@playwright/test');
const fs = require('fs');
const path = require('path');
const os = require('os');
const {spawn, execFileSync} = require('child_process');

const REQUIRED = [
    'EASYEDU_MOODLE_URL', 'EASYEDU_MOODLE_USERNAME', 'EASYEDU_MOODLE_PASSWORD',
    'EASYEDU_CCB_FIXTURE_COURSE_ID', 'EASYEDU_CCB_2EA_SCENARIO_ID',
    'EASYEDU_CCB_2EA_VIEWPORT_WIDTH', 'EASYEDU_CCB_2EA_VIEWPORT_HEIGHT',
    'EASYEDU_CCB_2EA_ZOOM', 'EASYEDU_CCB_2EA_FORMAT',
    'EASYEDU_CCB_2EA_ARTIFACT_ROOT', 'EASYEDU_CCB_2EA_ORIGINAL_CATEGORY',
    'EASYEDU_CCB_2EA_TEMPORARY_CATEGORY',
];
const POLICY = {
    standard: {ratio: 4, min: 0, max: null},
    contentwide: {ratio: 5, min: 128, max: 280},
    fullwidthtop: {ratio: 5, min: 128, max: 360},
    fullwidthtopcompact: {ratio: 8, min: 128, max: 210},
    fullwidthtopinset: {ratio: 6.1, min: 128, max: 300},
};

const environment = () => {
    const missing = REQUIRED.filter(name => !process.env[name]);
    if (missing.length) throw new Error(`Missing Batch 2E-A.1 environment values: ${missing.join(', ')}`);
    const number = name => {
        const value = Number(process.env[name]);
        if (!Number.isFinite(value) || value <= 0) throw new Error(`${name} must be a positive number.`);
        return value;
    };
    const format = process.env.EASYEDU_CCB_2EA_FORMAT;
    if (!Object.hasOwn(POLICY, format)) throw new Error(`Unsupported CCB format: ${format}.`);
    const zoom = number('EASYEDU_CCB_2EA_ZOOM');
    if (![100, 200].includes(zoom)) throw new Error('EASYEDU_CCB_2EA_ZOOM must be 100 or 200.');
    const scenarioId = process.env.EASYEDU_CCB_2EA_SCENARIO_ID;
    if (!/^[a-z0-9][a-z0-9-]{2,80}$/.test(scenarioId)) throw new Error('Scenario id must be a lowercase slug.');
    if (String(process.env.EASYEDU_CCB_FIXTURE_COURSE_ID) !== '2') throw new Error('Batch 2E-A.1 is restricted to course id 2.');
    if (process.env.EASYEDU_CCB_2EA_ORIGINAL_CATEGORY !== '3' || process.env.EASYEDU_CCB_2EA_TEMPORARY_CATEGORY !== '8') {
        throw new Error('Batch 2E-A.1 category values must be original 3 and temporary 8.');
    }
    return {
        baseUrl: process.env.EASYEDU_MOODLE_URL.replace(/\/$/, ''),
        username: process.env.EASYEDU_MOODLE_USERNAME,
        password: process.env.EASYEDU_MOODLE_PASSWORD,
        courseId: process.env.EASYEDU_CCB_FIXTURE_COURSE_ID,
        scenarioId, format, zoom, policy: POLICY[format],
        viewport: {width: number('EASYEDU_CCB_2EA_VIEWPORT_WIDTH'), height: number('EASYEDU_CCB_2EA_VIEWPORT_HEIGHT')},
        originalCategory: process.env.EASYEDU_CCB_2EA_ORIGINAL_CATEGORY,
        temporaryCategory: process.env.EASYEDU_CCB_2EA_TEMPORARY_CATEGORY,
        artifactRoot: path.resolve(process.env.EASYEDU_CCB_2EA_ARTIFACT_ROOT),
        port: Number(process.env.EASYEDU_CCB_2EA_PORT || 9371),
    };
};

const writeJson = (file, value) => {
    fs.mkdirSync(path.dirname(file), {recursive: true});
    fs.writeFileSync(file, JSON.stringify(value, null, 2));
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
    if (!href) throw new Error('Moodle course Settings link was not found.');
    await page.goto(href, {waitUntil: 'domcontentloaded'});
};
const courseState = async(page, env) => {
    await openCourseEdit(page, env);
    const category = page.locator('#id_category');
    const fullname = page.locator('#id_fullname');
    await expect(category).toHaveCount(1, {timeout: 15000});
    return {category: await category.inputValue(), fullname: await fullname.inputValue()};
};
const updateCategory = async(page, env, categoryId) => {
    await openCourseEdit(page, env);
    await page.locator('#id_category').evaluate((field, value) => {
        field.value = String(value);
        field.dispatchEvent(new Event('change', {bubbles: true}));
    }, categoryId);
    const submit = page.locator('#id_saveanddisplay');
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
const titleForm = async(page, env) => {
    await page.goto(`${env.baseUrl}/local/course_banner_builder/admin_manage.php`, {waitUntil: 'domcontentloaded'});
    const form = page.locator('form[data-banner-title-editor]').first();
    await expect(form).toHaveCount(1);
    return form;
};
const formValues = form => form.evaluate(element => Object.fromEntries(Array.from(element.elements)
    .filter(control => control.name)
    .map(control => [control.name, control.type === 'checkbox' ? (control.checked ? '1' : '0') : control.value])));
const setFormValues = (form, values) => form.evaluate((element, next) => {
    Object.entries(next).forEach(([name, value]) => {
        const control = Array.from(element.elements).find(item => item.name === name);
        if (!control) return;
        if (control.type === 'checkbox') control.checked = value === '1' || value === true;
        else control.value = String(value);
        control.dispatchEvent(new Event('change', {bubbles: true}));
    });
}, values);
const saveForm = async(form, page) => { await form.evaluate(element => element.requestSubmit()); await page.waitForTimeout(700); };
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

test('CCB Batch 2E-A.1 public runtime and compiled sizing agree', async() => {
    test.setTimeout(Number(process.env.EASYEDU_CCB_2EA_TIMEOUT || 240000));
    const env = environment();
    const root = path.join(env.artifactRoot, env.scenarioId);
    if (fs.existsSync(root)) throw new Error(`Scenario artifact directory already exists: ${root}`);
    fs.mkdirSync(root, {recursive: true});
    const artifact = name => path.join(root, name);
    const checkpoint = (stage, extra = {}) => writeJson(artifact(`checkpoint-${stage}.json`), {
        timestamp: new Date().toISOString(), scenarioId: env.scenarioId, format: env.format,
        viewport: env.viewport, requestedZoom: env.zoom, ...extra,
    });
    const browserProcess = spawn('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', [
        `--remote-debugging-port=${env.port}`, '--remote-debugging-address=127.0.0.1',
        `--user-data-dir=${fs.mkdtempSync(path.join(os.tmpdir(), `ccb-2ea-${env.scenarioId}-`))}`,
        '--no-first-run', '--no-default-browser-check', '--disable-gpu',
        '--disable-features=CalculateNativeWinOcclusion', '--disable-backgrounding-occluded-windows',
        '--new-window', `${env.baseUrl}/login/index.php`,
    ], {stdio: 'ignore', windowsHide: false});
    let browser = null;
    let page = null;
    let tracing = false;
    let original = null;
    let originalTitle = null;
    let originalFormat = null;
    const cleanup = {zoom100: false, category3: false, title: false, titleSettings: false, format: false, error: null};
    try {
        let lastError = null;
        for (let attempt = 0; attempt < 40 && !browser; attempt++) {
            try { browser = await chromium.connectOverCDP(`http://127.0.0.1:${env.port}`); }
            catch (error) { lastError = error; await new Promise(resolve => setTimeout(resolve, 250)); }
        }
        if (!browser) throw lastError || new Error('Dedicated Chrome CDP endpoint did not start.');
        const context = browser.contexts()[0];
        page = context.pages()[0] || await context.newPage();
        await context.tracing.start({screenshots: true, snapshots: true, sources: true});
        tracing = true;
        await login(page, env);
        original = await courseState(page, env);
        originalTitle = await formValues(await titleForm(page, env));
        originalFormat = await getFormat(page, env);
        writeJson(artifact('original-state.json'), {course: original, format: originalFormat, titleSettings: Object.fromEntries(Object.entries(originalTitle).map(([name, value]) => [name, name === 'sesskey' ? '[redacted]' : value]))});
        if (String(original.category) !== env.originalCategory) await updateCategory(page, env, env.originalCategory);
        await updateCategory(page, env, env.temporaryCategory);
        if (String((await courseState(page, env)).category) !== env.temporaryCategory) throw new Error('Temporary category 8 was not selected.');
        await updateFullname(page, env, 'Evaluation pedagogique inclusive : cooperation internationale, accessibilite numerique et reussite durable des apprenants');
        const editor = await titleForm(page, env);
        await setFormValues(editor, {enabled: '1', replacemoodletitle: '0', frameenabled: '1', frametype: 'box', framepadding: '18', fontsize: '100', lineheight: '110'});
        await saveForm(editor, page);
        await setFormat(page, env, env.format);
        await page.goto(`${env.baseUrl}/course/view.php?id=${env.courseId}`, {waitUntil: 'domcontentloaded'});
        await page.setViewportSize(env.viewport);
        const banner = page.locator('.local-course-banner-builder-native-course-banner').first();
        const overlays = page.locator('.local-course-banner-builder-fixed-overlay');
        await expect(page.locator('.local-course-banner-builder-native-course-banner')).toHaveCount(1);
        await expect(banner).toBeVisible({timeout: 15000});
        await expect(overlays).toHaveCount(8);
        await expect(overlays.first()).toBeVisible();
        const beforeZoom = await page.evaluate(() => ({innerWidth, innerHeight, devicePixelRatio, visualScale: visualViewport && visualViewport.scale}));
        if (env.zoom === 200) {
            const nativeEvidence = nativeZoom(browserProcess.pid, 'zoom', artifact('zoom-200-chrome.png'));
            await page.waitForTimeout(700);
            const afterZoom = await page.evaluate(() => ({innerWidth, innerHeight, devicePixelRatio, visualScale: visualViewport && visualViewport.scale}));
            const widthRatio = beforeZoom.innerWidth / Math.max(afterZoom.innerWidth, 1);
            const achieved = afterZoom.devicePixelRatio / Math.max(beforeZoom.devicePixelRatio, 1) >= 1.9 || widthRatio >= 1.9;
            writeJson(artifact('zoom-200-metrics.json'), {beforeZoom, afterZoom, widthRatio, achieved, nativeEvidence});
            if (!achieved) throw new Error('Genuine 200% browser zoom was not proven.');
        }
        const baseline = await page.evaluate(() => {
            const banner = document.querySelector('.local-course-banner-builder-native-course-banner');
            const box = banner.getBoundingClientRect();
            return Array.from(document.querySelectorAll('.local-course-banner-builder-fixed-overlay')).map(element => {
                const rect = element.getBoundingClientRect();
                return {left: (rect.left - box.left) / box.width, top: (rect.top - box.top) / box.height, width: rect.width / box.width, height: rect.height / box.height};
            });
        });
        const measured = await page.evaluate(async({format, policy, baseline}) => {
            const selector = `.local-course-banner-builder-native-course-banner--format-${format}`;
            const banner = document.querySelector('.local-course-banner-builder-native-course-banner');
            const box = banner.getBoundingClientRect();
            const style = getComputedStyle(banner);
            const naturalHeight = box.width / policy.ratio;
            const expectedHeight = policy.max === null ? naturalHeight : Math.min(policy.max, Math.max(policy.min, naturalHeight));
            const styleSheets = Array.from(document.styleSheets);
            const matchingRule = sheet => {
                try {
                    return Array.from(sheet.cssRules || []).find(rule => rule.selectorText && rule.selectorText.includes(selector) && rule.style.minHeight === '128px') || null;
                } catch (error) { return null; }
            };
            const runtimeSheet = styleSheets.find(sheet => sheet.ownerNode && sheet.ownerNode.tagName === 'STYLE' && sheet.ownerNode.textContent.includes('local-course-banner-builder-native-course-banner--format-contentwide'));
            const compiledSheet = styleSheets.find(sheet => sheet.ownerNode && sheet.ownerNode.tagName === 'LINK' && matchingRule(sheet));
            const runtimeRule = runtimeSheet && matchingRule(runtimeSheet);
            const compiledRule = compiledSheet && matchingRule(compiledSheet);
            const compiledSelectorText = '.local-course-banner-builder-native-course-banner--format-contentwide,' +
                '.local-course-banner-builder-native-course-banner--format-fullwidthtop,' +
                '.local-course-banner-builder-native-course-banner--format-fullwidthtopcompact,' +
                '.local-course-banner-builder-native-course-banner--format-fullwidthtopinset{min-height:128px';
            const stylesheetTexts = await Promise.all(Array.from(document.querySelectorAll('link[rel="stylesheet"]')).map(async link => ({
                href: link.href,
                text: await fetch(link.href).then(response => response.ok ? response.text() : '').catch(() => ''),
            })));
            const compiledTextFallback = stylesheetTexts.find(item => item.text.includes(compiledSelectorText));
            const pluginStylesHref = new URL('/local/course_banner_builder/styles.css', location.origin).href;
            const pluginStylesText = await fetch(pluginStylesHref).then(response => response.ok ? response.text() : '').catch(() => '');
            const compiledPluginFallback = pluginStylesText.replace(/\s+/g, '').includes(compiledSelectorText);
            const title = document.querySelector('.local-course-banner-builder-banner-title-overlay');
            const frame = title && title.querySelector(':scope > span');
            const titleBox = title && title.getBoundingClientRect();
            const frameBox = frame && frame.getBoundingClientRect();
            const navigation = document.querySelector('#page-navbar, nav[aria-label*="breadcrumb" i]');
            const navigationBox = navigation && navigation.getBoundingClientRect();
            const titleEdges = titleBox ? {top: titleBox.top - box.top, bottom: box.bottom - titleBox.bottom, left: titleBox.left - box.left, right: box.right - titleBox.right} : null;
            const overlays = Array.from(document.querySelectorAll('.local-course-banner-builder-fixed-overlay')).map(element => {
                const rect = element.getBoundingClientRect();
                const image = element.querySelector('img');
                const imageStyle = image && getComputedStyle(image);
                const clippingAncestors = [];
                let ancestor = element.parentElement;
                while (ancestor && clippingAncestors.length < 8) {
                    const ancestorStyle = getComputedStyle(ancestor);
                    if (/(hidden|clip|auto|scroll)/.test(`${ancestorStyle.overflow} ${ancestorStyle.overflowX} ${ancestorStyle.overflowY}`)) clippingAncestors.push(ancestor.className || ancestor.tagName);
                    ancestor = ancestor.parentElement;
                }
                return {box: {x: rect.x, y: rect.y, width: rect.width, height: rect.height}, normalized: {left: (rect.left - box.left) / box.width, top: (rect.top - box.top) / box.height, width: rect.width / box.width, height: rect.height / box.height}, opacity: getComputedStyle(element).opacity, zIndex: getComputedStyle(element).zIndex, clippingAncestors, image: imageStyle ? {objectPosition: imageStyle.objectPosition, objectFit: imageStyle.objectFit} : null};
            });
            const borders = Array.from(document.querySelectorAll('.local-course-banner-builder-fixed-border')).map(element => {
                const rect = element.getBoundingClientRect();
                return {x: rect.x, y: rect.y, width: rect.width, height: rect.height, insideBanner: rect.left >= box.left - 1 && rect.top >= box.top - 1 && rect.right <= box.right + 1 && rect.bottom <= box.bottom + 1};
            });
            const lineRange = title ? document.createRange() : null;
            if (lineRange) lineRange.selectNodeContents(title);
            const lineCount = lineRange ? new Set(Array.from(lineRange.getClientRects()).map(rect => Math.round(rect.top * 10) / 10)).size : 0;
            return {
                format, policy, css: {computedMinHeight: style.minHeight, computedMaxHeight: style.maxHeight, computedAspectRatio: style.aspectRatio, runtimeWinner: runtimeRule ? {selector: runtimeRule.selectorText, minHeight: runtimeRule.style.minHeight, owner: 'inline-runtime'} : null, compiledFallback: compiledRule ? {selector: compiledRule.selectorText, minHeight: compiledRule.style.minHeight, href: compiledSheet.href, inspection: 'cssom'} : (compiledTextFallback ? {selector: compiledSelectorText, minHeight: '128px', href: compiledTextFallback.href, inspection: 'theme-stylesheet-text'} : (compiledPluginFallback ? {selector: compiledSelectorText, minHeight: '128px', href: pluginStylesHref, inspection: 'plugin-stylesheet-text'} : null))},
                banner: {width: box.width, height: box.height, naturalHeight, expectedHeight, delta: box.height - expectedHeight, effectiveRatio: box.width / box.height},
                title: titleBox ? {box: {x: titleBox.x, y: titleBox.y, width: titleBox.width, height: titleBox.height}, frame: frameBox ? {x: frameBox.x, y: frameBox.y, width: frameBox.width, height: frameBox.height} : null, lineCount, clientWidth: title.clientWidth, scrollWidth: title.scrollWidth, clientHeight: title.clientHeight, scrollHeight: title.scrollHeight, edges: titleEdges, insideBanner: titleBox.left >= box.left - 1 && titleBox.top >= box.top - 1 && titleBox.right <= box.right + 1 && titleBox.bottom <= box.bottom + 1, navigationCollision: !!(navigationBox && titleBox && titleBox.top < navigationBox.bottom && titleBox.bottom > navigationBox.top)} : null,
                overlays, baseline, borders,
                document: {clientWidth: document.documentElement.clientWidth, scrollWidth: document.documentElement.scrollWidth, horizontalOverflow: Math.max(0, document.documentElement.scrollWidth - document.documentElement.clientWidth)},
            };
        }, {format: env.format, policy: env.policy, baseline});
        writeJson(artifact('measurement.json'), measured);
        await page.screenshot({path: artifact('public-banner.png'), fullPage: true});
        checkpoint('measured', {measurement: measured});
        if (Math.abs(measured.banner.delta) > 1) throw new Error(`Height mismatch is ${measured.banner.delta}px.`);
        if (env.format === 'standard') {
            if (measured.css.computedMinHeight !== '0px' || measured.css.computedMaxHeight !== 'none' || measured.banner.effectiveRatio < 3.9) throw new Error('Standard sizing changed.');
        } else {
            if (measured.css.computedMinHeight !== '128px') throw new Error(`Expected computed 128px floor, got ${measured.css.computedMinHeight}.`);
            if (!measured.css.runtimeWinner || !measured.css.compiledFallback) throw new Error('Runtime and compiled public CSS did not both expose the 128px selector.');
        }
        if (measured.document.horizontalOverflow !== 0) throw new Error('Unexpected horizontal overflow.');
        if (measured.title && (!measured.title.insideBanner || measured.title.navigationCollision || measured.title.clientWidth !== measured.title.scrollWidth || measured.title.clientHeight !== measured.title.scrollHeight)) throw new Error('Title or frame containment failed.');
        if (measured.borders.some(border => !border.insideBanner)) throw new Error('Border escaped the public banner.');
        if (measured.overlays.some((overlay, index) => Math.abs(overlay.normalized.left - measured.baseline[index].left) > 0.01 || Math.abs(overlay.normalized.top - measured.baseline[index].top) > 0.01)) throw new Error('Overlay normalized geometry drifted.');
    } catch (error) {
        writeJson(artifact('failure.json'), {error: String(error && error.stack || error)});
        checkpoint('failure', {error: String(error && error.stack || error)});
        throw error;
    } finally {
        if (page) {
            try { nativeZoom(browserProcess.pid, 'reset', artifact('cleanup-100-chrome.png')); cleanup.zoom100 = true; } catch (error) { cleanup.error = String(error); }
            try {
                if (originalTitle) { const form = await titleForm(page, env); await setFormValues(form, originalTitle); await saveForm(form, page); cleanup.titleSettings = true; }
                if (original && original.fullname) { await updateFullname(page, env, original.fullname); cleanup.title = true; }
                if (originalFormat) { await setFormat(page, env, originalFormat); cleanup.format = await getFormat(page, env) === originalFormat; }
                await updateCategory(page, env, env.originalCategory);
                cleanup.category3 = String((await courseState(page, env)).category) === env.originalCategory;
            } catch (error) { cleanup.error = String(error && error.stack || error); }
        }
        if (tracing && browser) { try { await browser.contexts()[0].tracing.stop({path: artifact('trace.zip')}); } catch (error) { cleanup.error = String(error); } }
        writeJson(artifact('cleanup.json'), cleanup);
        checkpoint('completed', {cleanup});
        if (browser) await browser.close().catch(() => {});
        if (!browserProcess.killed) browserProcess.kill();
        if (!cleanup.zoom100 || !cleanup.category3 || !cleanup.title || !cleanup.titleSettings || !cleanup.format) throw new Error(`Fixture cleanup incomplete: ${JSON.stringify(cleanup)}.`);
    }
});
