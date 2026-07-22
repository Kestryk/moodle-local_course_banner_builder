const {test, expect, chromium} = require('@playwright/test');
const fs = require('fs');
const path = require('path');
const {execFileSync, spawn} = require('child_process');
const os = require('os');
const crypto = require('crypto');

const requiredEnvironment = [
    'EASYEDU_MOODLE_URL',
    'EASYEDU_MOODLE_USERNAME',
    'EASYEDU_MOODLE_PASSWORD',
    'EASYEDU_CCB_FIXTURE_COURSE_SHORTNAME',
    'EASYEDU_CCB_FIXTURE_COURSE_ID',
];

const getRequiredEnvironment = () => {
    const missing = requiredEnvironment.filter(name => !process.env[name]);
    if (missing.length) {
        throw new Error(`Missing required test environment: ${missing.join(', ')}`);
    }
    return {
        baseUrl: process.env.EASYEDU_MOODLE_URL.replace(/\/$/, ''),
        username: process.env.EASYEDU_MOODLE_USERNAME,
        password: process.env.EASYEDU_MOODLE_PASSWORD,
        courseShortname: process.env.EASYEDU_CCB_FIXTURE_COURSE_SHORTNAME,
        courseId: process.env.EASYEDU_CCB_FIXTURE_COURSE_ID,
    };
};

const login = async(page, environment) => {
    await page.goto(`${environment.baseUrl}/login/index.php`, {waitUntil: 'domcontentloaded'});
    await page.locator('#username').fill(environment.username);
    await page.locator('#password').fill(environment.password);
    await page.locator('#loginbtn').click();
    await page.waitForLoadState('domcontentloaded');
    if (page.url().includes('/login/')) {
        throw new Error('Moodle authentication did not leave the login page.');
    }
};

const measure = async(locator, container) => locator.evaluate((element, containerElement) => {
    const box = element.getBoundingClientRect();
    const containerBox = containerElement.getBoundingClientRect();
    const style = getComputedStyle(element);
    const image = element.matches('img') ? element : element.querySelector('img');
    const imageStyle = image ? getComputedStyle(image) : null;
    const normalise = (value, total) => total ? Number((value / total).toFixed(6)) : null;
    return {
        box: {x: box.x, y: box.y, width: box.width, height: box.height},
        normalised: {
            left: normalise(box.left - containerBox.left, containerBox.width),
            top: normalise(box.top - containerBox.top, containerBox.height),
            width: normalise(box.width, containerBox.width),
            height: normalise(box.height, containerBox.height),
            centreX: normalise(box.left - containerBox.left + (box.width / 2), containerBox.width),
            centreY: normalise(box.top - containerBox.top + (box.height / 2), containerBox.height),
        },
        style: {
            left: style.left,
            top: style.top,
            width: style.width,
            height: style.height,
            overflow: style.overflow,
            transform: style.transform,
            transformOrigin: style.transformOrigin,
            opacity: style.opacity,
            zIndex: style.zIndex,
            display: style.display,
        },
        image: image ? {
            naturalWidth: image.naturalWidth,
            naturalHeight: image.naturalHeight,
            objectFit: imageStyle.objectFit,
            objectPosition: imageStyle.objectPosition,
            opacity: imageStyle.opacity,
            clipPath: imageStyle.clipPath,
        } : null,
    };
}, await container.elementHandle());

const measureAll = async(locator, container) => {
    const count = await locator.count();
    const result = [];
    for (let index = 0; index < count; index++) {
        result.push(await measure(locator.nth(index), container));
    }
    return result;
};

const measureContainer = async locator => locator.evaluate(element => {
    const box = element.getBoundingClientRect();
    const style = getComputedStyle(element);
    const ancestors = [];
    let ancestor = element.parentElement;
    while (ancestor && ancestors.length < 8) {
        const ancestorBox = ancestor.getBoundingClientRect();
        const ancestorStyle = getComputedStyle(ancestor);
        ancestors.push({
            tagName: ancestor.tagName,
            id: ancestor.id || null,
            className: ancestor.className || null,
            box: {x: ancestorBox.x, y: ancestorBox.y, width: ancestorBox.width, height: ancestorBox.height},
            overflow: ancestorStyle.overflow,
            overflowX: ancestorStyle.overflowX,
            overflowY: ancestorStyle.overflowY,
            position: ancestorStyle.position,
            width: ancestorStyle.width,
            maxWidth: ancestorStyle.maxWidth,
        });
        ancestor = ancestor.parentElement;
    }
    return {
        box: {x: box.x, y: box.y, width: box.width, height: box.height},
        aspectRatio: box.height ? Number((box.width / box.height).toFixed(6)) : null,
        className: element.className,
        overflow: style.overflow,
        transform: style.transform,
        display: style.display,
        width: style.width,
        height: style.height,
        minHeight: style.minHeight,
        maxHeight: style.maxHeight,
        computedAspectRatio: style.aspectRatio,
        boxSizing: style.boxSizing,
        padding: `${style.paddingTop} ${style.paddingRight} ${style.paddingBottom} ${style.paddingLeft}`,
        backgroundImage: style.backgroundImage,
        backgroundSize: style.backgroundSize,
        backgroundPosition: style.backgroundPosition,
        backgroundRepeat: style.backgroundRepeat,
        ancestors,
    };
});

const measureTitle = async locator => {
    if (!await locator.count()) {
        return null;
    }
    return locator.first().evaluate(element => {
        const box = element.getBoundingClientRect();
        const style = getComputedStyle(element);
        const range = document.createRange();
        range.selectNodeContents(element);
        const lineTops = Array.from(range.getClientRects())
            .filter(rect => rect.width > 0 && rect.height > 0)
            .map(rect => Math.round(rect.top * 10) / 10)
            .filter((top, index, values) => values.indexOf(top) === index);
        const frame = element.querySelector(':scope > span');
        const frameBox = frame ? frame.getBoundingClientRect() : null;
        return {
            text: (element.textContent || '').trim(),
            box: {x: box.x, y: box.y, width: box.width, height: box.height},
            lineCount: lineTops.length,
            lineTops,
            clientWidth: element.clientWidth,
            clientHeight: element.clientHeight,
            scrollWidth: element.scrollWidth,
            scrollHeight: element.scrollHeight,
            overflowsX: element.scrollWidth > element.clientWidth + 1,
            overflowsY: element.scrollHeight > element.clientHeight + 1,
            overflow: style.overflow,
            whiteSpace: style.whiteSpace,
            lineHeight: style.lineHeight,
            fontSize: style.fontSize,
            maxWidth: style.maxWidth,
            padding: `${style.paddingTop} ${style.paddingRight} ${style.paddingBottom} ${style.paddingLeft}`,
            border: `${style.borderTopWidth} ${style.borderRightWidth} ${style.borderBottomWidth} ${style.borderLeftWidth}`,
            frame: frameBox ? {
                x: frameBox.x, y: frameBox.y, width: frameBox.width, height: frameBox.height,
            } : null,
            transform: style.transform,
            zIndex: style.zIndex,
        };
    });
};

const allFormats = ['standard', 'contentwide', 'fullwidthtop', 'fullwidthtopcompact', 'fullwidthtopinset'];
const allViewports = [
    {name: 'desktop', width: 1600, height: 900},
    {name: 'tablet', width: 1024, height: 768},
    {name: 'portrait', width: 768, height: 1024},
    {name: 'mobile', width: 390, height: 844},
];
const environmentList = (name, fallback) => (process.env[name] || fallback)
    .split(',')
    .map(value => value.trim())
    .filter(Boolean);
const formats = environmentList('EASYEDU_CCB_FORMATS', allFormats.join(','));
const requestedViewportNames = environmentList(
    'EASYEDU_CCB_VIEWPORTS',
    process.env.EASYEDU_CCB_VIEWPORT || allViewports.map(viewport => viewport.name).join(',')
);
const viewports = allViewports.filter(viewport => requestedViewportNames.includes(viewport.name));
const formatLabel = formats.join('-');
const viewportLabel = viewports.map(viewport => viewport.name).join('-');

if (formats.some(format => !allFormats.includes(format))) {
    throw new Error(`Unsupported EASYEDU_CCB_FORMATS value: ${formats.join(', ')}`);
}
if (!viewports.length) {
    throw new Error(`Unsupported EASYEDU_CCB_VIEWPORT value: ${requestedViewportNames.join(', ')}`);
}

const getThemeAndZoom = async page => page.evaluate(() => {
    const classNames = [...document.body.classList, ...document.documentElement.classList];
    const themeClass = classNames.find(name => name.startsWith('theme-')) || null;
    const stylesheetTheme = Array.from(document.styleSheets)
        .map(sheet => sheet.href || '')
        .map(href => href.match(/\/theme\/styles\.php\/([^/?]+)/))
        .find(match => match && match[1]);
    return {
        theme: themeClass ? themeClass.replace(/^theme-/, '') : (stylesheetTheme ? stylesheetTheme[1] : null),
        visualViewportScale: window.visualViewport ? window.visualViewport.scale : null,
        devicePixelRatio: window.devicePixelRatio,
        innerWidth: window.innerWidth,
        innerHeight: window.innerHeight,
    };
});

const attemptGenuineBrowserZoom = async page => {
    const requested = Number(process.env.EASYEDU_CCB_BROWSER_ZOOM || 100);
    const before = await getThemeAndZoom(page);
    if (requested !== 200) {
        return {requested, attempted: false, before, after: before, achieved: requested === 100};
    }
    for (let step = 0; step < 5; step++) {
        await page.keyboard.press('Control++');
    }
    await page.waitForTimeout(500);
    const after = await getThemeAndZoom(page);
    const dprRatio = before.devicePixelRatio ? after.devicePixelRatio / before.devicePixelRatio : 1;
    const viewportRatio = after.innerWidth ? before.innerWidth / after.innerWidth : 1;
    return {
        requested,
        attempted: true,
        method: 'Chromium headed browser Control++ shortcut, five increments',
        before,
        after,
        dprRatio,
        viewportRatio,
        achieved: dprRatio >= 1.9 || viewportRatio >= 1.9,
    };
};

