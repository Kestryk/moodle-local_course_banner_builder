const {test, expect, chromium} = require('@playwright/test');
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

const requireEnvironment = () => {
    const required = [
        'EASYEDU_MOODLE_URL', 'EASYEDU_MOODLE_USERNAME', 'EASYEDU_MOODLE_PASSWORD',
        'EASYEDU_CCB_SELECTED_LAYER_STACKING_SOURCE_CATEGORY_ID',
        'EASYEDU_CCB_SELECTED_LAYER_STACKING_PROFILE',
        'EASYEDU_CCB_SELECTED_LAYER_STACKING_ARTIFACT_ROOT',
    ];
    const missing = required.filter(name => !process.env[name]);
    ensure(!missing.length, 'Missing selected-layer stacking environment values: ' + missing.join(', '));

    const artifactRoot = path.resolve(process.env.EASYEDU_CCB_SELECTED_LAYER_STACKING_ARTIFACT_ROOT);
    const profile = path.resolve(process.env.EASYEDU_CCB_SELECTED_LAYER_STACKING_PROFILE);
    ensure(path.isAbsolute(artifactRoot) && path.isAbsolute(profile), 'Artifact and profile paths must be absolute.');
    ensure(profile.toLowerCase().startsWith((artifactRoot + path.sep).toLowerCase()),
        'The isolated Chromium profile must stay below the artifact root.');
    ensure(!artifactRoot.toLowerCase().includes(path.sep + 'local' + path.sep + 'course_banner_builder'),
        'Artifacts must stay outside the CCB repository.');
    ensure(/^\d+$/.test(String(process.env.EASYEDU_CCB_SELECTED_LAYER_STACKING_SOURCE_CATEGORY_ID)),
        'The selected-layer fixture category must be numeric.');

    return {
        baseUrl: String(process.env.EASYEDU_MOODLE_URL).replace(/\/$/, ''),
        username: process.env.EASYEDU_MOODLE_USERNAME,
        password: process.env.EASYEDU_MOODLE_PASSWORD,
        categoryId: String(process.env.EASYEDU_CCB_SELECTED_LAYER_STACKING_SOURCE_CATEGORY_ID),
        profile,
        artifactRoot,
    };
};

