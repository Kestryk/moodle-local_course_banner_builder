const {test, expect, chromium} = require('@playwright/test');
const fs = require('fs');
const path = require('path');

const ensure = (condition, message) => {
    if (!condition) {
        throw new Error(message);
    }
};

const requireEnvironment = () => {
    const required = [
        'EASYEDU_MOODLE_URL',
        'EASYEDU_MOODLE_USERNAME',
        'EASYEDU_MOODLE_PASSWORD',
        'EASYEDU_CCB_WAVE_SOURCE_CATEGORY_ID',
        'EASYEDU_CCB_WAVE_ROOT_SOURCE_KEY',
        'EASYEDU_CCB_WAVE_CHILD_SOURCE_KEY',
        'EASYEDU_CCB_WAVE_SOURCE_KEY',
        'EASYEDU_CCB_WAVE_ARTIFACT_ROOT',
        'EASYEDU_CCB_WAVE_IMAGE_FIXTURE',
    ];
    const missing = required.filter(name => !process.env[name]);
    ensure(!missing.length, 'Missing cumulative CCB wave environment: ' + missing.join(', '));
    const artifactRoot = path.resolve(process.env.EASYEDU_CCB_WAVE_ARTIFACT_ROOT);
    ensure(path.isAbsolute(artifactRoot), 'Cumulative captures require an absolute external artifact root.');
    ensure(/^\d+$/.test(process.env.EASYEDU_CCB_WAVE_SOURCE_CATEGORY_ID),
        'The cumulative CCB source category must be numeric.');
    ensure(fs.existsSync(process.env.EASYEDU_CCB_WAVE_IMAGE_FIXTURE),
        'The cumulative CCB image fixture is unavailable.');
    for (const name of ['EASYEDU_CCB_WAVE_ROOT_SOURCE_KEY', 'EASYEDU_CCB_WAVE_CHILD_SOURCE_KEY',
        'EASYEDU_CCB_WAVE_SOURCE_KEY']) {
        ensure(/^category:\d+$/.test(process.env[name]), 'The cumulative CCB source key must be category:numeric.');
    }
    return {
        artifactRoot,
        baseUrl: process.env.EASYEDU_MOODLE_URL.replace(/\/$/, ''),
        categoryId: process.env.EASYEDU_CCB_WAVE_SOURCE_CATEGORY_ID,
        rootKey: process.env.EASYEDU_CCB_WAVE_ROOT_SOURCE_KEY,
        childKey: process.env.EASYEDU_CCB_WAVE_CHILD_SOURCE_KEY,
        sourceKey: process.env.EASYEDU_CCB_WAVE_SOURCE_KEY,
        imageFixture: process.env.EASYEDU_CCB_WAVE_IMAGE_FIXTURE,
        password: process.env.EASYEDU_MOODLE_PASSWORD,
        username: process.env.EASYEDU_MOODLE_USERNAME,
    };
};

const sourceKeySelector = key => '[data-source-key="' + key + '"]';

const ownedSourceChainRows = (page, env) => page.locator('[data-source-chain-row="1"]').filter({
    has: page.locator([env.rootKey, env.childKey, env.sourceKey].map(sourceKeySelector).join(', ')),
});

const ownedSourceChainRow = (page, key) => page.locator('[data-source-chain-row="1"]').filter({
    has: page.locator(sourceKeySelector(key)),
});

const ownedSourceEditor = (page, env) => page.locator(
    '[data-source-visual-editor="1"][data-sourcekey="' + env.sourceKey + '"]'
);

const captureHuman = async(page, env, name, locator = null) => {
    const file = path.join(env.artifactRoot, name + '.png');
    fs.mkdirSync(env.artifactRoot, {recursive: true});
    if (locator) {
        await locator.screenshot({path: file});
    } else {
        await page.screenshot({path: file, fullPage: true});
    }
    return path.basename(file);
};

