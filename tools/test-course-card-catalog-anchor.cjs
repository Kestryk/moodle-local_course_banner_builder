/* eslint-env node */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
const {chromium} = require(process.env.EASYEDU_PLAYWRIGHT_MODULE || 'playwright');

(async() => {
    const browser = await chromium.launch({headless: true, executablePath: process.env.EASYEDU_CHROME_PATH});
    let checks = 0;
    try {
        for (const source of ['amd/src/coursecards.js', 'amd/build/coursecards.min.js']) {
            for (const width of [1600, 768, 390]) {
                for (const name of ['French A1 [English]', 'Long course name '.repeat(16)]) {
                    const page = await browser.newPage({viewport: {width, height: 800}});
                    await page.route('**/*', route => route.abort());
                    // Native catalogue shape: its image parent can be taller than
                    // the image. This is not the dashboard's background-image div.
                    await page.setContent('<div class="coursebox" data-courseid="123">' +
                        '<div class="info"><h3 class="coursename"><a></a></h3></div>' +
                        '<div class="content"><div class="courseimage">' +
                        '<img src="https://example.invalid/course/overviewfiles/course_banner_builder_auto_test.png">' +
                        '</div><div class="summary">Course details</div></div></div>');
                    await page.addStyleTag({content: fs.readFileSync(path.join(root, 'styles.css'), 'utf8') +
                        '.courseimage {width:100px;min-height:120px}' +
                        '.courseimage img {width:100px;height:45px;max-width:100px;max-height:45px}'});
                    await page.evaluate(name => {
                        document.querySelector('.coursename a').textContent = name;
                        window.define = (...args) => {
                            window.cardModule = args[args.length - 1]({wwwroot: 'https://example.invalid'});
                        };
                    }, name);
                    await page.addScriptTag({content: fs.readFileSync(path.join(root, source), 'utf8')});
                    const result = await page.evaluate(() => {
                        const options = {enabled: true, cardTitle: {enabled: true, align: 'center'}};
                        window.cardModule.init(null, options);
                        window.cardModule.init(null, options);
                        const image = document.querySelector('img').getBoundingClientRect();
                        const title = document.querySelector('.local-course-banner-builder-course-card-title');
                        const bounds = title.getBoundingClientRect();
                        const text = title.firstElementChild;
                        return {text: title.textContent, count: document.querySelectorAll('.' + title.classList[0]).length,
                            delta: Math.max(Math.abs(bounds.x - image.x), Math.abs(bounds.y - image.y),
                                Math.abs(bounds.width - image.width), Math.abs(bounds.height - image.height)),
                            clamp: getComputedStyle(text).webkitLineClamp,
                            textHeight: text.getBoundingClientRect().height,
                            lineHeight: parseFloat(getComputedStyle(text).lineHeight)};
                    });
                    assert.equal(result.count, 1);
                    assert.equal(result.text, name.trim());
                    assert.ok(result.delta <= 0.5, 'title must use the image box, not its 120px parent');
                    assert.equal(result.clamp, '2');
                    assert.ok(result.textHeight <= result.lineHeight * 2 + 0.5);
                    checks++;
                    await page.close();
                }
            }
        }
    } finally {
        await browser.close();
    }
    console.log(JSON.stringify({checks, pass: true}));
})().catch(error => { console.error(error); process.exitCode = 1; });