const nativeChromeZoom = (operation, screenshotPath = null) => {
    const zoomKeys = operation === 'reset' ? "'^0'" : "'^{+}'";
    const zoomCount = operation === 'reset' ? 1 : 5;
    const screenshot = screenshotPath
        ? `Add-Type -AssemblyName System.Windows.Forms; Add-Type -AssemblyName System.Drawing; ` +
            `$bounds=[System.Windows.Forms.Screen]::PrimaryScreen.Bounds; $bitmap=New-Object System.Drawing.Bitmap $bounds.Width,$bounds.Height; ` +
            `$graphics=[System.Drawing.Graphics]::FromImage($bitmap); $graphics.CopyFromScreen($bounds.Location,[System.Drawing.Point]::Empty,$bounds.Size); ` +
            `$bitmap.Save('${screenshotPath.replace(/'/g, "''")}'); $graphics.Dispose(); $bitmap.Dispose(); `
        : '';
    const command = `$ErrorActionPreference='Stop'; ` +
        `$window=Get-Process chrome -ErrorAction SilentlyContinue | Where-Object {$_.MainWindowHandle -ne 0} | ` +
        `Sort-Object StartTime -Descending | Select-Object -First 1; ` +
        `if(-not $window){throw 'No visible Chrome window was found.'}; ` +
        `$shell=New-Object -ComObject WScript.Shell; if(-not $shell.AppActivate($window.Id)){throw 'Chrome activation failed.'}; ` +
        `Start-Sleep -Milliseconds 400; 1..${zoomCount} | ForEach-Object {$shell.SendKeys(${zoomKeys}); Start-Sleep -Milliseconds 180}; ` +
        `Start-Sleep -Milliseconds 700; ${screenshot} Write-Output $window.Id`;
    return execFileSync('powershell.exe', ['-NoProfile', '-NonInteractive', '-Command', command], {
        encoding: 'utf8',
        windowsHide: true,
    }).trim();
};