const waitForFrames = page => page.evaluate(() => new Promise(resolve => {
    requestAnimationFrame(() => requestAnimationFrame(() => requestAnimationFrame(resolve)));
}));

const sourceUrl = env => {
    const url = new URL('/local/course_banner_builder/admin_manage.php', env.baseUrl);
    url.searchParams.set('sourcekey', 'category:' + env.categoryId);
    return url.toString();
};

const loginAndOpen = async(page, env, source = true) => {
    await page.goto(env.baseUrl + '/login/index.php', {waitUntil: 'domcontentloaded', timeout: 60000});
    await page.locator('#username').fill(env.username);
    await page.locator('#password').fill(env.password);
    await page.locator('#loginbtn').click({noWaitAfter: true});
    await expect(page).not.toHaveURL(/\/login\//, {timeout: 60000});
    await page.goto(source ? sourceUrl(env) :
        env.baseUrl + '/local/course_banner_builder/admin_manage.php', {waitUntil: 'domcontentloaded', timeout: 60000});
};

const closeModal = async(modal) => {
    await modal.locator('[data-dismiss="modal"], [data-bs-dismiss="modal"], .close').first().click();
    await expect(modal).toBeHidden({timeout: 30000});
};

const assertNoHorizontalOverflow = async(page, label) => {
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth + 1);
    expect(overflow, label).toBe(false);
};

const assertTitleColourDialog = async(page, env, context, captureName, narrow) => {
    await loginAndOpen(page, env, context !== 'site');
    if (narrow) {
        await page.setViewportSize({width: 390, height: 844});
        await page.evaluate(() => {
            document.documentElement.style.zoom = '2';
        });
    } else {
        await page.setViewportSize({width: 1440, height: 900});
    }
    const modal = page.locator('#local-course-banner-builder-title-settings-' + context + '-modal');
    await expect(page.locator('[data-bs-target="#local-course-banner-builder-title-settings-' + context + '-modal"], ' +
        '[data-target="#local-course-banner-builder-title-settings-' + context + '-modal"]'),
        context + ' title trigger').toHaveCount(1);
    const trigger = page.locator('[data-bs-target="#local-course-banner-builder-title-settings-' + context + '-modal"], ' +
        '[data-target="#local-course-banner-builder-title-settings-' + context + '-modal"]').first();
    if (await modal.isHidden()) {
        await trigger.click();
    }
    await expect(modal, context + ' title editor').toBeVisible({timeout: 30000});
    const form = modal.locator('form[data-banner-title-editor][data-title-current-context="' + context + '"]');
    await expect(form, context + ' title form').toHaveCount(1);
    await expect(form.locator('input[type="color"]'), context + ' title editor has no native picker').toHaveCount(0);
    const swatch = form.locator('[data-action="local-course-banner-builder-open-title-colour-dialog"]').first();
    const persisted = form.locator('[data-title-color-text-for]').first();
    const before = await persisted.inputValue();
    await swatch.click();
    const dialog = page.locator('[data-title-colour-dialog="1"]');
    await expect(dialog, context + ' title colour dialog').toBeVisible();
    await captureHuman(page, env, captureName, dialog);
    const input = dialog.locator('input[type="text"]').first();
    await dialog.locator('[data-title-colour-value="#0F6CBF"]').click();
    await dialog.getByRole('button', {name: /apply/i}).click();
    await expect(dialog).toBeHidden();
    await expect(persisted).not.toHaveValue(before);
    await expect(swatch).toBeFocused();

    const applied = await persisted.inputValue();
    await swatch.click();
    await expect(dialog).toBeVisible();
    await input.fill('#DC2626');
    await dialog.getByRole('button', {name: /cancel/i}).click();
    await expect(persisted, context + ' Cancel remains transactional').toHaveValue(applied);
    await expect(swatch).toBeFocused();

    await swatch.click();
    await expect(dialog).toBeVisible();
    await input.fill('#16A34A');
    await page.keyboard.press('Escape');
    await expect(dialog).toBeHidden();
    await expect(persisted, context + ' Escape remains transactional').toHaveValue(applied);
    await expect(swatch).toBeFocused();
    await assertNoHorizontalOverflow(page, context + ' title dialog must not overflow at this viewport');
    await closeModal(modal);
    await page.evaluate(() => {
        document.documentElement.style.zoom = '';
    });
};

