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
    return {
        artifactRoot,
        baseUrl: process.env.EASYEDU_MOODLE_URL.replace(/\/$/, ''),
        categoryId: process.env.EASYEDU_CCB_WAVE_SOURCE_CATEGORY_ID,
        imageFixture: process.env.EASYEDU_CCB_WAVE_IMAGE_FIXTURE,
        password: process.env.EASYEDU_MOODLE_PASSWORD,
        username: process.env.EASYEDU_MOODLE_USERNAME,
    };
};

const waitForFrames = page => page.evaluate(() => new Promise(resolve => {
    requestAnimationFrame(() => requestAnimationFrame(() => requestAnimationFrame(resolve)));
}));

const captureHuman = async(page, env, name, locator = null) => {
    const file = path.join(env.artifactRoot, name + '.png');
    fs.mkdirSync(env.artifactRoot, {recursive: true});
    if (locator) {
        await locator.screenshot({path: file});
    } else {
        await page.screenshot({path: file, fullPage: true});
    }
    return file;
};

const loginAndOpen = async(page, env) => {
    await page.goto(env.baseUrl + '/login/index.php', {waitUntil: 'domcontentloaded', timeout: 60000});
    await page.locator('#username').fill(env.username);
    await page.locator('#password').fill(env.password);
    await page.locator('#loginbtn').click({noWaitAfter: true});
    await expect(page).not.toHaveURL(/\/login\//, {timeout: 60000});
    const url = new URL('/local/course_banner_builder/admin_manage.php', env.baseUrl);
    url.searchParams.set('sourcekey', 'category:' + env.categoryId);
    await page.goto(url.toString(), {waitUntil: 'domcontentloaded', timeout: 60000});
};

const placementSnapshot = form => form.evaluate(currentForm => {
    const activeIndex = String(currentForm.dataset.activeDraftIndex || '');
    const selector = suffix => suffix + '[data-draft-index="' + activeIndex + '"]';
    const current = currentForm.querySelector(selector('[data-preview-current-image="1"][data-preview-draft-layer="1"]'));
    const visual = currentForm.querySelector(selector('[data-preview-draft-visual-layer="1"]'));
    const rect = node => {
        if (!node) {
            return null;
        }
        const value = node.getBoundingClientRect();
        return {height: value.height, left: value.left, top: value.top, width: value.width};
    };
    const placement = node => node ? {
        anchor: node.getAttribute('data-preview-anchor'),
        fitmode: node.getAttribute('data-preview-fitmode'),
        height: node.getAttribute('data-preview-custom-height'),
        left: node.getAttribute('data-preview-offset-left'),
        top: node.getAttribute('data-preview-offset-top'),
        width: node.getAttribute('data-preview-custom-width'),
    } : null;
    const field = name => currentForm.querySelector(name)?.value ?? null;
    return {
        activeIndex,
        crop: {
            enabled: field('#id_imagecropenabled'),
            height: field('#id_imagecropheightpercent'),
            left: field('#id_imagecropleftpercent'),
            top: field('#id_imagecroptoppercent'),
            width: field('#id_imagecropwidthpercent'),
        },
        current: {placement: placement(current), rect: rect(current)},
        visual: {placement: placement(visual), rect: rect(visual)},
    };
});

const expectPlacementStable = (before, after, reason) => {
    expect(after.current.placement, reason + ': current placement').toEqual(before.current.placement);
    expect(after.visual.placement, reason + ': visual placement').toEqual(before.visual.placement);
    for (const key of ['height', 'left', 'top', 'width']) {
        expect(Math.abs(after.current.rect[key] - before.current.rect[key]), reason + ': current ' + key)
            .toBeLessThanOrEqual(1);
        expect(Math.abs(after.visual.rect[key] - before.visual.rect[key]), reason + ': visual ' + key)
            .toBeLessThanOrEqual(1);
    }
};

const uploadImage = async(page, form, fixture) => {
    const add = form.locator('#fitem_id_bannerimage_filemanager .fp-btn-add a, ' +
        '#fitem_id_bannerimage_filemanager input.fp-btn-choose').first();
    await expect(add, 'File-manager add must be visible').toBeVisible({timeout: 60000});
    await add.click();
    const picker = page.locator('.file-picker:visible').last();
    await expect(picker, 'File picker must open').toBeVisible({timeout: 30000});
    let upload = picker.locator('input[name="repo_upload_file"]').first();
    if (await upload.count() === 0) {
        await picker.locator('.fp-repo-name', {hasText: /upload/i}).first().click();
        upload = picker.locator('input[name="repo_upload_file"]').first();
    }
    await upload.setInputFiles(fixture);
    await picker.locator('.fp-upload-btn').first().click();
    await expect(picker, 'File picker must close after upload').toBeHidden({timeout: 45000});
};

const dragCropHandle = async(page, form) => {
    const handle = await form.locator('[data-preview-crop-handle="se"]').first().boundingBox();
    ensure(handle, 'Crop southeast handle has no layout box.');
    await page.mouse.move(handle.x + handle.width / 2, handle.y + handle.height / 2);
    await page.mouse.down();
    await page.mouse.move(handle.x - 56, handle.y - 36, {steps: 8});
    await page.mouse.up();
};

const assertCropFlow = async(page, env, mode, viewportName, edit) => {
    const trigger = edit ?
        page.locator('[data-edit-layer-url][data-edit-layer-modal="local-course-banner-builder-edit-image-layer-modal"]').first() :
        page.locator('[data-target="#local-course-banner-builder-add-layer-modal"], ' +
            '[data-bs-target="#local-course-banner-builder-add-layer-modal"]').first();
    await expect(trigger, mode + ' image modal trigger').toBeVisible({timeout: 30000});
    await trigger.click();
    const modal = page.locator(edit ? '#local-course-banner-builder-edit-image-layer-modal:visible' :
        '#local-course-banner-builder-add-layer-modal:visible').first();
    await expect(modal, mode + ' image modal').toBeVisible({timeout: 30000});
    const form = modal.locator('form.mform').first();
    if (!edit) {
        await uploadImage(page, form, env.imageFixture);
        await uploadImage(page, form, env.imageFixture);
    }
    const image = form.locator('[data-preview-current-image="1"][data-preview-draft-layer="1"] ' +
        '[data-preview-image-tag="1"]').first();
    await expect.poll(() => image.evaluate(node => node.complete ? node.naturalWidth : 0), {timeout: 45000})
        .toBeGreaterThan(0);
    await captureHuman(page, env, '03-img08-' + viewportName + '-' + (edit ? 'edit' : 'add') + '-before-geometry', modal);
    await waitForFrames(page);
    const before = await placementSnapshot(form);
    await form.locator('[data-action="local-course-banner-builder-toggle-modal-preview-crop"]').first().click();
    await expect(form.locator('[data-preview-crop-box="1"]').first()).toBeVisible();
    await dragCropHandle(page, form);
    await form.locator('[data-action="local-course-banner-builder-apply-preview-crop"]').first().click();
    await waitForFrames(page);
    const afterApply = await placementSnapshot(form);
    expectPlacementStable(before, afterApply, mode + ' Apply');
    expect(afterApply.crop, mode + ' Apply must change crop fields').not.toEqual(before.crop);

    await form.locator('[data-action="local-course-banner-builder-undo-modal-preview-change"]').first().click();
    await waitForFrames(page);
    const afterUndo = await placementSnapshot(form);
    expectPlacementStable(before, afterUndo, mode + ' Undo');
    expect(afterUndo.crop, mode + ' Undo restores crop fields').toEqual(before.crop);

    await form.locator('[data-action="local-course-banner-builder-redo-modal-preview-change"]').first().click();
    await waitForFrames(page);
    const afterRedo = await placementSnapshot(form);
    expectPlacementStable(afterApply, afterRedo, mode + ' Redo');
    expect(afterRedo.crop, mode + ' Redo restores changed crop fields').toEqual(afterApply.crop);

    await form.locator('[data-action="local-course-banner-builder-toggle-modal-preview-crop"]').first().click();
    await dragCropHandle(page, form);
    const drafts = form.locator('[data-preview-draft-visual-layer="1"]');
    await expect(drafts, mode + ' requires two drafts for switch coverage').toHaveCount(2);
    await drafts.nth(1).click();
    await waitForFrames(page);
    await drafts.nth(0).click();
    await waitForFrames(page);
    const afterSwitch = await placementSnapshot(form);
    expectPlacementStable(before, afterSwitch, mode + ' draft-switch commit');
    expect(afterSwitch.crop, mode + ' draft-switch keeps changed crop').not.toEqual(before.crop);
    await modal.locator('[data-dismiss="modal"], [data-bs-dismiss="modal"], .close').first().click();
    await expect(modal).toBeHidden({timeout: 30000});
    await trigger.click();
    await expect(modal, mode + ' reopen').toBeVisible({timeout: 30000});
    await modal.locator('[data-dismiss="modal"], [data-bs-dismiss="modal"], .close').first().click();
    await expect(modal, mode + ' reopened modal closes').toBeHidden({timeout: 30000});
};

test('EED-CCB-2026-0042/0043/0044 cumulative visual and interaction wave', async() => {
    test.setTimeout(600000);
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

        const parentPencils = page.locator('[data-action="local-course-banner-builder-change-source-parent"]');
        await expect(parentPencils, 'Selected and configured parent pencils').toHaveCount(2);
        await expect(page.locator('[data-source-chain-row="1"]'), 'Inherited configured-source rows').toHaveCount(2);
        const collapseAll = page.locator('[data-action="local-course-banner-builder-toggle-all-source-chains"]');
        await expect(collapseAll, 'Collapse all').toBeVisible();
        await collapseAll.click();
        await expect(collapseAll).toHaveAttribute('aria-pressed', 'true');
        await collapseAll.click();
        const editSource = page.locator('[data-action="local-course-banner-builder-show-source-chain-preview"]').first();
        await expect(editSource, 'Edit source action').toBeVisible();

        await parentPencils.first().click();
        const parentModal = page.locator('#local-course-banner-builder-change-source-parent-modal');
        await expect(parentModal, 'Parent-source modal').toBeVisible();
        await captureHuman(page, env, '02-0042-parent-modal-before-sensitive', parentModal);
        evidence.captures.push('02-0042-parent-modal-before-sensitive.png');
        const search = parentModal.locator('[data-action="local-course-banner-builder-filter-sources"]');
        await expect(search, 'Parent-source search').toBeVisible();
        const parentToggle = parentModal.locator(
            '[data-source-dropdown="parent-change"] .local-course-banner-builder-source-dropdown-toggle'
        );
        await parentToggle.click();
        const parentList = parentModal.locator('[data-parent-source-change-options="1"]');
        await expect(parentList, 'Parent-source list').toBeVisible();
        const [toggleBox, listBox] = await Promise.all([parentToggle.boundingBox(), parentList.boundingBox()]);
        ensure(toggleBox && listBox && listBox.y >= toggleBox.y + toggleBox.height - 1,
            'Parent-source list must open below its trigger.');
        await search.fill('zzz-no-parent-match');
        await expect(parentList).toBeVisible();
        const save = parentModal.locator('[data-parent-source-change-submit="1"]');
        await expect(save.locator('.fa-save'), 'Parent Save icon').toHaveCount(1);
        await parentModal.locator('[data-action="local-course-banner-builder-cancel-source-parent-change"]').first().click();
        await expect(parentModal).toBeHidden();

        const checkerboard = page.locator('.local-course-banner-builder-banner-preview-frame, ' +
            '.local-course-banner-builder-border-preview-frame').first();
        await expect(checkerboard, 'Checkerboard preview surface').toBeVisible();

        await assertCropFlow(page, env, 'IMG-08 desktop add', 'desktop', false);
        await page.setViewportSize({width: 390, height: 844});
        await loginAndOpen(page, env);
        await assertCropFlow(page, env, 'IMG-08 narrow add', 'narrow', false);
        await page.setViewportSize({width: 1440, height: 900});
        await loginAndOpen(page, env);
        await assertCropFlow(page, env, 'IMG-08 desktop edit', 'desktop', true);
        await page.setViewportSize({width: 390, height: 844});
        await loginAndOpen(page, env);
        await assertCropFlow(page, env, 'IMG-08 narrow edit', 'narrow', true);

        await page.setViewportSize({width: 1440, height: 900});
        await loginAndOpen(page, env);
        const root = page.locator('[data-source-visual-editor="1"]').first();
        await expect(root, 'Source visual editor').toBeVisible();
        await captureHuman(page, env, '04-0044-motion-drag-before-sensitive', root);
        evidence.captures.push('04-0044-motion-drag-before-sensitive.png');
        const canvas = root.locator('[data-source-preview-canvas="1"], .local-course-banner-builder-source-preview-canvas').first();
        const filmstrip = root.locator('[data-source-preview-filmstrip="1"], .local-course-banner-builder-source-filmstrip').first();
        const beforeFilmstrip = await filmstrip.boundingBox();
        await root.locator('[data-source-preview-mode-value="mobile"]').click();
        await expect(root).toHaveAttribute('data-source-preview-mode', 'mobile');
        await root.locator('[data-source-preview-mode-value="desktop"]').click();
        await expect(root).toHaveAttribute('data-source-preview-mode', 'desktop');
        expect(await filmstrip.boundingBox(), 'Desktop/Mobile only animates canvas').toEqual(beforeFilmstrip);
        await expect(canvas).toBeVisible();
        await page.emulateMedia({reducedMotion: 'reduce'});
        await root.locator('[data-source-preview-mode-value="mobile"]').click();
        await expect(root).toHaveAttribute('data-source-preview-mode', 'mobile');
        await root.locator('[data-source-preview-mode-value="desktop"]').click();
        await expect(root).toHaveAttribute('data-source-preview-mode', 'desktop');

        const disclosure = page.locator('.local-course-banner-builder-layer-details-accordion').first();
        await expect(disclosure, 'Layer infos and overrides disclosure').toBeVisible();
        await disclosure.locator('summary').click();
        await expect(disclosure).toHaveAttribute('open', '');
        await disclosure.locator('summary').click();
        await expect(disclosure).not.toHaveAttribute('open', '');

        const movable = page.locator('.local-course-banner-builder-layer-row[draggable="true"]');
        const locked = page.locator('.local-course-banner-builder-layer-row--order-locked');
        await expect(movable, 'Movable layer alternatives').toHaveCount(2);
        await expect(locked, 'Locked layer alternative').toHaveCount(1);
        await expect(locked).not.toHaveAttribute('draggable', 'true');
        await movable.nth(0).dragTo(movable.nth(1));
        await expect(page.locator('.local-course-banner-builder-layer-drag-preview')).toHaveCount(0);
        await expect(page.locator('.local-course-banner-builder-layer-row-dragging')).toHaveCount(0);
        await expect(page.locator('.local-course-banner-builder-layer-row-drop-before, ' +
            '.local-course-banner-builder-layer-row-drop-after')).toHaveCount(0);
    } catch (error) {
        evidence.error = String(error && error.stack || error);
        throw error;
    } finally {
        fs.mkdirSync(env.artifactRoot, {recursive: true});
        fs.writeFileSync(path.join(env.artifactRoot, 'ccb-wave-0042-0044-evidence.json'), JSON.stringify(evidence, null, 2));
        await context.close().catch(() => {});
        await browser.close().catch(() => {});
    }
});