// Recovery-only native interaction. It receives the exact Chrome process that
// this test started, never enumerates or targets another Chrome window.
const nativeChromeZoomForProcess = (processId, operation, screenshotPath) => {
    const keys = operation === 'reset' ? "'^0'" : "'^{+}'";
    const count = operation === 'reset' ? 1 : 5;
    const escapedPath = screenshotPath.replace(/'/g, "''");
    const command = `$ErrorActionPreference='Stop'; ` +
        `$window=Get-Process -Id ${processId} -ErrorAction Stop; ` +
        `if($window.MainWindowHandle -eq 0){throw 'Dedicated Chrome process has no visible main window.'}; ` +
        `$shell=New-Object -ComObject WScript.Shell; if(-not $shell.AppActivate($window.Id)){throw 'Dedicated Chrome activation failed.'}; ` +
        `Start-Sleep -Milliseconds 500; 1..${count} | ForEach-Object {$shell.SendKeys(${keys}); Start-Sleep -Milliseconds 220}; ` +
        `Start-Sleep -Milliseconds 900; ` +
        `Add-Type -AssemblyName System.Windows.Forms; Add-Type -AssemblyName System.Drawing; ` +
        `$bounds=[System.Windows.Forms.Screen]::PrimaryScreen.Bounds; $bitmap=New-Object System.Drawing.Bitmap $bounds.Width,$bounds.Height; ` +
        `$graphics=[System.Drawing.Graphics]::FromImage($bitmap); $graphics.CopyFromScreen($bounds.Location,[System.Drawing.Point]::Empty,$bounds.Size); ` +
        `$bitmap.Save('${escapedPath}'); $graphics.Dispose(); $bitmap.Dispose(); ` +
        `Write-Output ($window.Id.ToString()+'|'+$window.MainWindowHandle.ToString()+'|'+$window.MainWindowTitle)`;
    return execFileSync('powershell.exe', ['-NoProfile', '-NonInteractive', '-Command', command], {
        encoding: 'utf8', windowsHide: true,
    }).trim();
};

const artifactPath = (testInfo, label, name) => {
    if (!process.env.EASYEDU_CCB_ARTIFACT_ROOT) {
        return testInfo.outputPath(name);
    }
    const directory = path.join(process.env.EASYEDU_CCB_ARTIFACT_ROOT, label);
    fs.mkdirSync(directory, {recursive: true});
    return path.join(directory, name);
};

const writeAtomicJson = (filePath, value) => {
    fs.mkdirSync(path.dirname(filePath), {recursive: true});
    const temporaryPath = `${filePath}.${process.pid}.tmp`;
    fs.writeFileSync(temporaryPath, JSON.stringify(value, null, 2));
    fs.renameSync(temporaryPath, filePath);
};

const openAdminPreview = async(page, environment) => {
    await page.goto(`${environment.baseUrl}/local/course_banner_builder/admin_manage.php`, {waitUntil: 'domcontentloaded'});
    const button = page.locator('[data-action="local-course-banner-builder-show-source-chain-preview"]').first();
    await expect(button).toBeVisible();
    await button.click();
    const frame = page.locator('[data-source-preview-frame="1"]').first();
    await expect(frame).toBeVisible();
    const layers = frame.locator('[data-source-preview-layer="1"]');
    expect(await layers.count()).toBeGreaterThan(0);
    return {container: await measureContainer(frame), layers: await measureAll(layers, frame)};
};

const openPublicBanner = async(page, environment) => {
    await page.goto(`${environment.baseUrl}/course/view.php?id=${encodeURIComponent(environment.courseId)}`, {
        waitUntil: 'networkidle',
    });
    const banner = page.locator('.local-course-banner-builder-native-course-banner').first();
    await expect(banner).toBeVisible();
    const layers = banner.locator('.local-course-banner-builder-fixed-overlay');
    expect(await layers.count()).toBeGreaterThan(0);
    return {
        container: await measureContainer(banner),
        layers: await measureAll(layers, banner),
        title: await measureTitle(banner.locator('.local-course-banner-builder-banner-title-overlay')),
        viewport: await page.evaluate(() => ({
            innerWidth: window.innerWidth,
            innerHeight: window.innerHeight,
            visualViewport: window.visualViewport ? {
                width: window.visualViewport.width,
                height: window.visualViewport.height,
                scale: window.visualViewport.scale,
                offsetLeft: window.visualViewport.offsetLeft,
                offsetTop: window.visualViewport.offsetTop,
            } : null,
        })),
    };
};

const diagnosticFormatRatios = {
    standard: 4,
    contentwide: 5,
    fullwidthtop: 5,
    fullwidthtopcompact: 8,
    fullwidthtopinset: 6.1,
};

const applyDiagnosticPolicy = async(banner, format, policy) => {
    if (policy === 'baseline') {
        return {policy, diagnostic: false};
    }
    return banner.evaluate((element, {format, policy, ratios}) => {
        const width = element.getBoundingClientRect().width;
        const naturalHeight = width / ratios[format];
        let appliedHeight = naturalHeight;
        let activated = false;
        if (policy === 'shared-min-144') {
            appliedHeight = Math.max(naturalHeight, 144);
            activated = naturalHeight < 144;
        } else if (policy === 'effective-cap-2.5') {
            appliedHeight = Math.max(naturalHeight, width / 2.5);
            activated = naturalHeight < width / 2.5;
        } else if (policy === 'combined-128-2.5-144') {
            const safetyHeight = Math.min(144, Math.max(128, width / 2.5));
            appliedHeight = Math.max(naturalHeight, safetyHeight);
            activated = naturalHeight < safetyHeight;
        }
        element.dataset.ccbLot0DiagnosticPolicy = policy;
        element.style.setProperty('min-height', '0px', 'important');
        element.style.setProperty('max-height', 'none', 'important');
        element.style.setProperty('height', `${appliedHeight}px`, 'important');
        return {
            policy,
            diagnostic: true,
            format,
            width,
            configuredRatio: ratios[format],
            naturalHeight,
            appliedHeight,
            activated,
        };
    }, {format, policy, ratios: diagnosticFormatRatios});
};

const measureDiagnosticPublic = async(page, environment, format, viewport, policy) => {
    await page.setViewportSize(viewport);
    await page.goto(`${environment.baseUrl}/course/view.php?id=${encodeURIComponent(environment.courseId)}`, {
        waitUntil: 'networkidle',
    });
    const banner = page.locator('.local-course-banner-builder-native-course-banner').first();
    await expect(banner).toBeVisible();
    const diagnostic = await applyDiagnosticPolicy(banner, format, policy);
    await page.waitForTimeout(80);
    const container = await measureContainer(banner);
    const title = await measureTitle(banner.locator('.local-course-banner-builder-banner-title-overlay'));
    const titleEdges = title ? {
        left: Number((title.box.x - container.box.x).toFixed(3)),
        right: Number((container.box.x + container.box.width - (title.box.x + title.box.width)).toFixed(3)),
        top: Number((title.box.y - container.box.y).toFixed(3)),
        bottom: Number((container.box.y + container.box.height - (title.box.y + title.box.height)).toFixed(3)),
    } : null;
    return {
        format,
        viewport,
        diagnostic,
        container,
        title,
        titleEdges,
        overlays: await measureAll(banner.locator('.local-course-banner-builder-fixed-overlay'), banner),
        controls: await banner.locator('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled])')
            .evaluateAll(elements => elements.map(element => {
                const box = element.getBoundingClientRect();
                return {
                    tagName: element.tagName,
                    text: (element.textContent || '').trim(),
                    ariaLabel: element.getAttribute('aria-label'),
                    box: {x: box.x, y: box.y, width: box.width, height: box.height},
                    tabIndex: element.tabIndex,
                };
            })),
        pageReflow: await page.evaluate(() => ({
            documentScrollWidth: document.documentElement.scrollWidth,
            documentClientWidth: document.documentElement.clientWidth,
            pageScrollWidth: document.body.scrollWidth,
            pageClientWidth: document.body.clientWidth,
            horizontalOverflow: Math.max(document.documentElement.scrollWidth - document.documentElement.clientWidth,
                document.body.scrollWidth - document.body.clientWidth),
            viewportHeight: window.innerHeight,
        })),
    };
};

const setCourseFormat = async(page, environment, format) => {
    await page.goto(`${environment.baseUrl}/local/course_banner_builder/admin_manage.php`, {waitUntil: 'domcontentloaded'});
    await page.locator('.local-course-banner-builder-admin-format-button').first().click();
    const modal = page.locator('.modal.show').last();
    await expect(modal).toBeVisible();
    const formatInput = modal.locator(`input[name="bannerformat"][value="${format}"]`);
    await formatInput.evaluate(input => input.click());
    await expect(formatInput).toBeChecked();
    await modal.locator('button[type="submit"]').click();
    await expect(modal).toBeHidden({timeout: 10000});
    await page.reload({waitUntil: 'domcontentloaded'});
};

const getCourseFormat = async(page, environment) => {
    await page.goto(`${environment.baseUrl}/local/course_banner_builder/admin_manage.php`, {waitUntil: 'domcontentloaded'});
    await page.locator('.local-course-banner-builder-admin-format-button').first().click();
    const modal = page.locator('.modal.show').last();
    await expect(modal).toBeVisible();
    const format = await modal.locator('input[name="bannerformat"]:checked').inputValue();
    await modal.locator('[data-dismiss="modal"], [data-bs-dismiss="modal"]').first().click();
    await expect(modal).toBeHidden();
    return format;
};

const getNamedFormValues = async form => form.evaluate(element => Object.fromEntries(
    Array.from(element.elements)
        .filter(field => field.name && field.type !== 'file')
        .map(field => [field.name, field.type === 'checkbox' ? (field.checked ? '1' : '0') : field.value])
));

const setNamedFormValues = async(form, values) => form.evaluate((element, nextValues) => {
    Object.entries(nextValues).forEach(([name, value]) => {
        const field = Array.from(element.elements).find(candidate => candidate.name === name);
        if (!field) {
            return;
        }
        if (field.type === 'checkbox') {
            field.checked = String(value) === '1';
        } else {
            field.value = value;
        }
        field.dispatchEvent(new Event('input', {bubbles: true}));
        field.dispatchEvent(new Event('change', {bubbles: true}));
    });
}, values);

const openCourseTitleForm = async(page, environment) => {
    await page.goto(`${environment.baseUrl}/local/course_banner_builder/admin_manage.php`, {
        waitUntil: 'networkidle',
    });
    const form = page.locator('#local-course-banner-builder-title-settings-course-modal form[data-banner-title-editor="1"]');
    await expect(form).toHaveCount(1);
    return form;
};

const saveCourseTitleForm = async(form, page) => {
    await form.evaluate(element => element.requestSubmit());
    await page.waitForTimeout(1500);
};

const openCourseEditForm = async(page, environment) => {
    await page.setViewportSize({width: 1600, height: 900});
    await page.goto(`${environment.baseUrl}/course/view.php?id=${encodeURIComponent(environment.courseId)}`, {
        waitUntil: 'domcontentloaded',
    });
    const href = await page.locator('a').evaluateAll(links => {
        const settings = links.find(link =>
            (link.textContent || '').trim() === 'Settings' && link.href.includes('/course/edit.php')
        );
        return settings ? settings.href : null;
    });
    if (!href) {
        const diagnostic = await page.locator('body').innerText();
        throw new Error(`The fixture course does not expose the supported Settings link at ${page.url()}: ${diagnostic.slice(0, 300)}`);
    }
    await page.goto(href, {waitUntil: 'domcontentloaded'});
};

const getCourseFullname = async(page, environment) => {
    await openCourseEditForm(page, environment);
    const fullname = page.locator('#id_fullname');
    await expect(fullname).toBeVisible();
    return fullname.inputValue();
};

const updateCourseFullname = async(page, environment, fullname) => {
    await openCourseEditForm(page, environment);
    const field = page.locator('#id_fullname');
    await expect(field).toBeVisible();
    await field.fill(fullname);
    await page.locator('#id_saveanddisplay').click({noWaitAfter: true});
    await page.waitForTimeout(1500);
};

const getCourseCategory = async(page, environment) => {
    await openCourseEditForm(page, environment);
    const field = page.locator('#id_category');
    await expect(field).toHaveCount(1);
    return {
        id: await field.inputValue(),
        label: await field.locator('option:checked').textContent(),
    };
};

const updateCourseCategory = async(page, environment, categoryId) => {
    await openCourseEditForm(page, environment);
    const field = page.locator('#id_category');
    await expect(field).toHaveCount(1);
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
    await page.waitForTimeout(700);
};

const compareLayers = (preview, publicLayers) => preview.map((layer, index) => {
    const publicLayer = publicLayers[index];
    if (!publicLayer) {
        return {index, status: 'missing-public-layer'};
    }
    const fields = ['left', 'top', 'width', 'height', 'centreX', 'centreY'];
    const normalisedDelta = Object.fromEntries(fields.map(field => [
        field,
        Number((publicLayer.normalised[field] - layer.normalised[field]).toFixed(6)),
    ]));
    return {index, normalisedDelta};
});

test.describe('CCB public image-overlay geometry', () => {
    test.setTimeout(90000);

    test('captures the authenticated fixture surfaces without mutating CCB settings', async({browser}, testInfo) => {
        const environment = getRequiredEnvironment();
        const context = await browser.newContext({viewport: {width: 1600, height: 900}});
        const evidencePath = name => artifactPath(testInfo, 'baseline', name);
        await context.tracing.start({screenshots: true, snapshots: true, sources: true});
        const page = await context.newPage();
        try {
            await login(page, environment);

            await page.goto(`${environment.baseUrl}/local/course_banner_builder/admin_manage.php`, {
                waitUntil: 'networkidle',
            });
            const adminRoot = page.locator('.local-course-banner-builder-admin, #local-course-banner-builder-admin').first();
            await expect(adminRoot).toBeVisible();
            const formatControls = await page.locator('a, button').evaluateAll(elements => elements.map(element => ({
                text: (element.textContent || '').trim(),
                href: element.href || null,
                action: element.getAttribute('data-action'),
                className: element.className,
            })).filter(item => /course format/i.test(item.text)));
            const formatButton = page.locator('.local-course-banner-builder-admin-format-button').first();
            await expect(formatButton).toBeVisible();
            await formatButton.click();
            const formatModal = page.locator('.modal.show').last();
            await expect(formatModal, 'Course-format modal did not open.').toBeVisible();
            const formatOptions = await formatModal.locator('input, select, button').evaluateAll(elements => elements.map(element => ({
                tag: element.tagName,
                type: element.type || null,
                name: element.name || null,
                value: element.value || null,
                text: (element.textContent || '').trim(),
                className: element.className,
                checked: Boolean(element.checked),
            })).filter(item => /standard|contentwide|fullwidthtop|compact|inset/i.test(`${item.value} ${item.text}`)));
            await page.locator('.modal.show [data-dismiss="modal"], .modal.show [data-bs-dismiss="modal"]').first().click();
            const previewButton = page.locator('[data-action="local-course-banner-builder-show-source-chain-preview"]').first();
            await expect(previewButton, 'No configured source-chain preview is available for the fixture.').toBeVisible();
            await previewButton.click();
            const adminFrame = page.locator('[data-source-preview-frame="1"]').first();
            await expect(adminFrame, 'CCB did not render a stable source preview frame.').toBeVisible();
            const adminLayers = adminFrame.locator('[data-source-preview-layer="1"]');
            expect(await adminLayers.count(), 'CCB source preview has no image layer.').toBeGreaterThan(0);
            await page.screenshot({path: evidencePath('ccb-admin.png'), fullPage: true});
            const adminResult = {
                selector: '[data-source-preview-frame="1"] [data-source-preview-layer="1"]',
                container: await measureContainer(adminFrame),
                layers: await measureAll(adminLayers, adminFrame),
            };

            await page.goto(`${environment.baseUrl}/course/view.php?id=${encodeURIComponent(environment.courseId)}`, {
                waitUntil: 'networkidle',
            });
            const banner = page.locator('.local-course-banner-builder-native-course-banner').first();
            await expect(banner, `Fixture ${environment.courseShortname} has no public CCB banner.`).toBeVisible();
            const publicOverlay = banner.locator('.local-course-banner-builder-fixed-overlay').first();
            await expect(publicOverlay, 'Fixture has no migrated public HTML image overlay.').toBeVisible();
            await page.screenshot({path: evidencePath('ccb-public.png'), fullPage: true});

            const result = {
                fixture: environment.courseShortname,
                formatControls,
                formatOptions,
                admin: adminResult,
                public: {
                    selector: '.local-course-banner-builder-native-course-banner .local-course-banner-builder-fixed-overlay',
                    container: await measureContainer(banner),
                    layers: await measureAll(banner.locator('.local-course-banner-builder-fixed-overlay'), banner),
                },
            };
            await require('fs').promises.writeFile(
                evidencePath('ccb-geometry.json'),
                JSON.stringify(result, null, 2)
            );
            await testInfo.attach('ccb-geometry.json', {
                body: JSON.stringify(result, null, 2),
                contentType: 'application/json',
            });
        } finally {
            await context.tracing.stop({path: evidencePath('ccb-trace.zip')});
            await context.close();
        }
    });

    test('captures Lot 0 title-editor and public accessibility evidence without mutation', async({browser}, testInfo) => {
        const environment = getRequiredEnvironment();
        const context = await browser.newContext({viewport: {width: 1600, height: 900}});
        const page = await context.newPage();
        const evidencePath = name => artifactPath(testInfo, 'lot0-surface', name);
        await context.tracing.start({screenshots: true, snapshots: true, sources: true});
        try {
            await login(page, environment);
            await page.goto(`${environment.baseUrl}/local/course_banner_builder/admin_manage.php`, {
                waitUntil: 'networkidle',
            });
            const titleActions = await page.locator('button, a').evaluateAll(elements => elements.map(element => ({
                tag: element.tagName,
                text: (element.textContent || '').trim(),
                className: element.className || '',
                target: element.getAttribute('data-target') || element.getAttribute('data-bs-target'),
                action: element.getAttribute('data-action'),
                disabled: Boolean(element.disabled),
            })).filter(item => /title|banner title/i.test(`${item.text} ${item.className} ${item.target} ${item.action}`)));
            const titleForms = await page.locator('[data-banner-title-editor="1"]').evaluateAll(forms => forms.map(form => ({
                action: form.getAttribute('action'),
                method: form.getAttribute('method'),
                modalId: form.closest('.modal')?.id || null,
                inputs: Array.from(form.querySelectorAll('input, textarea, select')).map(input => ({
                    name: input.getAttribute('name'),
                    type: input.getAttribute('type'),
                    value: input.getAttribute('name') === 'sesskey' ? '[redacted]' : input.getAttribute('value'),
                    checked: Boolean(input.checked),
                    disabled: Boolean(input.disabled),
                    dataControl: input.getAttribute('data-title-control'),
                })),
            })));

            await page.goto(`${environment.baseUrl}/course/view.php?id=${encodeURIComponent(environment.courseId)}`, {
                waitUntil: 'networkidle',
            });
            const banner = page.locator('.local-course-banner-builder-native-course-banner').first();
            await expect(banner).toBeVisible();
            const publicAccessibility = await banner.evaluate(element => {
                const nearestAriaHidden = node => {
                    let current = node;
                    while (current) {
                        if (current.getAttribute && current.getAttribute('aria-hidden') === 'true') {
                            return {
                                tagName: current.tagName,
                                id: current.id || null,
                                className: current.className || null,
                            };
                        }
                        current = current.parentElement;
                    }
                    return null;
                };
                const focusables = Array.from(element.querySelectorAll(
                    'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
                )).map(node => ({
                    tagName: node.tagName,
                    text: (node.textContent || '').trim(),
                    ariaLabel: node.getAttribute('aria-label'),
                    tabIndex: node.tabIndex,
                    className: node.className || '',
                    ariaHiddenOwner: nearestAriaHidden(node),
                }));
                const title = element.querySelector('.local-course-banner-builder-banner-title-overlay');
                const heading = document.querySelector('#page-header h1, h1');
                return {
                    bannerAriaHidden: element.getAttribute('aria-hidden'),
                    nearestBannerAriaHidden: nearestAriaHidden(element),
                    title: title ? {
                        text: (title.textContent || '').trim(),
                        ariaHiddenOwner: nearestAriaHidden(title),
                    } : null,
                    heading: heading ? {
                        text: (heading.textContent || '').trim(),
                        hidden: getComputedStyle(heading).display === 'none' || heading.hidden,
                        ariaHiddenOwner: nearestAriaHidden(heading),
                    } : null,
                    focusables,
                };
            });
            const courseActions = await page.locator('a, button').evaluateAll(elements => elements.map(element => ({
                tag: element.tagName,
                text: (element.textContent || '').trim(),
                ariaLabel: element.getAttribute('aria-label'),
                href: element.getAttribute('href'),
                className: element.className || '',
                dataAction: element.getAttribute('data-action'),
            })).filter(item => /edit|setting|course|more|action/i.test(
                `${item.text} ${item.ariaLabel} ${item.href} ${item.className} ${item.dataAction}`
            )));
            const settingsHref = courseActions.find(item => item.text === 'Settings' && /\/course\/edit\.php/.test(item.href || ''))?.href || null;
            let courseEdit = null;
            if (settingsHref) {
                await page.goto(settingsHref, {waitUntil: 'domcontentloaded'});
                courseEdit = await page.locator('form').evaluateAll(forms => forms.map(form => ({
                    id: form.id || null,
                    action: form.getAttribute('action'),
                    controls: Array.from(form.querySelectorAll('input, button')).map(control => ({
                        id: control.id || null,
                        name: control.getAttribute('name'),
                        type: control.getAttribute('type'),
                        value: control.getAttribute('value'),
                        text: (control.textContent || '').trim(),
                    })).filter(control => control.id === 'id_fullname' || control.type === 'submit' || /submit|save/i.test(`${control.id} ${control.name} ${control.text}`)),
                })));
            }
            const result = {titleActions, titleForms, publicAccessibility, courseActions, settingsHref, courseEdit};
            await require('fs').promises.writeFile(
                evidencePath('ccb-lot0-surface.json'),
                JSON.stringify(result, null, 2)
            );
            await page.screenshot({path: evidencePath('ccb-lot0-surface.png'), fullPage: true});
        } finally {
            await context.tracing.stop({path: evidencePath('ccb-lot0-surface-trace.zip')});
            await context.close();
        }
    });

    test('creates and restores a reversible Lot 0 long-title fixture through supported forms', async({browser}, testInfo) => {
        test.setTimeout(180000);
        const environment = getRequiredEnvironment();
        const context = await browser.newContext({viewport: {width: 390, height: 844}});
        const page = await context.newPage();
        const evidencePath = name => artifactPath(testInfo, 'lot0-fixture', name);
        const longTitle = 'Évaluation pédagogique inclusive : coopération internationale, accessibilité numérique et réussite durable des apprenants';
        let originalCourseTitle = null;
        let originalTitleSettings = null;
        let restored = {courseTitle: false, titleSettings: false};
        await context.tracing.start({screenshots: true, snapshots: true, sources: true});
        try {
            await login(page, environment);
            originalCourseTitle = await getCourseFullname(page, environment);
            const originalForm = await openCourseTitleForm(page, environment);
            originalTitleSettings = await getNamedFormValues(originalForm);

            await updateCourseFullname(page, environment, longTitle);
            const fixtureForm = await openCourseTitleForm(page, environment);
            await setNamedFormValues(fixtureForm, {
                enabled: '1',
                replacemoodletitle: '0',
                frameenabled: '1',
                frametype: 'box',
                framecolor: '#0B2C4D',
                framebordercolor: '#F5C451',
                frameopacity: '88',
                frameborderwidth: '2',
                frameradius: '12',
                framepadding: '18',
                fontsize: '100',
                x: '50',
                y: '50',
                lineheight: '110',
                color: '#FFFFFF',
            });
            await saveCourseTitleForm(fixtureForm, page);

            await page.setViewportSize({width: 390, height: 844});
            const publicBanner = await openPublicBanner(page, environment);
            expect(publicBanner.title, 'The long-title fixture did not render a public title.').not.toBeNull();
            expect(publicBanner.title.text).toContain('Évaluation pédagogique inclusive');
            await page.screenshot({path: evidencePath('long-title-mobile.png'), fullPage: true});
            const accessibility = await page.locator('.local-course-banner-builder-native-course-banner').first().evaluate(element => ({
                bannerAriaHidden: element.getAttribute('aria-hidden'),
                titleAriaHidden: element.querySelector('.local-course-banner-builder-banner-title-overlay')?.getAttribute('aria-hidden') || null,
                visibleHeading: document.querySelector('#page-header h1, h1')?.textContent?.trim() || null,
            }));
            const reportSettings = Object.fromEntries(Object.entries(originalTitleSettings)
                .map(([name, value]) => [name, name === 'sesskey' ? '[redacted]' : value]));
            await require('fs').promises.writeFile(
                evidencePath('ccb-lot0-fixture.json'),
                JSON.stringify({
                    fixture: {
                        courseId: environment.courseId,
                        originalCourseTitle,
                        longTitle,
                        titleSettingsApplied: {
                            enabled: '1', replacemoodletitle: '0', frameenabled: '1', frametype: 'box',
                            frameopacity: '88', frameborderwidth: '2', framepadding: '18', fontsize: '100',
                            x: '50', y: '50', lineheight: '110',
                        },
                        originalTitleSettings: reportSettings,
                    },
                    public: publicBanner,
                    accessibility,
                }, null, 2)
            );
        } finally {
            if (originalTitleSettings) {
                const restoreForm = await openCourseTitleForm(page, environment);
                await setNamedFormValues(restoreForm, originalTitleSettings);
                await saveCourseTitleForm(restoreForm, page);
                restored.titleSettings = true;
            }
            if (originalCourseTitle !== null) {
                await updateCourseFullname(page, environment, originalCourseTitle);
                restored.courseTitle = true;
            }
            await require('fs').promises.writeFile(
                evidencePath('ccb-lot0-fixture-restoration.json'),
                JSON.stringify({restored}, null, 2)
            );
            await context.tracing.stop({path: evidencePath('ccb-lot0-fixture-trace.zip')});
            await context.close();
        }
    });

    test('records Lot 0 diagnostic mobile-sizing evidence and restores the fixture', async({browser}, testInfo) => {
        test.setTimeout(600000);
        const environment = getRequiredEnvironment();
        const context = await browser.newContext({viewport: {width: 1600, height: 900}});
        const page = await context.newPage();
        const evidencePath = name => artifactPath(testInfo, 'lot0-diagnostic', name);
        const longTitle = 'Évaluation pédagogique inclusive : coopération internationale, accessibilité numérique et réussite durable des apprenants';
        const shortTitle = 'Bannière CCB : évaluation';
        const policies = ['baseline', 'strict', 'shared-min-144', 'effective-cap-2.5', 'combined-128-2.5-144'];
        const viewportDesktop = {name: 'desktop', width: 1600, height: 900};
        const viewportPortrait = {name: 'portrait', width: 768, height: 1024};
        const viewportMobile = {name: 'mobile', width: 390, height: 844};
        let originalCourseTitle = null;
        let originalTitleSettings = null;
        let originalFormat = null;
        let restored = {courseTitle: false, titleSettings: false, format: false};
        const result = {long: [], short: [], noTitle: []};
        await context.tracing.start({screenshots: true, snapshots: true, sources: true});
        try {
            await login(page, environment);
            originalCourseTitle = await getCourseFullname(page, environment);
            const originalForm = await openCourseTitleForm(page, environment);
            originalTitleSettings = await getNamedFormValues(originalForm);
            originalFormat = await getCourseFormat(page, environment);

            await updateCourseFullname(page, environment, longTitle);
            const longForm = await openCourseTitleForm(page, environment);
            await setNamedFormValues(longForm, {
                enabled: '1', replacemoodletitle: '0', frameenabled: '1', frametype: 'box',
                framecolor: '#0B2C4D', framebordercolor: '#F5C451', frameopacity: '88',
                frameborderwidth: '2', frameradius: '12', framepadding: '18', fontsize: '100',
                x: '50', y: '50', lineheight: '110', color: '#FFFFFF',
            });
            await saveCourseTitleForm(longForm, page);

            for (const format of allFormats) {
                await setCourseFormat(page, environment, format);
                result.long.push(await measureDiagnosticPublic(page, environment, format, viewportDesktop, 'baseline'));
                for (const policy of policies) {
                    result.long.push(await measureDiagnosticPublic(page, environment, format, viewportMobile, policy));
                }
                if (format !== 'standard') {
                    for (const policy of policies) {
                        result.long.push(await measureDiagnosticPublic(page, environment, format, viewportPortrait, policy));
                    }
                }
            }
            await page.setViewportSize({width: viewportMobile.width, height: viewportMobile.height});
            await page.screenshot({path: evidencePath('long-mobile-final-cell.png'), fullPage: true});

            await updateCourseFullname(page, environment, shortTitle);
            for (const format of allFormats) {
                await setCourseFormat(page, environment, format);
                result.short.push(await measureDiagnosticPublic(page, environment, format, viewportMobile, 'baseline'));
            }

            const noTitleForm = await openCourseTitleForm(page, environment);
            await setNamedFormValues(noTitleForm, {enabled: '0'});
            await saveCourseTitleForm(noTitleForm, page);
            for (const format of allFormats) {
                await setCourseFormat(page, environment, format);
                result.noTitle.push(await measureDiagnosticPublic(page, environment, format, viewportMobile, 'baseline'));
            }

            const reportSettings = Object.fromEntries(Object.entries(originalTitleSettings)
                .map(([name, value]) => [name, name === 'sesskey' ? '[redacted]' : value]));
            await require('fs').promises.writeFile(
                evidencePath('ccb-lot0-diagnostic.json'),
                JSON.stringify({
                    diagnosticOnly: true,
                    fixture: {originalCourseTitle, longTitle, shortTitle, originalFormat, originalTitleSettings: reportSettings},
                    candidates: {
                        baseline: 'Existing per-format min/max clamp; no test-side injection.',
                        strict: 'height = containerWidth / configuredFormatRatio',
                        'shared-min-144': 'height = max(containerWidth / configuredFormatRatio, 144px)',
                        'effective-cap-2.5': 'height = max(containerWidth / configuredFormatRatio, containerWidth / 2.5)',
                        'combined-128-2.5-144': 'height = max(containerWidth / configuredFormatRatio, clamp(128px, containerWidth / 2.5, 144px))',
                    },
                    result,
                }, null, 2)
            );
        } finally {
            if (originalTitleSettings) {
                const restoreForm = await openCourseTitleForm(page, environment);
                await setNamedFormValues(restoreForm, originalTitleSettings);
                await saveCourseTitleForm(restoreForm, page);
                restored.titleSettings = true;
            }
            if (originalCourseTitle !== null) {
                await updateCourseFullname(page, environment, originalCourseTitle);
                restored.courseTitle = true;
            }
            if (originalFormat) {
                await setCourseFormat(page, environment, originalFormat);
                restored.format = await getCourseFormat(page, environment) === originalFormat;
            }
            await require('fs').promises.writeFile(
                evidencePath('ccb-lot0-diagnostic-restoration.json'),
                JSON.stringify({restored}, null, 2)
            );
            await context.tracing.stop({path: evidencePath('ccb-lot0-diagnostic-trace.zip')});
            await context.close();
        }
    });

    test('attempts genuine Chrome 200 percent zoom for the reversible Lot 0 fixture', async({}, testInfo) => {
        test.setTimeout(300000);
        const environment = getRequiredEnvironment();
        const evidencePath = name => artifactPath(testInfo, 'lot0-native-zoom', name);
        const longTitle = 'Évaluation pédagogique inclusive : coopération internationale, accessibilité numérique et réussite durable des apprenants';
        const browser = await chromium.launch({channel: 'chrome', headless: false});
        const context = await browser.newContext({viewport: null});
        const page = await context.newPage();
        const cdp = await context.newCDPSession(page);
        let originalCourseTitle = null;
        let originalTitleSettings = null;
        let originalFormat = null;
        let restored = {courseTitle: false, titleSettings: false, format: false, browserZoom: false};
        await context.tracing.start({screenshots: true, snapshots: true, sources: true});
        const setWindow = async(width, height) => {
            const windowInfo = await cdp.send('Browser.getWindowForTarget');
            await cdp.send('Browser.setWindowBounds', {windowId: windowInfo.windowId, bounds: {width, height}});
            await page.waitForTimeout(700);
        };
        const measureZoomSurface = async(label) => {
            await page.goto(`${environment.baseUrl}/course/view.php?id=${encodeURIComponent(environment.courseId)}`, {
                waitUntil: 'networkidle',
            });
            const banner = page.locator('.local-course-banner-builder-native-course-banner').first();
            await expect(banner).toBeVisible();
            return {
                label,
                browser: await getThemeAndZoom(page),
                viewport: await page.evaluate(() => ({
                    visualViewport: window.visualViewport ? {
                        width: window.visualViewport.width,
                        height: window.visualViewport.height,
                        scale: window.visualViewport.scale,
                    } : null,
                    horizontalOverflow: Math.max(document.documentElement.scrollWidth - document.documentElement.clientWidth,
                        document.body.scrollWidth - document.body.clientWidth),
                })),
                container: await measureContainer(banner),
                title: await measureTitle(banner.locator('.local-course-banner-builder-banner-title-overlay')),
                controls: await banner.locator('a[href], button:not([disabled]), input:not([disabled])').count(),
            };
        };
        try {
            await login(page, environment);
            originalCourseTitle = await getCourseFullname(page, environment);
            const originalForm = await openCourseTitleForm(page, environment);
            originalTitleSettings = await getNamedFormValues(originalForm);
            originalFormat = await getCourseFormat(page, environment);
            await updateCourseFullname(page, environment, longTitle);
            const titleForm = await openCourseTitleForm(page, environment);
            await setNamedFormValues(titleForm, {
                enabled: '1', replacemoodletitle: '0', frameenabled: '1', frametype: 'box',
                framecolor: '#0B2C4D', framebordercolor: '#F5C451', frameopacity: '88',
                frameborderwidth: '2', framepadding: '18', fontsize: '100', x: '50', y: '50', lineheight: '110',
            });
            await saveCourseTitleForm(titleForm, page);
            await setCourseFormat(page, environment, 'fullwidthtopinset');

            await setWindow(1600, 900);
            const desktop100 = await measureZoomSurface('desktop-100');
            const desktopWindowId = nativeChromeZoom('zoom', evidencePath('desktop-chrome-200.png'));
            await page.waitForTimeout(1000);
            const desktop200 = await measureZoomSurface('desktop-200');
            nativeChromeZoom('reset');
            await page.waitForTimeout(1000);
            const desktopReset = await measureZoomSurface('desktop-reset');

            await setWindow(390, 844);
            const mobile100 = await measureZoomSurface('mobile-100');
            const mobileWindowId = nativeChromeZoom('zoom', evidencePath('mobile-chrome-200.png'));
            await page.waitForTimeout(1000);
            const mobile200 = await measureZoomSurface('mobile-200');
            nativeChromeZoom('reset');
            await page.waitForTimeout(1000);
            const mobileReset = await measureZoomSurface('mobile-reset');
            restored.browserZoom = true;

            const achieved = sample => sample.browser.devicePixelRatio >= 1.9 ||
                sample.browser.innerWidth <= Math.max(1, (sample.label.startsWith('desktop') ? desktop100.browser.innerWidth : mobile100.browser.innerWidth) / 1.9);
            await require('fs').promises.writeFile(
                evidencePath('ccb-lot0-native-zoom.json'),
                JSON.stringify({
                    method: 'Visible Chrome window, Windows AppActivate, browser-chrome Ctrl+plus via WScript.SendKeys, five increments, then Ctrl+0 reset.',
                    desktopWindowId,
                    mobileWindowId,
                    desktop: {at100: desktop100, at200: desktop200, reset: desktopReset, achieved: achieved(desktop200)},
                    mobile: {at100: mobile100, at200: mobile200, reset: mobileReset, achieved: achieved(mobile200)},
                }, null, 2)
            );
        } finally {
            try {
                nativeChromeZoom('reset');
                restored.browserZoom = true;
            } catch (error) {
                restored.browserZoom = false;
            }
            if (originalTitleSettings) {
                const restoreForm = await openCourseTitleForm(page, environment);
                await setNamedFormValues(restoreForm, originalTitleSettings);
                await saveCourseTitleForm(restoreForm, page);
                restored.titleSettings = true;
            }
            if (originalCourseTitle !== null) {
                await updateCourseFullname(page, environment, originalCourseTitle);
                restored.courseTitle = true;
            }
            if (originalFormat) {
                await setCourseFormat(page, environment, originalFormat);
                restored.format = await getCourseFormat(page, environment) === originalFormat;
            }
            await require('fs').promises.writeFile(
                evidencePath('ccb-lot0-native-zoom-restoration.json'),
                JSON.stringify({restored}, null, 2)
            );
            await context.tracing.stop({path: evidencePath('ccb-lot0-native-zoom-trace.zip')});
            await context.close();
            await browser.close();
        }
    });

    test('runs the recovery-only, dedicated headed Chrome 200 percent floor comparison', async({}, testInfo) => {
        test.setTimeout(600000);
        const environment = getRequiredEnvironment();
        const evidencePath = name => artifactPath(testInfo, 'lot0-recovery-exclusive-zoom', name);
        const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
        const debuggingPort = 9338;
        const profileDirectory = fs.mkdtempSync(path.join(os.tmpdir(), 'ccb-lot0-recovery-'));
        const longTitle = 'Evaluation pedagogique inclusive : cooperation internationale, accessibilite numerique et reussite durable des apprenants';
        const floors = [128, 136, 144, 152];
        const ratios = {fullwidthtopinset: 6.1};
        const chromeProcess = spawn(chromePath, [
            `--remote-debugging-port=${debuggingPort}`, '--remote-debugging-address=127.0.0.1',
            `--user-data-dir=${profileDirectory}`, '--no-first-run', '--no-default-browser-check',
            '--new-window', `${environment.baseUrl}/login/index.php`,
        ], {stdio: 'ignore', windowsHide: false});
        let browser = null;
        let context = null;
        let page = null;
        let originalCourseTitle = null;
        let originalCourseCategory = null;
        let originalTitleSettings = null;
        let originalFormat = null;
        let restored = {courseTitle: false, courseCategory: false, titleSettings: false, format: false, browserZoom: false};
        const result = {method: 'Dedicated Chrome process with private profile, exact PID AppActivate, Windows Ctrl+plus, and CDP measurement.', profileDirectory, chromeProcessId: chromeProcess.pid, floors, desktop: {}, mobile: {}, accessibility: null};
        const connectDedicatedChrome = async() => {
            let lastError = null;
            for (let attempt = 0; attempt < 40; attempt++) {
                try {
                    return await chromium.connectOverCDP(`http://127.0.0.1:${debuggingPort}`);
                } catch (error) {
                    lastError = error;
                    await new Promise(resolve => setTimeout(resolve, 250));
                }
            }
            throw lastError || new Error('Dedicated Chrome CDP endpoint did not start.');
        };
        const setWindow = async(width, height) => {
            const session = await context.newCDPSession(page);
            const info = await session.send('Browser.getWindowForTarget');
            await session.send('Browser.setWindowBounds', {windowId: info.windowId, bounds: {width, height}});
            await page.waitForTimeout(800);
        };
        const sample = async(label, format, floor = null) => {
            await page.goto(`${environment.baseUrl}/course/view.php?id=${encodeURIComponent(environment.courseId)}`, {waitUntil: 'domcontentloaded'});
            const banner = page.locator('.local-course-banner-builder-native-course-banner').first();
            await expect(banner).toBeVisible();
            const policy = await banner.evaluate((element, input) => {
                const width = element.getBoundingClientRect().width;
                const naturalHeight = width / input.ratio;
                const appliedHeight = input.floor === null ? null : Math.max(naturalHeight, input.floor);
                if (appliedHeight !== null) {
                    element.style.setProperty('min-height', '0px', 'important');
                    element.style.setProperty('max-height', 'none', 'important');
                    element.style.setProperty('height', `${appliedHeight}px`, 'important');
                }
                return {formula: input.floor === null ? 'baseline' : `max(width / ${input.ratio}, ${input.floor}px)`, width, naturalHeight, appliedHeight};
            }, {ratio: ratios[format], floor});
            await page.waitForTimeout(100);
            const container = await measureContainer(banner);
            const title = await measureTitle(banner.locator('.local-course-banner-builder-banner-title-overlay'));
            const edges = title ? {
                left: Number((title.box.x - container.box.x).toFixed(3)),
                right: Number((container.box.x + container.box.width - title.box.x - title.box.width).toFixed(3)),
                top: Number((title.box.y - container.box.y).toFixed(3)),
                bottom: Number((container.box.y + container.box.height - title.box.y - title.box.height).toFixed(3)),
            } : null;
            const reflow = await page.evaluate(() => Math.max(document.documentElement.scrollWidth - document.documentElement.clientWidth, document.body.scrollWidth - document.body.clientWidth));
            const pass = !!title && !title.overflowsX && !title.overflowsY && edges.left >= 0 && edges.right >= 0 && edges.top >= 0 && edges.bottom >= 0 && reflow <= 1;
            return {label, format, floor, policy, browser: await getThemeAndZoom(page), container, title, edges, reflow, pass};
        };
        try {
            browser = await connectDedicatedChrome();
            context = browser.contexts()[0];
            page = context.pages()[0] || await context.newPage();
            await login(page, environment);
            originalCourseTitle = await getCourseFullname(page, environment);
            originalCourseCategory = await getCourseCategory(page, environment);
            originalTitleSettings = await getNamedFormValues(await openCourseTitleForm(page, environment));
            originalFormat = await getCourseFormat(page, environment);
            await fs.promises.writeFile(evidencePath('fixture-initial-state.json'), JSON.stringify({
                courseTitle: originalCourseTitle,
                courseCategory: originalCourseCategory,
                bannerFormat: originalFormat,
                titleSettings: Object.fromEntries(Object.entries(originalTitleSettings)
                    .map(([name, value]) => [name, name === 'sesskey' ? '[redacted]' : value])),
            }, null, 2));

            // The course's original category has no CCB layers. Category 8 is the
            // existing non-production source-chain fixture with eight enabled image layers.
            await updateCourseCategory(page, environment, 8);
            await page.goto(`${environment.baseUrl}/course/view.php?id=${encodeURIComponent(environment.courseId)}`, {waitUntil: 'domcontentloaded'});
            result.sourceRestoration = await page.evaluate(() => ({
                url: location.href,
                pageType: document.body.id || null,
                bodyClasses: Array.from(document.body.classList),
                visibleHeading: document.querySelector('#page-header h1, h1')?.textContent?.trim() || null,
                bannerCount: document.querySelectorAll('.local-course-banner-builder-native-course-banner').length,
                overlayCount: document.querySelectorAll('.local-course-banner-builder-fixed-overlay').length,
                bannerHtml: document.querySelector('.local-course-banner-builder-native-course-banner')?.outerHTML.slice(0, 1600) || null,
            }));
            expect(result.sourceRestoration.bannerCount, 'The supported category-source restoration did not emit a public CCB banner.').toBeGreaterThan(0);
            expect(result.sourceRestoration.overlayCount, 'The restored public CCB banner has no fixed overlay.').toBeGreaterThan(0);
            await updateCourseFullname(page, environment, longTitle);
            const titleForm = await openCourseTitleForm(page, environment);
            await setNamedFormValues(titleForm, {enabled: '1', replacemoodletitle: '0', frameenabled: '1', frametype: 'box', framecolor: '#0B2C4D', framebordercolor: '#F5C451', frameopacity: '88', frameborderwidth: '2', framepadding: '18', fontsize: '100', x: '50', y: '50', lineheight: '110', color: '#FFFFFF'});
            await saveCourseTitleForm(titleForm, page);

            // This non-standard format is already a verified public-banner fixture.
            await setCourseFormat(page, environment, 'fullwidthtopinset');
            await setWindow(1600, 900);
            result.desktop.at100 = await sample('desktop-100-baseline', 'fullwidthtopinset');
            result.desktop.activeWindow100 = nativeChromeZoomForProcess(chromeProcess.pid, 'reset', evidencePath('desktop-100-active-ccb.png'));
            result.desktop.at100AfterReset = await sample('desktop-100-after-reset', 'fullwidthtopinset');
            result.desktop.activeWindow200 = nativeChromeZoomForProcess(chromeProcess.pid, 'zoom', evidencePath('desktop-200-chrome-indicator.png'));
            result.desktop.at200 = [];
            for (const floor of floors) {
                result.desktop.at200.push(await sample(`desktop-200-${floor}`, 'fullwidthtopinset', floor));
            }
            await page.screenshot({path: evidencePath('desktop-200-page.png'), fullPage: true});
            result.desktop.resetWindow = nativeChromeZoomForProcess(chromeProcess.pid, 'reset', evidencePath('desktop-reset-active-ccb.png'));

            await setCourseFormat(page, environment, 'fullwidthtopinset');
            await setWindow(390, 844);
            result.mobile.at100 = await sample('mobile-100-baseline', 'fullwidthtopinset');
            result.mobile.activeWindow100 = nativeChromeZoomForProcess(chromeProcess.pid, 'reset', evidencePath('mobile-100-active-ccb.png'));
            result.mobile.at100AfterReset = await sample('mobile-100-after-reset', 'fullwidthtopinset');
            result.mobile.activeWindow200 = nativeChromeZoomForProcess(chromeProcess.pid, 'zoom', evidencePath('mobile-200-chrome-indicator.png'));
            result.mobile.at200 = [];
            for (const floor of floors) {
                result.mobile.at200.push(await sample(`mobile-200-${floor}`, 'fullwidthtopinset', floor));
            }
            await page.screenshot({path: evidencePath('mobile-200-page.png'), fullPage: true});
            await page.keyboard.press('Tab');
            result.accessibility = await page.evaluate(() => ({
                bannerAriaHidden: document.querySelector('.local-course-banner-builder-native-course-banner')?.getAttribute('aria-hidden') || null,
                titleAriaHidden: document.querySelector('.local-course-banner-builder-banner-title-overlay')?.getAttribute('aria-hidden') || null,
                bannerFocusables: Array.from(document.querySelectorAll('.local-course-banner-builder-native-course-banner a[href], .local-course-banner-builder-native-course-banner button:not([disabled]), .local-course-banner-builder-native-course-banner input:not([disabled])')).map(element => ({tag: element.tagName, text: (element.textContent || '').trim(), ariaLabel: element.getAttribute('aria-label')})),
                activeElement: {tag: document.activeElement?.tagName || null, text: (document.activeElement?.textContent || '').trim(), outline: getComputedStyle(document.activeElement).outline},
            }));
            await page.screenshot({path: evidencePath('mobile-200-focus.png'), fullPage: true});
            result.mobile.resetWindow = nativeChromeZoomForProcess(chromeProcess.pid, 'reset', evidencePath('mobile-reset-active-ccb.png'));
            restored.browserZoom = true;
            const allCandidates = floors.map(floor => ({floor, desktop: result.desktop.at200.find(sample => sample.floor === floor), mobile: result.mobile.at200.find(sample => sample.floor === floor)}));
            result.candidateDecision = allCandidates.map(candidate => ({floor: candidate.floor, pass: candidate.desktop.pass && candidate.mobile.pass, desktop: candidate.desktop.pass, mobile: candidate.mobile.pass}));
            result.selectedFloor = (result.candidateDecision.find(candidate => candidate.pass) || {}).floor || null;
            await fs.promises.writeFile(evidencePath('ccb-lot0-recovery-zoom.json'), JSON.stringify(result, null, 2));
            expect(result.selectedFloor, 'No tested shared floor passed both genuine-200 desktop and mobile acceptance gates.').not.toBeNull();
        } finally {
            if (page) {
                try { nativeChromeZoomForProcess(chromeProcess.pid, 'reset', evidencePath('final-reset-active-ccb.png')); restored.browserZoom = true; } catch (error) { restored.browserZoom = false; }
                if (originalTitleSettings) { const restoreForm = await openCourseTitleForm(page, environment); await setNamedFormValues(restoreForm, originalTitleSettings); await saveCourseTitleForm(restoreForm, page); restored.titleSettings = true; }
                if (originalCourseTitle !== null) { await updateCourseFullname(page, environment, originalCourseTitle); restored.courseTitle = true; }
                if (originalFormat) { await setCourseFormat(page, environment, originalFormat); restored.format = await getCourseFormat(page, environment) === originalFormat; }
                if (originalCourseCategory) { await updateCourseCategory(page, environment, originalCourseCategory.id); restored.courseCategory = (await getCourseCategory(page, environment)).id === originalCourseCategory.id; }
            }
            await fs.promises.writeFile(evidencePath('ccb-lot0-recovery-restoration.json'), JSON.stringify({restored}, null, 2));
            if (browser) { await browser.close(); }
            if (!chromeProcess.killed) { chromeProcess.kill(); }
        }
    });

    test('captures Lot 0C checkpointed real-Chrome evidence without changing CCB state', async() => {
        const testTimeoutMs = 540000;
        const externalTimeoutMs = Number(process.env.EASYEDU_CCB_LOT0C_EXTERNAL_TIMEOUT_MS || 600000);
        test.setTimeout(testTimeoutMs);
        const environment = getRequiredEnvironment();
        if (String(environment.courseId) !== '2') {
            throw new Error(`Lot 0C is restricted to course id 2, not ${environment.courseId}.`);
        }
        const artifactBase = process.env.EASYEDU_CCB_ARTIFACT_ROOT;
        if (!artifactBase) {
            throw new Error('EASYEDU_CCB_ARTIFACT_ROOT must name a new evidence root for Lot 0C.');
        }
        const runId = `${new Date().toISOString().replace(/[:.]/g, '-')}-${process.pid}-${crypto.randomBytes(3).toString('hex')}`;
        const runRoot = path.resolve(artifactBase, `ccb-policy-lot0c-checkpointed-${runId}`);
        if (fs.existsSync(runRoot)) {
            throw new Error(`Lot 0C evidence directory already exists: ${runRoot}`);
        }
        fs.mkdirSync(runRoot, {recursive: true});
        const checkpointRoot = path.join(runRoot, 'checkpoints');
        const artifact = name => path.join(runRoot, name);
        const candidates = [128, 136, 144, 152];
        const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
        if (!fs.existsSync(chromePath)) {
            throw new Error(`Required headed Chrome executable was not found: ${chromePath}`);
        }
        const debuggingPort = Number(process.env.EASYEDU_CCB_LOT0C_PORT || 9351);
        const profileDirectory = fs.mkdtempSync(path.join(os.tmpdir(), 'ccb-lot0c-'));
        const chromeProcess = spawn(chromePath, [
            `--remote-debugging-port=${debuggingPort}`, '--remote-debugging-address=127.0.0.1',
            `--user-data-dir=${profileDirectory}`, '--no-first-run', '--no-default-browser-check',
            '--new-window', `${environment.baseUrl}/login/index.php`,
        ], {stdio: 'ignore', windowsHide: false});
        const startedAt = Date.now();
        const timings = [];
        const cleanup = {zoomAt100: false, categoryAt3: false, attempts: [], error: null};
        const result = {
            runId,
            courseId: String(environment.courseId),
            candidates,
            method: 'Dedicated headed Google Chrome, exact process AppActivate, Windows Ctrl+0/Ctrl+plus, CDP measurement; no viewport emulation, transform, canvas or test-side CCB style injection.',
            testTimeoutMs,
            externalTimeoutMs,
            artifacts: {root: runRoot},
            candidateResults: [],
            cleanup,
            error: null,
        };
        let browser = null;
        let context = null;
        let page = null;
        let observedCategory = null;

        const overlaps = (first, second) => {
            if (!first || !second) {
                return false;
            }
            return first.x < second.right && first.right > second.x && first.y < second.bottom && first.bottom > second.y;
        };
        const captureSurface = async() => {
            if (!page) {
                return {
                    url: null, viewport: null, wrapper: null, overlays: [], header: null,
                    breadcrumb: null, title: null, content: null, accessibility: null,
                };
            }
            return page.evaluate(() => {
                const rect = element => {
                    if (!element) {
                        return null;
                    }
                    const box = element.getBoundingClientRect();
                    return {x: box.x, y: box.y, width: box.width, height: box.height, right: box.right, bottom: box.bottom};
                };
                const isVisible = element => {
                    if (!element) {
                        return false;
                    }
                    const style = getComputedStyle(element);
                    const box = element.getBoundingClientRect();
                    return style.display !== 'none' && style.visibility !== 'hidden' && box.width > 0 && box.height > 0;
                };
                const nearestClippingAncestors = element => {
                    const ancestors = [];
                    let ancestor = element ? element.parentElement : null;
                    while (ancestor && ancestors.length < 8) {
                        const style = getComputedStyle(ancestor);
                        if (/(hidden|clip|auto|scroll)/.test(`${style.overflow} ${style.overflowX} ${style.overflowY}`)) {
                            ancestors.push({tag: ancestor.tagName, id: ancestor.id || null, className: ancestor.className || null, overflow: `${style.overflow} ${style.overflowX} ${style.overflowY}`, box: rect(ancestor)});
                        }
                        ancestor = ancestor.parentElement;
                    }
                    return ancestors;
                };
                const banner = document.querySelector('.local-course-banner-builder-native-course-banner');
                const overlays = Array.from(document.querySelectorAll('.local-course-banner-builder-fixed-overlay'));
                const header = document.querySelector('#page-header, header');
                const breadcrumb = document.querySelector('nav[aria-label*="breadcrumb" i], .breadcrumb');
                const visualTitle = document.querySelector('.local-course-banner-builder-banner-title-overlay');
                const heading = document.querySelector('#page-header h1, h1');
                const content = document.querySelector('#region-main .course-content, #region-main [data-region="course-content"], .course-content');
                const ariaHiddenAncestors = element => {
                    const hidden = [];
                    let ancestor = element;
                    while (ancestor) {
                        if (ancestor.getAttribute && ancestor.getAttribute('aria-hidden') === 'true') {
                            hidden.push({tag: ancestor.tagName, id: ancestor.id || null, className: ancestor.className || null});
                        }
                        ancestor = ancestor.parentElement;
                    }
                    return hidden;
                };
                return {
                    url: location.href,
                    viewport: {
                        innerWidth: window.innerWidth, innerHeight: window.innerHeight,
                        outerWidth: window.outerWidth, outerHeight: window.outerHeight,
                        devicePixelRatio: window.devicePixelRatio,
                        visualViewport: window.visualViewport ? {
                            width: window.visualViewport.width, height: window.visualViewport.height,
                            scale: window.visualViewport.scale,
                        } : null,
                        horizontalOverflow: Math.max(0, document.documentElement.scrollWidth - document.documentElement.clientWidth),
                    },
                    wrapper: banner ? {
                        count: document.querySelectorAll('.local-course-banner-builder-native-course-banner').length,
                        visible: isVisible(banner), box: rect(banner), overflow: getComputedStyle(banner).overflow,
                        clippingAncestors: nearestClippingAncestors(banner),
                    } : {count: 0, visible: false, box: null, overflow: null, clippingAncestors: []},
                    overlays: overlays.map((overlay, index) => ({
                        index, visible: isVisible(overlay), box: rect(overlay), overflow: getComputedStyle(overlay).overflow,
                        clippingAncestors: nearestClippingAncestors(overlay),
                    })),
                    header: {box: rect(header), visible: isVisible(header)},
                    breadcrumb: {box: rect(breadcrumb), visible: isVisible(breadcrumb), text: breadcrumb ? (breadcrumb.textContent || '').trim() : null},
                    title: {
                        visual: visualTitle ? {box: rect(visualTitle), visible: isVisible(visualTitle), ariaHidden: visualTitle.getAttribute('aria-hidden'), ariaHiddenAncestors: ariaHiddenAncestors(visualTitle)} : null,
                        h1: heading ? {box: rect(heading), visible: isVisible(heading), text: (heading.textContent || '').trim(), ariaHidden: heading.getAttribute('aria-hidden'), ariaHiddenAncestors: ariaHiddenAncestors(heading)} : null,
                    },
                    content: {box: rect(content), visible: isVisible(content)},
                    accessibility: {
                        h1s: Array.from(document.querySelectorAll('h1')).map(element => ({
                            text: (element.textContent || '').trim(), visible: isVisible(element), ariaHidden: element.getAttribute('aria-hidden'), ariaHiddenAncestors: ariaHiddenAncestors(element),
                        })),
                    },
                };
            });
        };
        const assess = (surface, expectedOverlayCount, zoomAchieved = true) => {
            const wrapper = surface.wrapper;
            const wrapperBox = wrapper && wrapper.box;
            const overlayContainment = wrapperBox && surface.overlays.length ? surface.overlays.every(overlay => {
                const box = overlay.box;
                return box && box.x >= wrapperBox.x - 0.5 && box.y >= wrapperBox.y - 0.5 && box.right <= wrapperBox.right + 0.5 && box.bottom <= wrapperBox.bottom + 0.5;
            }) : false;
            const titleBox = surface.title.visual ? surface.title.visual.box : (surface.title.h1 ? surface.title.h1.box : null);
            const titleBreadcrumb = titleBox && surface.breadcrumb.box ? (overlaps(titleBox, surface.breadcrumb.box) ? 'fail' : 'pass') : 'not-applicable';
            const contentOverlap = wrapperBox && surface.content.box ? overlaps(wrapperBox, surface.content.box) : false;
            const checks = {
                wrapper: wrapper && wrapper.count === 1 && wrapper.visible ? 'pass' : 'fail',
                expectedOverlayCount: surface.overlays.length === expectedOverlayCount ? 'pass' : 'fail',
                overlayContainment: overlayContainment ? 'pass' : 'fail',
                clipping: surface.overlays.every(overlay => overlay.visible && overlay.clippingAncestors.every(ancestor => !ancestor.box || !overlay.box || (overlay.box.x >= ancestor.box.x - 0.5 && overlay.box.y >= ancestor.box.y - 0.5 && overlay.box.right <= ancestor.box.right + 0.5 && overlay.box.bottom <= ancestor.box.bottom + 0.5))) ? 'pass' : 'fail',
                contentOverlap: contentOverlap ? 'fail' : (surface.content.box ? 'pass' : 'not-applicable'),
                titleBreadcrumb,
                horizontalOverflow: surface.viewport && surface.viewport.horizontalOverflow <= 1 ? 'pass' : 'fail',
                genuine200: zoomAchieved ? 'pass' : 'fail',
            };
            const fixtureStatus = Object.values(checks).some(value => value === 'fail') ? 'fail' : 'pass';
            return {
                fixtureStatus,
                candidateStatus: fixtureStatus === 'fail' ? 'fail' : 'indeterminate',
                checks,
                note: 'Candidate floors are recorded without injecting a CSS formula, inline height, transform or renderer change. Policy selection remains indeterminate until an implementation phase applies a reviewed policy.',
            };
        };
        const checkpoint = async(checkpointName, extra = {}) => {
            const checkpointStartedAt = Date.now();
            let surface;
            let surfaceError = null;
            try {
                surface = await captureSurface();
            } catch (error) {
                surfaceError = String(error && error.message || error);
                surface = {url: page ? page.url() : null, viewport: null, wrapper: null, overlays: [], header: null};
            }
            const payload = {
                timestamp: new Date().toISOString(), checkpoint: checkpointName, runId,
                url: surface.url || (page ? page.url() : null), courseId: String(environment.courseId),
                observedCategory, expectedCategory: extra.expectedCategory ?? null,
                candidate: extra.candidate ?? null, requestedZoom: extra.requestedZoom ?? null,
                viewport: surface.viewport ?? null, wrapper: surface.wrapper ?? null,
                overlays: surface.overlays ?? [], header: surface.header ?? null,
                assertion: extra.assertion ?? null, screenshot: extra.screenshot ?? null,
                error: extra.error ?? surfaceError, cleanup: extra.cleanup ?? cleanup,
                accessibility: surface.accessibility ?? null, title: surface.title ?? null,
                breadcrumb: surface.breadcrumb ?? null, content: surface.content ?? null,
                artifacts: result.artifacts,
            };
            writeAtomicJson(path.join(checkpointRoot, `${checkpointName}.json`), payload);
            timings.push({checkpoint: checkpointName, durationMs: Date.now() - checkpointStartedAt});
            return payload;
        };
        const connectDedicated = async() => {
            let lastError = null;
            for (let attempt = 0; attempt < 40; attempt++) {
                try {
                    return await chromium.connectOverCDP(`http://127.0.0.1:${debuggingPort}`);
                } catch (error) {
                    lastError = error;
                    await new Promise(resolve => setTimeout(resolve, 250));
                }
            }
            throw lastError || new Error('Dedicated Chrome CDP endpoint did not start.');
        };
        const setWindow = async() => {
            const session = await context.newCDPSession(page);
            const info = await session.send('Browser.getWindowForTarget');
            await session.send('Browser.setWindowBounds', {windowId: info.windowId, bounds: {width: 1600, height: 900}});
            await page.waitForTimeout(300);
        };
        const readCategory = async() => {
            observedCategory = await getCourseCategory(page, environment);
            return observedCategory;
        };
        const resetZoom = async(screenshotName) => {
            const before = await captureSurface();
            const nativeEvidence = nativeChromeZoomForProcess(chromeProcess.pid, 'reset', artifact(screenshotName));
            const after = await captureSurface();
            return {nativeEvidence, before: before.viewport, after: after.viewport};
        };
        const zoomTo200 = async(baselineViewport, screenshotName) => {
            const nativeEvidence = nativeChromeZoomForProcess(chromeProcess.pid, 'zoom', artifact(screenshotName));
            await page.waitForFunction(before => window.devicePixelRatio / Math.max(before.devicePixelRatio, 1) >= 1.9 || before.innerWidth / Math.max(window.innerWidth, 1) >= 1.9, baselineViewport, {timeout: 5000}).catch(() => {});
            const after = await captureSurface();
            const dprRatio = after.viewport && baselineViewport ? after.viewport.devicePixelRatio / Math.max(baselineViewport.devicePixelRatio, 1) : 0;
            const widthRatio = after.viewport && baselineViewport ? baselineViewport.innerWidth / Math.max(after.viewport.innerWidth, 1) : 0;
            return {nativeEvidence, after: after.viewport, dprRatio, widthRatio, achieved: dprRatio >= 1.9 || widthRatio >= 1.9};
        };
        const restoreCategory3 = async() => {
            for (let attempt = 1; attempt <= 2; attempt++) {
                try {
                    const current = await readCategory();
                    if (String(current.id) !== '3') {
                        await updateCourseCategory(page, environment, 3);
                    }
                    const confirmed = await readCategory();
                    const attemptResult = {attempt, before: current, after: confirmed, restored: String(confirmed.id) === '3', error: null};
                    cleanup.attempts.push(attemptResult);
                    await checkpoint(`cleanup-category-attempt-${attempt}`, {expectedCategory: '3', assertion: attemptResult, cleanup});
                    if (attemptResult.restored) {
                        cleanup.categoryAt3 = true;
                        return;
                    }
                } catch (error) {
                    const attemptResult = {attempt, restored: false, error: String(error && error.message || error)};
                    cleanup.attempts.push(attemptResult);
                    await checkpoint(`cleanup-category-attempt-${attempt}`, {expectedCategory: '3', assertion: attemptResult, error: attemptResult.error, cleanup}).catch(() => {});
                }
            }
            cleanup.error = 'Course category could not be confirmed as 3 after two UI restoration attempts.';
        };

        try {
            await checkpoint('started', {expectedCategory: '3'});
            browser = await connectDedicated();
            context = browser.contexts()[0];
            page = context.pages()[0] || await context.newPage();
            await setWindow();
            await checkpoint('browser-connected', {expectedCategory: '3'});
            await login(page, environment);
            await checkpoint('authenticated', {expectedCategory: '3'});
            const initialCategory = await readCategory();
            await checkpoint('initial-category-observed', {expectedCategory: '3', assertion: {status: String(initialCategory.id) === '3' ? 'pass' : 'fail', initialCategory}});
            if (String(initialCategory.id) === '8') {
                await updateCourseCategory(page, environment, 3);
                const repairedCategory = await readCategory();
                await checkpoint('initial-category-8-restored', {expectedCategory: '3', assertion: {status: String(repairedCategory.id) === '3' ? 'pass' : 'fail', repairedCategory}});
                expect(String(repairedCategory.id), 'Course 2 could not be restored to category 3 before Lot 0C evidence collection.').toBe('3');
            } else if (String(initialCategory.id) !== '3') {
                throw new Error(`Course 2 must start in category 3 or be recovered from category 8; found category ${initialCategory.id}.`);
            }
            await page.goto(`${environment.baseUrl}/course/view.php?id=${encodeURIComponent(environment.courseId)}`, {waitUntil: 'domcontentloaded'});
            await expect(page.locator('.local-course-banner-builder-native-course-banner')).toHaveCount(0);
            await expect(page.locator('.local-course-banner-builder-fixed-overlay')).toHaveCount(0);
            await checkpoint('category-3-public-verified', {expectedCategory: '3', assertion: {status: 'pass', wrapperExpected: 0, overlaysExpected: 0}});

            await updateCourseCategory(page, environment, 8);
            await checkpoint('category-8-move-submitted', {expectedCategory: '8'});
            const category8 = await readCategory();
            await checkpoint('category-8-ui-confirmed', {expectedCategory: '8', assertion: {status: String(category8.id) === '8' ? 'pass' : 'fail', category8}});
            expect(String(category8.id), 'Course category update through the Moodle edit form did not select category 8.').toBe('8');
            await page.goto(`${environment.baseUrl}/course/view.php?id=${encodeURIComponent(environment.courseId)}`, {waitUntil: 'domcontentloaded'});
            const banner = page.locator('.local-course-banner-builder-native-course-banner');
            const overlays = page.locator('.local-course-banner-builder-fixed-overlay');
            await expect(banner).toHaveCount(1);
            await expect(banner).toBeVisible({timeout: 15000});
            await expect(overlays).toHaveCount(8);
            await checkpoint('category-8-public-verified', {expectedCategory: '8', assertion: {status: 'pass', wrapperExpected: 1, overlaysExpected: 8}});

            for (const candidate of candidates) {
                const reset = await resetZoom(`candidate-${candidate}-100-chrome.png`);
                const at100 = await captureSurface();
                const at100Screenshot = `candidate-${candidate}-100-viewport.png`;
                await page.screenshot({path: artifact(at100Screenshot), fullPage: false});
                const at100Assessment = assess(at100, 8, true);
                await checkpoint(`candidate-${candidate}-100`, {
                    expectedCategory: '8', candidate, requestedZoom: 100, screenshot: at100Screenshot,
                    assertion: {...at100Assessment, zoom: reset},
                });

                const at200Zoom = await zoomTo200(at100.viewport, `candidate-${candidate}-200-chrome.png`);
                const at200 = await captureSurface();
                const at200Screenshot = `candidate-${candidate}-200-viewport.png`;
                await page.screenshot({path: artifact(at200Screenshot), fullPage: false});
                const at200Assessment = assess(at200, 8, at200Zoom.achieved);
                await checkpoint(`candidate-${candidate}-200`, {
                    expectedCategory: '8', candidate, requestedZoom: 200, screenshot: at200Screenshot,
                    assertion: {...at200Assessment, zoom: at200Zoom},
                });
                const candidateResult = {candidate, at100: {surface: at100, assessment: at100Assessment, screenshot: at100Screenshot}, at200: {surface: at200, assessment: at200Assessment, screenshot: at200Screenshot, zoom: at200Zoom}};
                result.candidateResults.push(candidateResult);
                writeAtomicJson(artifact(`candidate-${candidate}.json`), candidateResult);
                if (!at200Zoom.achieved) {
                    throw new Error(`Genuine 200% zoom was not proven for candidate ${candidate}.`);
                }
            }
            result.policyRecommendation = {
                status: 'blocked',
                reason: 'The Lot 0C scope forbids applying a candidate floor or changing CCB state. The evidence is suitable to gate a later policy implementation, but cannot select a production formula itself.',
            };
            await checkpoint('evidence-captured', {expectedCategory: '8', assertion: result.policyRecommendation});
        } catch (error) {
            result.error = String(error && error.message || error);
            await checkpoint('error', {expectedCategory: observedCategory ? String(observedCategory.id) : null, error: result.error}).catch(() => {});
            throw error;
        } finally {
            await checkpoint('cleanup-started', {expectedCategory: '3', cleanup}).catch(() => {});
            if (page) {
                try {
                    const zoomReset = await resetZoom('cleanup-100-chrome.png');
                    cleanup.zoomAt100 = !!zoomReset.after;
                    await checkpoint('cleanup-zoom-100', {expectedCategory: '3', assertion: zoomReset, cleanup});
                } catch (error) {
                    cleanup.error = String(error && error.message || error);
                    await checkpoint('cleanup-zoom-error', {expectedCategory: '3', error: cleanup.error, cleanup}).catch(() => {});
                }
                await restoreCategory3();
            }
            await checkpoint('completed', {expectedCategory: '3', assertion: {cleanupSucceeded: cleanup.categoryAt3}, cleanup}).catch(() => {});
            result.timing = {
                elapsedMs: Date.now() - startedAt,
                testTimeoutMs,
                externalTimeoutMs,
                slowestCheckpoint: timings.sort((first, second) => second.durationMs - first.durationMs)[0] || null,
                checkpointCount: timings.length,
            };
            writeAtomicJson(artifact('run-summary.json'), result);
            if (browser) {
                await browser.close().catch(() => {});
            }
            if (!chromeProcess.killed) {
                chromeProcess.kill();
            }
            if (!cleanup.categoryAt3) {
                throw new Error('Lot 0C cleanup failed: course 2 was not confirmed in category 3 after two UI retries.');
            }
        }
    });

    test(`records the required format and viewport matrix (${formatLabel}; ${viewportLabel}), then restores the original format`, async({browser}, testInfo) => {
        test.setTimeout(600000);
        const environment = getRequiredEnvironment();
        const context = await browser.newContext({viewport: {width: 1600, height: 900}});
        const evidencePath = name => artifactPath(testInfo, `matrix-${formatLabel}-${viewportLabel}`, name);
        const page = await context.newPage();
        let originalFormat = null;
        let zoomAttempt = null;
        const matrix = [];
        await context.tracing.start({screenshots: true, snapshots: true, sources: true});
        try {
            await login(page, environment);
            const themeAndZoom = await getThemeAndZoom(page);
            if (process.env.EASYEDU_CCB_THEME && themeAndZoom.theme !== process.env.EASYEDU_CCB_THEME) {
                throw new Error(`Expected Moodle theme ${process.env.EASYEDU_CCB_THEME}, found ${themeAndZoom.theme || 'unknown'}.`);
            }
            originalFormat = await getCourseFormat(page, environment);
            for (const format of formats) {
                await setCourseFormat(page, environment, format);
                for (const viewport of viewports) {
                    await page.setViewportSize({width: viewport.width, height: viewport.height});
                    if (zoomAttempt === null) {
                        zoomAttempt = await attemptGenuineBrowserZoom(page);
                    }
                    const admin = await openAdminPreview(page, environment);
                    const publicBanner = await openPublicBanner(page, environment);
                    expect(admin.container.className).toContain(`--format-${format}`);
                    matrix.push({
                        format,
                        viewport,
                        zoomAttempt,
                        themeAndZoom: await getThemeAndZoom(page),
                        admin,
                        public: publicBanner,
                        comparison: compareLayers(admin.layers, publicBanner.layers),
                    });
                    if (process.env.EASYEDU_CCB_CAPTURE_SCREENSHOTS === '1') {
                        await page.screenshot({
                            path: evidencePath(`public-${format}-${viewport.name}.png`),
                            fullPage: true,
                        });
                        await page.goto(`${environment.baseUrl}/local/course_banner_builder/admin_manage.php`, {
                            waitUntil: 'domcontentloaded',
                        });
                        await page.locator('[data-action="local-course-banner-builder-show-source-chain-preview"]').first().click();
                        await page.screenshot({
                            path: evidencePath(`admin-${format}-${viewport.name}.png`),
                            fullPage: true,
                        });
                    }
                }
            }
            await require('fs').promises.writeFile(
                evidencePath(`ccb-format-viewport-matrix-${formatLabel}-${viewportLabel}.json`),
                JSON.stringify({originalFormat, matrix}, null, 2)
            );
        } finally {
            if (originalFormat) {
                await setCourseFormat(page, environment, originalFormat);
                const restoredFormat = await getCourseFormat(page, environment);
                if (restoredFormat !== originalFormat) {
                    throw new Error(`CCB format restoration failed: expected ${originalFormat}, found ${restoredFormat}.`);
                }
            }
            await context.tracing.stop({path: evidencePath(`ccb-format-viewport-matrix-${formatLabel}-${viewportLabel}-trace.zip`)});
            await context.close();
        }
    });
});
