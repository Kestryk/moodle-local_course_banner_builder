'use strict';
// Native read-only regression probe. Supply an existing storage state or use guest access.
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const {chromium} = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const [base, pagePath, output] = process.argv.slice(2);
assert(base && pagePath && output && path.isAbsolute(output), 'Pass base URL, banner page path and absolute evidence directory');
fs.mkdirSync(output, {recursive: true});
(async () => {
    const browser = await chromium.launch({headless: true});
    const report = {base, pagePath, cases: [], errors: []};
    try {
        const context = await browser.newContext({viewport: {width: 1600, height: 1000},
            storageState: process.env.CCB_STORAGE_STATE || undefined});
        await context.addInitScript(() => {
            window.ccbSamples = [];
            let previous = '';
            const sample = () => {
                for (const e of document.querySelectorAll('.local-course-banner-builder-native-shell')) {
                    const r = e.getBoundingClientRect();
                    const m = {id: e.id, mounted: e.classList.contains('local-course-banner-builder-native-shell--mounted'),
                        visibility: getComputedStyle(e).visibility, x: r.x, y: r.y, w: r.width, h: r.height};
                    const key = JSON.stringify(m);
                    if (key !== previous && window.ccbSamples.length < 100) {
                        window.ccbSamples.push({...m, t: performance.now()});
                        previous = key;
                    }
                }
            };
            new MutationObserver(sample).observe(document, {subtree: true, childList: true,
                attributes: true, attributeFilter: ['class', 'style']});
            const tick = () => { sample(); if (performance.now() < 15000) { requestAnimationFrame(tick); } };
            requestAnimationFrame(tick);
        });
        const page = await context.newPage();
        page.on('pageerror', e => report.errors.push({message: e.message, stack: e.stack}));
        if (!process.env.CCB_STORAGE_STATE) {
            await page.goto(base + '/login/index.php', {waitUntil: 'domcontentloaded'});
            assert(await page.locator('#loginguestbtn').count(), 'Provide an authorised storage state; no guest login is available');
            await page.locator('#loginguestbtn').click();
        }
        for (const width of [1600, 390]) {
            await page.setViewportSize({width, height: 1000});
            await page.goto(base + pagePath, {waitUntil: 'domcontentloaded', timeout: 120000});
            await page.waitForTimeout(2000);
            const samples = await page.evaluate(() => window.ccbSamples);
            report.cases.push({width, actualUrl: page.url(), samples});
            assert(samples.length, 'No banner; an unrelated page is not a passing test');
            assert(!samples.some(s => !s.mounted && s.visibility === 'visible'), 'Banner painted before mounting');
            const last = samples[samples.length - 1];
            assert(last.mounted && last.visibility === 'visible' && last.w > 0, 'Banner not visible after mounting');
            assert(last.x >= -0.5 && last.x + last.w <= width + 0.5, 'Mounted banner leaves the viewport');
            await page.screenshot({path: path.join(output, width + '.png')});
        }
        const noJs = await browser.newContext({javaScriptEnabled: false, storageState: await context.storageState()});
        const staticPage = await noJs.newPage();
        await staticPage.goto(base + pagePath, {waitUntil: 'domcontentloaded'});
        report.noScript = await staticPage.locator('.local-course-banner-builder-native-shell').evaluateAll(es =>
            es.map(e => ({id: e.id, visibility: getComputedStyle(e).visibility})));
        assert(report.noScript.length && report.noScript.every(s => s.visibility === 'visible'), 'No-script banner lost');
        assert.equal(report.errors.length, 0, 'JavaScript exception');
        report.passed = true;
    } finally {
        fs.writeFileSync(path.join(output, 'native-first-paint.json'), JSON.stringify(report, null, 2));
        await browser.close();
    }
    console.log(JSON.stringify({passed: true, cases: report.cases.length, errors: report.errors.length}));
})().catch(e => { console.error(e); process.exitCode = 1; });