const assertImageCropFlow = async(page, env, viewportName, edit) => {
    const editor = ownedSourceEditor(page, env);
    await expect(editor, 'IMG-08 owned source editor').toHaveCount(1);
    const trigger = edit ?
        editor.locator('[data-edit-layer-url][data-edit-layer-modal="local-course-banner-builder-edit-image-layer-modal"]').first() :
        editor.locator('[data-target="#local-course-banner-builder-add-layer-modal"], ' +
            '[data-bs-target="#local-course-banner-builder-add-layer-modal"]').first();
    await expect(trigger, 'IMG-08 image modal trigger').toBeVisible({timeout: 30000});
    await trigger.click();
    const modal = page.locator(edit ? '#local-course-banner-builder-edit-image-layer-modal:visible' :
        '#local-course-banner-builder-add-layer-modal:visible').first();
    await expect(modal, 'IMG-08 image modal').toBeVisible({timeout: 30000});
    const form = modal.locator('form.mform').first();
    if (!edit) {
        const add = form.locator('#fitem_id_bannerimage_filemanager .fp-btn-add a, ' +
            '#fitem_id_bannerimage_filemanager input.fp-btn-choose').first();
        await add.click();
        const picker = page.locator('.file-picker:visible').last();
        await expect(picker).toBeVisible({timeout: 30000});
        const upload = picker.locator('input[name="repo_upload_file"]').first();
        await upload.setInputFiles(env.imageFixture);
        await picker.locator('.fp-upload-btn').first().click();
        await expect(picker).toBeHidden({timeout: 45000});
    }
    const image = form.locator('[data-preview-current-image="1"] [data-preview-image-tag="1"]').first();
    await expect.poll(() => image.evaluate(node => node.complete ? node.naturalWidth : 0), {timeout: 45000})
        .toBeGreaterThan(0);
    await captureHuman(page, env, '03-img08-' + viewportName + '-' + (edit ? 'edit' : 'add') + '-before-geometry', modal);
    const before = await form.evaluate(current => ({
        height: current.querySelector('[data-preview-current-image="1"]')?.getAttribute('data-preview-custom-height'),
        left: current.querySelector('[data-preview-current-image="1"]')?.getAttribute('data-preview-offset-left'),
        top: current.querySelector('[data-preview-current-image="1"]')?.getAttribute('data-preview-offset-top'),
        width: current.querySelector('[data-preview-current-image="1"]')?.getAttribute('data-preview-custom-width'),
    }));
    await form.locator('[data-action="local-course-banner-builder-toggle-modal-preview-crop"]').first().click();
    await expect(form.locator('[data-preview-crop-box="1"]').first()).toBeVisible();
    const handle = await form.locator('[data-preview-crop-handle="se"]').first().boundingBox();
    ensure(handle, 'IMG-08 crop handle has no layout box.');
    await page.mouse.move(handle.x + handle.width / 2, handle.y + handle.height / 2);
    await page.mouse.down();
    await page.mouse.move(handle.x - 48, handle.y - 32, {steps: 8});
    await page.mouse.up();
    await form.locator('[data-action="local-course-banner-builder-apply-preview-crop"]').first().click();
    await waitForFrames(page);
    const after = await form.evaluate(current => ({
        height: current.querySelector('[data-preview-current-image="1"]')?.getAttribute('data-preview-custom-height'),
        left: current.querySelector('[data-preview-current-image="1"]')?.getAttribute('data-preview-offset-left'),
        top: current.querySelector('[data-preview-current-image="1"]')?.getAttribute('data-preview-offset-top'),
        width: current.querySelector('[data-preview-current-image="1"]')?.getAttribute('data-preview-custom-width'),
    }));
    expect(after, 'IMG-08 Apply keeps placement').toEqual(before);
    await closeModal(modal);
};