const login = async(page, env) => {
    await page.goto(env.baseUrl + '/login/index.php', {waitUntil: 'domcontentloaded'});
    await page.locator('#username').fill(env.username);
    await page.locator('#password').fill(env.password);
    await page.locator('#loginbtn').click({noWaitAfter: true});
    await expect(page).not.toHaveURL(/\/login\//, {timeout: 30000});
};

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

const stackEvidence = async page => page.evaluate(() => {
    const root = document.querySelector('[data-source-visual-editor="1"]');
    const frame = root?.querySelector('[data-source-preview-frame="1"]');
    const layers = Array.from(root?.querySelectorAll(
        '[data-source-preview-layer="1"][data-source-preview-editable="1"]'
    ) || []);
    const selected = layers.find(layer => layer.classList.contains(
        'local-course-banner-builder-source-preview-layer--selected'
    ));
    const outline = frame?.querySelector('[data-preview-selection-outline="1"]');
    const rect = element => {
        if (!element) {
            return null;
        }
        const box = element.getBoundingClientRect();
        return {left: box.left, top: box.top, width: box.width, height: box.height};
    };
    const describe = layer => ({
        id: layer.getAttribute('data-source-preview-layer-id') || '',
        persistedZIndex: Number(layer.getAttribute('data-preview-zindex') || 0),
        computedZIndex: Number(getComputedStyle(layer).zIndex || 0),
        selected: layer === selected,
        rect: rect(layer),
    });
    return {
        root: !!root,
        frame: !!frame,
        layers: layers.map(describe),
        selected: selected ? describe(selected) : null,
        outline: outline ? {
            hidden: !!outline.hidden,
            computedZIndex: Number(getComputedStyle(outline).zIndex || 0),
            rect: rect(outline),
        } : null,
    };
});

test('CCB_SELECTED_LAYER_STACKING_0034 keeps selected-layer promotion transient', async() => {
    test.setTimeout(120000);
    const env = requireEnvironment();
    const runRoot = path.join(env.artifactRoot, 'selected-layer-stacking');
    const context = await chromium.launchPersistentContext(env.profile, {
        headless: true,
        viewport: {width: 1600, height: 900},
        deviceScaleFactor: 1,
        ignoreHTTPSErrors: true,
        args: ['--disable-gpu'],
    });
    const page = context.pages()[0] || await context.newPage();
    const evidence = {consoleErrors: [], failedRequests: []};
    page.setDefaultTimeout(30000);
    page.on('console', message => {
        if (message.type() === 'error') {
            evidence.consoleErrors.push(message.text());
        }
    });
    page.on('requestfailed', request => {
        const failure = request.failure()?.errorText || 'unknown';
        if (!/ERR_ABORTED/i.test(failure)) {
            evidence.failedRequests.push({method: request.method(), failure});
        }
    });

    try {
        await login(page, env);
        const url = new URL('/local/course_banner_builder/admin_manage.php', env.baseUrl);
        url.searchParams.set('sourcekey', 'category:' + env.categoryId);
        await page.goto(url.toString(), {waitUntil: 'domcontentloaded'});

        const root = page.locator('[data-source-visual-editor="1"]').first();
        const layers = root.locator('[data-source-preview-layer="1"][data-source-preview-editable="1"]');
        await expect(root).toBeVisible();
        const layerCount = await layers.count();
        expect(layerCount).toBeGreaterThanOrEqual(2);

        const lowerLayer = layers.nth(0);
        const higherLayer = layers.nth(layerCount - 1);
        const originalPersistedZIndex = await lowerLayer.getAttribute('data-preview-zindex');
        await lowerLayer.focus();
        await lowerLayer.press('Enter');
        await expect(lowerLayer).toHaveClass(/local-course-banner-builder-source-preview-layer--selected/);
        evidence.selected = await stackEvidence(page);
        expect(evidence.selected.selected.persistedZIndex).toBe(Number(originalPersistedZIndex));
        expect(evidence.selected.selected.computedZIndex).toBe(8950);
        expect(evidence.selected.outline.hidden).toBe(false);
        expect(evidence.selected.outline.computedZIndex).toBeGreaterThan(evidence.selected.selected.computedZIndex);
        evidence.selected.layers.filter(layer => !layer.selected).forEach(layer => {
            expect(evidence.selected.selected.computedZIndex).toBeGreaterThan(layer.computedZIndex);
        });
        await captureCdp(page, context, path.join(runRoot, 'selected-layer-promoted.png'));

        const higherPersistedZIndex = await higherLayer.getAttribute('data-preview-zindex');
        await higherLayer.focus();
        await higherLayer.press('Enter');
        await expect(lowerLayer).not.toHaveClass(/local-course-banner-builder-source-preview-layer--selected/);
        evidence.deselected = await stackEvidence(page);
        const deselectedLayer = evidence.deselected.layers.find(layer => layer.id ===
            (evidence.selected.selected.id));
        expect(deselectedLayer.persistedZIndex).toBe(Number(originalPersistedZIndex));
        expect(deselectedLayer.computedZIndex).toBe(Number(originalPersistedZIndex));
        expect(evidence.deselected.selected.persistedZIndex).toBe(Number(higherPersistedZIndex));
        expect(evidence.deselected.selected.computedZIndex).toBe(8950);

        const higherBox = await higherLayer.boundingBox();
        expect(higherBox).not.toBeNull();
        await page.mouse.move(higherBox.x + (higherBox.width / 2), higherBox.y + (higherBox.height / 2));
        await page.mouse.down();
        await page.mouse.move(higherBox.x + (higherBox.width / 2) + 12, higherBox.y + (higherBox.height / 2) + 8);
        await page.mouse.up();
        evidence.afterDrag = await stackEvidence(page);
        expect(evidence.afterDrag.selected.id).toBe(evidence.deselected.selected.id);
        expect(evidence.afterDrag.selected.persistedZIndex).toBe(Number(higherPersistedZIndex));
        expect(evidence.afterDrag.selected.computedZIndex).toBe(8950);

        const save = root.locator('[form="local-course-banner-builder-source-preview-save-form"]').first();
        await expect(save).toBeEnabled();
        const saveResponse = page.waitForResponse(response => response.request().method() === 'POST' &&
            response.url().includes('/local/course_banner_builder/admin_manage.php') && response.status() < 400,
        {timeout: 60000});
        await save.click({noWaitAfter: true});
        await saveResponse;
        await page.goto(url.toString(), {waitUntil: 'domcontentloaded'});
        evidence.afterReload = await stackEvidence(page);
        expect(evidence.afterReload.selected).toBeNull();
        expect(evidence.consoleErrors).toEqual([]);
        expect(evidence.failedRequests).toEqual([]);
        writeJson(path.join(runRoot, 'selected-layer-stacking-evidence.json'), evidence);
    } catch (error) {
        evidence.error = String(error && error.stack || error);
        writeJson(path.join(runRoot, 'selected-layer-stacking-failure.json'), evidence);
        throw error;
    } finally {
        await context.close();
    }
});
