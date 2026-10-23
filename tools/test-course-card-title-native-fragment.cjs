/* eslint-env node */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
const {chromium} = require(process.env.EASYEDU_PLAYWRIGHT_MODULE || 'playwright');
const fixture = fs.readFileSync(path.join(root, 'tests/fixtures/coursecard-moodle53.html'), 'utf8');
const css = fs.readFileSync(path.join(root, 'styles.css'), 'utf8');

(async() => {
    const browser = await chromium.launch({headless: true, executablePath: process.env.EASYEDU_CHROME_PATH});
    let checks = 0;
    try {
        for (const moduleFile of ['amd/src/coursecards.js', 'amd/build/coursecards.min.js']) {
            for (const variant of ['full', 'fallback', 'plain', 'disabled']) {
                const page = await browser.newPage({viewport: {width: 390, height: 800}});
                const errors = [];
                page.on('pageerror', error => errors.push(error.stack));
                await page.route('**/*', route => route.abort());
                await page.setContent(fixture);
                await page.addStyleTag({content: css + '\n.course-card { width:320px; } .card-img-top { height:112px; }'});
                const expected = await page.evaluate(variant => {
                    const name = document.querySelector('.coursename .multiline');
                    const expected = name.getAttribute('title');
                    if (variant === 'fallback') {
                        name.removeAttribute('title');
                        name.querySelector('[aria-hidden="true"]').textContent = expected;
                    }
                    if (variant === 'plain') {
                        document.querySelector('.card-img-top').style.backgroundImage = 'url("https://example.invalid/teacher.png")';
                    }
                    // The real fixture contains a titled star, hidden full name,
                    // visible shortened duplicate and screen-reader labels.
                    window.define = (...args) => {
                        window.cardModule = args[args.length - 1]({wwwroot: 'https://example.invalid'});
                    };
                    return expected;
                }, variant);
                await page.addScriptTag({content: fs.readFileSync(path.join(root, moduleFile), 'utf8')});
                const result = await page.evaluate(variant => {
                    const options = {enabled: true, cardTitle: {enabled: variant !== 'disabled'}};
                    window.cardModule.init(null, options);
                    window.cardModule.init(null, options);
                    const title = document.querySelector('.local-course-banner-builder-course-card-title');
                    const text = title?.querySelector('.local-course-banner-builder-course-card-title__text');
                    const style = text && getComputedStyle(text);
                    return {count: document.querySelectorAll('.local-course-banner-builder-course-card-title').length,
                        text: title?.textContent, clamp: style?.webkitLineClamp, padding: style?.padding,
                        height: text?.getBoundingClientRect().height, lineHeight: parseFloat(style?.lineHeight)};
                }, variant);
                if (variant === 'plain' || variant === 'disabled') {
                    assert.equal(result.count, 0, moduleFile + ' leaves unmanaged / disabled cards alone');
                } else {
                    assert.equal(result.count, 1, 'rescanning does not duplicate the title');
                    assert.equal(result.text, expected, moduleFile + ' extracts one complete name, not accessible labels');
                    assert.equal(result.clamp, '2');
                    assert.equal(result.padding, '0px', 'clamped text must have no padding');
                    assert.ok(result.height <= result.lineHeight * 2 + 0.5, 'text frame holds at most two lines');
                }
                assert.deepEqual(errors, []);
                checks++;
                await page.close();
            }
        }
        console.log('PASS ' + checks + ' real Moodle fragment cases (source + built AMD).');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