const assertSourceTreeAndPreview = async(page, env, evidence) => {
    await page.setViewportSize({width: 1440, height: 900});
    await loginAndOpen(page, env);
    const rows = ownedSourceChainRows(page, env);
    for (const key of [env.rootKey, env.childKey, env.sourceKey]) {
        await expect(ownedSourceChainRow(page, key), '0050 fixture source row ' + key).toHaveCount(1);
    }
    const toggles = rows.locator('[data-action="local-course-banner-builder-toggle-source-chain"]');
    await expect(rows, '0050 requires a three-level source fixture').toHaveCount(3);
    await expect(toggles, '0050 requires two expandable ancestors').toHaveCount(2);
    await captureHuman(page, env, '08-0050-source-tree-before-sensitive');
    evidence.captures.push('08-0050-source-tree-before-sensitive.png');
    await expect(toggles.first()).toHaveAttribute('aria-controls', /local-course-banner-builder-source-chain-row-/);
    await toggles.first().click();
    await expect(toggles.first()).toHaveAttribute('aria-expanded', 'false');
    await expect(rows.nth(1)).toBeHidden();
    await expect(rows.nth(2)).toBeHidden();
    await toggles.first().click();
    await expect(rows.nth(1)).toBeVisible();
    await toggles.nth(1).click();
    await expect(rows.nth(2)).toBeHidden();
    await toggles.nth(1).click();
    const collapseAll = page.locator('[data-action="local-course-banner-builder-toggle-all-source-chains"]');
    await collapseAll.click();
    await expect(rows.nth(1)).toBeHidden();
    await expect(rows.nth(2)).toBeHidden();
    await collapseAll.click();
    await expect(rows.nth(2)).toBeVisible();
    await page.emulateMedia({reducedMotion: 'reduce'});
    await toggles.first().click();
    await expect(rows.nth(1)).toBeHidden();
    await toggles.first().click();
    await expect(rows.nth(2)).toBeVisible();
    await page.emulateMedia({reducedMotion: 'no-preference'});

    const previewTrigger = ownedSourceChainRow(page, env.sourceKey).locator(
        '[data-action="local-course-banner-builder-show-source-chain-preview"]'
    ).first();
    await previewTrigger.click();
    const previewModal = page.locator('#local-course-banner-builder-source-chain-preview-modal');
    const body = previewModal.locator('[data-source-chain-preview-modal-body="1"]');
    await expect(previewModal).toBeVisible({timeout: 30000});
    await expect(previewModal, '0050 Kit loading state').toHaveClass(/is-loading/);
    await expect(body, '0050 loading body busy state').toHaveAttribute('aria-busy', 'true');
    await captureHuman(page, env, '09-0050-preview-loading-before-sensitive', previewModal);
    evidence.captures.push('09-0050-preview-loading-before-sensitive.png');
    await expect(body).toHaveAttribute('aria-busy', 'false', {timeout: 45000});
    const previewRoot = body.locator('[data-source-visual-editor="1"]');
    await expect(previewRoot).toBeVisible();
    const footer = previewModal.locator('[data-source-chain-preview-modal-footer="1"]');
    await expect(footer.locator('a.local-course-banner-builder-source-preview-button')).toHaveCount(1);
    await expect(body.locator('.local-course-banner-builder-source-chain-preview-actions')).toHaveCount(0);
    await captureHuman(page, env, '10-0050-preview-ready-before-sensitive', previewModal);
    evidence.captures.push('10-0050-preview-ready-before-sensitive.png');
    const canvas = previewRoot.locator('[data-source-preview-canvas="1"], .local-course-banner-builder-source-preview-canvas').first();
    const footerBefore = await footer.boundingBox();
    await previewRoot.locator('[data-source-preview-mode-value="mobile"]').click();
    await expect(previewRoot).toHaveAttribute('data-source-preview-mode', 'mobile');
    await previewRoot.locator('[data-source-preview-mode-value="desktop"]').click();
    await expect(previewRoot).toHaveAttribute('data-source-preview-mode', 'desktop');
    expect(await footer.boundingBox(), '0050 Desktop/Mobile only changes the preview canvas').toEqual(footerBefore);
    await expect(canvas).toBeVisible();
    await closeModal(previewModal);
    await expect(previewTrigger).toBeFocused();

    const previewUrl = new URL(await previewTrigger.getAttribute('data-preview-url'), env.baseUrl).toString();
    await page.route(previewUrl, route => route.fulfill({status: 500, body: 'safe preview error'}));
    await previewTrigger.click();
    await expect(previewModal).toBeVisible();
    await captureHuman(page, env, '11-0050-preview-error-before-sensitive', previewModal);
    evidence.captures.push('11-0050-preview-error-before-sensitive.png');
    await expect(body.locator('.text-danger')).toBeVisible({timeout: 30000});
    await expect(body).toBeFocused();
    await page.unroute(previewUrl);
    await closeModal(previewModal);
    await expect(previewTrigger).toBeFocused();
};

