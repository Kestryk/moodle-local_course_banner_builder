const fs = require('fs');
const path = require('path');
const {chromium} = require('playwright');

const required = ['EASYEDU_MOODLE_URL', 'EASYEDU_MOODLE_USERNAME', 'EASYEDU_MOODLE_PASSWORD'];
const missing = required.filter(name => !process.env[name]);
if (missing.length) {
    throw new Error(`Missing button-probe environment values: ${missing.join(', ')}.`);
}

const root = path.join(process.env.LOCALAPPDATA, 'EasyEdu', 'artifacts', 'design-system-component-probes');
const runDirectory = path.join(root, `ccb-save-preview-${new Date().toISOString().replace(/[:.]/g, '')}`);
const env = {
    baseUrl: String(process.env.EASYEDU_MOODLE_URL).replace(/\/$/, ''),
    categoryId: String(process.env.EASYEDU_CCB_LARGE_AUTHORING_SOURCE_CATEGORY_ID || '4'),
    username: process.env.EASYEDU_MOODLE_USERNAME,
    password: process.env.EASYEDU_MOODLE_PASSWORD,
};

const extract = node => {
    const style = getComputedStyle(node);
    const icon = node.querySelector('.fa, .icon');
    const iconStyle = icon ? getComputedStyle(icon) : null;
    const properties = [
        'alignItems', 'backgroundColor', 'borderBottomLeftRadius', 'borderBottomRightRadius',
        'borderBottomWidth', 'borderColor', 'borderLeftWidth', 'borderRightWidth',
        'borderTopLeftRadius', 'borderTopRightRadius', 'borderTopWidth', 'boxShadow', 'color',
        'cursor', 'display', 'fontFamily', 'fontSize', 'fontStyle', 'fontWeight', 'gap', 'height',
        'justifyContent', 'letterSpacing', 'lineHeight', 'minHeight', 'opacity', 'paddingBottom', 'paddingLeft',
        'paddingRight', 'paddingTop', 'textTransform', 'transitionDuration', 'transitionProperty',
        'transitionTimingFunction', 'width',
    ];
    const rect = node.getBoundingClientRect();
    return {
        html: node.outerHTML,
        accessible: {
            ariaLabel: node.getAttribute('aria-label'),
            disabled: node.disabled,
            text: (node.textContent || '').replace(/\s+/g, ' ').trim(),
            type: node.getAttribute('type'),
        },
        classes: Array.from(node.classList),
        box: {width: Math.round(rect.width * 100) / 100, height: Math.round(rect.height * 100) / 100},
        computed: Object.fromEntries(properties.map(name => [name, style[name]])),
        cssVariables: Object.fromEntries([
            '--easyedu-control-height', '--easyedu-primary', '--easyedu-primary-strong',
            '--easyedu-primary-soft', '--easyedu-control-focus-border', '--easyedu-focus-ring',
            '--easyedu-focus-ring-width', '--easyedu-focus-outline-width',
            '--easyedu-focus-outline-offset',
        ].map(name => [name, style.getPropertyValue(name).trim()])),
        icon: icon ? {
            classes: Array.from(icon.classList),
            color: iconStyle.color,
            fontFamily: iconStyle.fontFamily,
            fontSize: iconStyle.fontSize,
            fontWeight: iconStyle.fontWeight,
            lineHeight: iconStyle.lineHeight,
            pseudoBefore: {
                content: getComputedStyle(icon, '::before').content,
                fontFamily: getComputedStyle(icon, '::before').fontFamily,
                fontWeight: getComputedStyle(icon, '::before').fontWeight,
            },
        } : null,
    };
};

const extractFontEvidence = node => {
    const style = getComputedStyle(node);
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');
    const specimen = 'Save preview changes AaWw12345';
    const measure = family => {
        context.font = `600 12.48px ${family}`;
        return Math.round(context.measureText(specimen).width * 1000) / 1000;
    };
    return {
        cssDeclaration: style.fontFamily,
        browserCanUse: {
            segoeUi: document.fonts.check('600 12.48px "Segoe UI"'),
            notoSans: document.fonts.check('600 12.48px "Noto Sans"'),
        },
        textWidths: {
            declared: measure(style.fontFamily),
            systemUi: measure('system-ui'),
            segoeUi: measure('"Segoe UI"'),
            notoSans: measure('"Noto Sans"'),
        },
    };
};

const login = async page => {
    await page.goto(`${env.baseUrl}/login/index.php`, {waitUntil: 'domcontentloaded'});
    if (!/\/login\//.test(page.url())) {
        return;
    }
    await page.locator('#username').fill(env.username);
    await page.locator('#password').fill(env.password);
    await Promise.all([
        page.waitForURL(url => !/\/login\//.test(url.pathname), {waitUntil: 'domcontentloaded'}),
        page.locator('#loginbtn').click(),
    ]);
};

const main = async() => {
    fs.mkdirSync(runDirectory, {recursive: true});
    const browser = await chromium.launch({headless: true});
    const page = await browser.newPage({viewport: {width: 1440, height: 900}, deviceScaleFactor: 2});
    try {
        await login(page);
        await page.goto(
            `${env.baseUrl}/local/course_banner_builder/admin_manage.php?sourcekey=category:${encodeURIComponent(env.categoryId)}`,
            {waitUntil: 'domcontentloaded'}
        );
        const button = page.locator('[data-source-preview-save-control="1"]').first();
        await button.waitFor({state: 'visible', timeout: 60000});
        await button.scrollIntoViewIfNeeded();

        await button.screenshot({path: path.join(runDirectory, 'default.png')});
        const defaultState = await button.evaluate(extract);
        const fontEvidence = await button.evaluate(extractFontEvidence);

        await button.hover();
        await button.screenshot({path: path.join(runDirectory, 'hover.png')});
        const hoverState = await button.evaluate(extract);

        await button.focus();
        await button.screenshot({path: path.join(runDirectory, 'focus.png')});
        const focusState = await button.evaluate(extract);

        await button.evaluate(node => {
            node.blur();
            node.disabled = true;
        });
        await page.mouse.move(1400, 850);
        await button.screenshot({path: path.join(runDirectory, 'disabled.png')});
        const disabledState = await button.evaluate(extract);

        fs.writeFileSync(path.join(runDirectory, 'source.json'), JSON.stringify({
            component: 'CCB / Button / Primary / Save preview changes',
            source: {
                url: page.url(),
                selector: '[data-source-preview-save-control="1"]',
                nonMutating: true,
            },
            fontEvidence,
            states: {default: defaultState, hover: hoverState, focus: focusState, disabled: disabledState},
        }, null, 2));
        process.stdout.write(`${runDirectory}\n`);
    } finally {
        await browser.close();
    }
};

main().catch(error => {
    process.stderr.write(`${error.stack || error.message}\n`);
    process.exitCode = 1;
});