test('EED-CCB-2026-0042-0050 cumulative visual and interaction wave', async() => {
    test.setTimeout(900000);
    const env = requireEnvironment();
    const evidence = {captures: []};
    const browser = await chromium.launch({headless: true});
    const context = await browser.newContext({ignoreHTTPSErrors: true, viewport: {width: 1440, height: 900}});
    const page = await context.newPage();
    page.setDefaultTimeout(30000);
    try {
        await loginAndOpen(page, env);
        await captureHuman(page, env, '01-0042-parent-list-before-sensitive');
        evidence.captures.push('01-0042-parent-list-before-sensitive.png');
        const parentPencils = ownedSourceChainRows(page, env).locator(
            '[data-action="local-course-banner-builder-change-source-parent"]' + sourceKeySelector(env.childKey) + ', ' +
            '[data-action="local-course-banner-builder-change-source-parent"]' + sourceKeySelector(env.sourceKey)
        );
        await expect(parentPencils).toHaveCount(2);
        const parentModal = page.locator('#local-course-banner-builder-change-source-parent-modal');
        await parentPencils.first().click();
        await expect(parentModal).toBeVisible();
        await captureHuman(page, env, '02-0042-parent-modal-before-sensitive', parentModal);
        evidence.captures.push('02-0042-parent-modal-before-sensitive.png');
        const search = parentModal.locator('[data-action="local-course-banner-builder-filter-sources"]');
        await expect(search).toBeVisible();
        const parentToggle = parentModal.locator('[data-source-dropdown="parent-change"] .local-course-banner-builder-source-dropdown-toggle');
        await parentToggle.click();
        const parentList = parentModal.locator('[data-parent-source-change-options="1"]');
        await expect(parentList).toBeVisible();
        const [toggleBox, listBox] = await Promise.all([parentToggle.boundingBox(), parentList.boundingBox()]);
        ensure(toggleBox && listBox && listBox.y >= toggleBox.y + toggleBox.height - 1,
            '0042 parent list must open below its trigger.');
        await search.fill('zzz-no-parent-match');
        await expect(parentList).toBeVisible();
        await expect(parentModal.locator('[data-parent-source-change-submit="1"] .fa-save')).toHaveCount(1);
        await parentModal.locator('[data-action="local-course-banner-builder-cancel-source-parent-change"]').first().click();
        await expect(parentModal).toBeHidden();
        await expect(page.locator('.local-course-banner-builder-banner-preview-frame, ' +
            '.local-course-banner-builder-border-preview-frame').first()).toBeVisible();

        await assertImageCropFlow(page, env, 'desktop', false);
        await page.setViewportSize({width: 390, height: 844});
        await loginAndOpen(page, env);
        await assertImageCropFlow(page, env, 'narrow', false);
        await page.setViewportSize({width: 1440, height: 900});
        await loginAndOpen(page, env);
        await assertImageCropFlow(page, env, 'desktop', true);
        await page.setViewportSize({width: 390, height: 844});
        await loginAndOpen(page, env);
        await assertImageCropFlow(page, env, 'narrow', true);

        await page.setViewportSize({width: 1440, height: 900});
        await loginAndOpen(page, env);
        const root = ownedSourceEditor(page, env);
        await expect(root, '0044 owned source editor').toHaveCount(1);
        await captureHuman(page, env, '04-0044-motion-drag-before-sensitive', root);
        evidence.captures.push('04-0044-motion-drag-before-sensitive.png');
        const filmstrip = root.locator('[data-source-preview-filmstrip="1"], .local-course-banner-builder-source-filmstrip').first();
        const filmstripBefore = await filmstrip.boundingBox();
        await root.locator('[data-source-preview-mode-value="mobile"]').click();
        await root.locator('[data-source-preview-mode-value="desktop"]').click();
        expect(await filmstrip.boundingBox(), '0044 leaves filmstrip stationary').toEqual(filmstripBefore);
        await page.emulateMedia({reducedMotion: 'reduce'});
        await root.locator('[data-source-preview-mode-value="mobile"]').click();
        await expect(root).toHaveAttribute('data-source-preview-mode', 'mobile');
        await page.emulateMedia({reducedMotion: 'no-preference'});
        const disclosure = root.locator('.local-course-banner-builder-layer-details-accordion').first();
        await expect(disclosure).toBeVisible();
        await disclosure.locator('summary').click();
        await expect(disclosure).toHaveAttribute('open', '');
        await disclosure.locator('summary').click();
        await expect(disclosure).not.toHaveAttribute('open', '');
        const layerTable = page.locator('tbody[data-layer-sortable-sourcekey="' + env.sourceKey + '"]');
        await expect(layerTable, '0044 owned source layer table').toHaveCount(1);
        const movable = layerTable.locator('.local-course-banner-builder-layer-row[draggable="true"]');
        const locked = layerTable.locator('.local-course-banner-builder-layer-row--order-locked');
        await expect(movable).toHaveCount(2);
        await expect(locked).toHaveCount(1);
        await movable.nth(0).dragTo(movable.nth(1));
        await expect(root.locator('.local-course-banner-builder-layer-drag-preview')).toHaveCount(0);
        await expect(root.locator('.local-course-banner-builder-layer-row-dragging')).toHaveCount(0);
        await expect(root.locator('.local-course-banner-builder-layer-row-drop-before, ' +
            '.local-course-banner-builder-layer-row-drop-after')).toHaveCount(0);

        await assertTitleColourDialog(page, env, 'course', '05-0046-course-title-colour-dialog', false);
        await assertTitleColourDialog(page, env, 'site', '06-0046-site-title-colour-dialog', false);
        await assertTitleColourDialog(page, env, 'activity', '07-0046-activity-title-colour-dialog', true);
        await assertSourceTreeAndPreview(page, env, evidence);
    } catch (error) {
        evidence.error = String(error && error.stack || error);
        throw error;
    } finally {
        fs.mkdirSync(env.artifactRoot, {recursive: true});
        fs.writeFileSync(path.join(env.artifactRoot, 'ccb-wave-0042-0050-evidence.json'), JSON.stringify(evidence, null, 2));
        await context.close().catch(() => {});
        await browser.close().catch(() => {});
    }
});
