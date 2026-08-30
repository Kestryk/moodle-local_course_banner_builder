const {test, expect} = require('@playwright/test');
const fs = require('fs');
const path = require('path');

const requireEnvironment = () => {
    const names = [
        'EASYEDU_MOODLE_URL',
        'EASYEDU_MOODLE_USERNAME',
        'EASYEDU_MOODLE_PASSWORD',
        'EASYEDU_CCB_CROP_HISTORY_MODAL_URL',
        'EASYEDU_CCB_CROP_HISTORY_ELEMENT_ID',
        'EASYEDU_CCB_CROP_HISTORY_ARTIFACT_ROOT',
        'EASYEDU_CCB_CROP_HISTORY_MANIFEST',
    ];
    const missing = names.filter(name => !process.env[name]);
    if (missing.length) {
        throw new Error('Missing CROP-08 environment values: ' + missing.join(', '));
    }
    return {
        artifactRoot: path.resolve(process.env.EASYEDU_CCB_CROP_HISTORY_ARTIFACT_ROOT),
        elementId: String(process.env.EASYEDU_CCB_CROP_HISTORY_ELEMENT_ID),
        manifest: path.resolve(process.env.EASYEDU_CCB_CROP_HISTORY_MANIFEST),
        modalUrl: process.env.EASYEDU_CCB_CROP_HISTORY_MODAL_URL,
        password: process.env.EASYEDU_MOODLE_PASSWORD,
        url: process.env.EASYEDU_MOODLE_URL.replace(/\/$/, ''),
        username: process.env.EASYEDU_MOODLE_USERNAME,
    };
};

const login = async(page, env) => {
    await page.goto(env.url + '/login/index.php', {waitUntil: 'domcontentloaded'});
    await page.locator('#username').fill(env.username);
    await page.locator('#password').fill(env.password);
    await Promise.all([
        page.waitForURL(url => url.protocol.startsWith('http') && !url.pathname.endsWith('/login/index.php'), {timeout: 60000}),
        page.locator('#loginbtn').click(),
    ]);
};

const sample = form => form.evaluate(node => {
    const settings = JSON.parse(node.querySelector('#id_multilayerdraftsettings')?.value || '{}');
    const active = String(node.dataset.activeDraftIndex || '');
    const current = node.querySelector('[data-preview-current-image="1"][data-preview-draft-layer="1"]');
    const rect = current?.getBoundingClientRect();
    return {
        active,
        crop: settings[active] ? {
            enabled: !!settings[active].imagecropenabled,
            height: Number(settings[active].imagecropheightpercent),
            left: Number(settings[active].imagecropleftpercent),
            top: Number(settings[active].imagecroptoppercent),
            width: Number(settings[active].imagecropwidthpercent),
        } : null,
        rect: rect ? {height: rect.height, width: rect.width, x: rect.x, y: rect.y} : null,
        settings,
    };
});

test('CROP-08 restores chronological non-destructive transformations across two existing images', async({page}) => {
    test.setTimeout(120000);
    const env = requireEnvironment();
    fs.mkdirSync(env.artifactRoot, {recursive: true});
    await login(page, env);
    await page.goto(env.modalUrl, {waitUntil: 'domcontentloaded'});

    const edit = page.locator('[data-edit-layer-url*="elementid=' + env.elementId + '"]');
    await expect(edit, 'CROP-08 fixture image layer must expose its edit action.').toHaveCount(1);
    await edit.click();
    // The edit response is rendered as a Bootstrap dialog. Its accessible
    // dialog is stable, while the placeholder's data attribute and Moodle's
    // form class are not guaranteed to survive the AJAX body replacement.
    // Scope to the visible edit dialog and require the image Filemanager field
    // so an old/hidden modal can never be selected.
    const editDialog = page.locator(
        '[role="dialog"].modal.show, [role="dialog"].modal[aria-modal="true"]'
    ).filter({has: page.locator('#id_bannerimage_filemanager')}).last();
    await expect(editDialog, 'CROP-08 image edit dialog must be visible after the edit action.').toBeVisible();
    const form = editDialog.locator('form').filter({has: page.locator('#id_bannerimage_filemanager')}).last();
    await expect(form).toBeVisible();
    const draftitemid = await form.locator('#id_bannerimage_filemanager').inputValue();
    expect(draftitemid).toMatch(/^\d+$/);
    const manifest = JSON.parse(fs.readFileSync(env.manifest, 'utf8').replace(/^\uFEFF/, ''));
    manifest.draftitemid = Number(draftitemid);
    fs.writeFileSync(env.manifest, JSON.stringify(manifest, null, 2));
    const selectors = form.locator('[data-draft-preview-select="1"]');
    await expect(selectors).toHaveCount(2);
    const firstIndex = await selectors.nth(0).getAttribute('data-draft-index');
    const secondIndex = await selectors.nth(1).getAttribute('data-draft-index');
    expect(firstIndex).toBeTruthy();
    expect(secondIndex).toBeTruthy();

    await selectors.nth(0).click();
    await expect(selectors.nth(0)).toHaveAttribute('aria-pressed', 'true');
    const beforeCrop = await sample(form);
    const crop = form.locator('[data-action="local-course-banner-builder-toggle-modal-preview-crop"]');
    await crop.click();
    const handle = form.locator('[data-preview-crop-handle="se"]');
    await expect(handle).toBeVisible();
    const box = await handle.boundingBox();
    if (!box) {
        throw new Error('CROP-08 crop handle has no geometry.');
    }
    await page.mouse.move(box.x + box.width / 2, box.y + box.height / 2);
    await page.mouse.down();
    await page.mouse.move(box.x - 45, box.y - 30, {steps: 8});
    await page.mouse.up();
    await form.locator('[data-action="local-course-banner-builder-apply-modal-preview-crop"]').click();
    const afterCrop = await sample(form);
    expect(afterCrop.crop?.enabled).toBe(true);
    expect(afterCrop.crop?.width).toBeLessThan(beforeCrop.crop?.width || 100);
    expect(Math.abs((afterCrop.rect?.width || 0) - (beforeCrop.rect?.width || 0))).toBeLessThanOrEqual(1);
    expect(Math.abs((afterCrop.rect?.height || 0) - (beforeCrop.rect?.height || 0))).toBeLessThanOrEqual(1);

    await selectors.nth(1).click();
    await expect(selectors.nth(1)).toHaveAttribute('aria-pressed', 'true');
    const beforeFit = await sample(form);
    await form.locator('[data-action="local-course-banner-builder-fit-layer-preview-image"]').click();
    const afterFit = await sample(form);
    expect(afterFit.active).toBe(String(secondIndex));
    expect(afterFit.settings[String(firstIndex)].imagecropenabled).toBe(true);

    await form.locator('[data-action="local-course-banner-builder-fill-layer-preview-image"]').click();
    const afterFill = await sample(form);
    const opacityToggle = form.locator('[data-action="local-course-banner-builder-toggle-modal-preview-opacity"]');
    await opacityToggle.click();
    const opacity = form.locator('[data-preview-opacity-panel="modal"] input[type="range"]');
    await expect(opacity).toBeVisible();
    await opacity.focus();
    await opacity.press('ArrowLeft');
    const afterOpacity = await sample(form);
    expect(afterOpacity.settings[String(secondIndex)].imageopacity).toBeLessThan(
        afterFill.settings[String(secondIndex)].imageopacity
    );

    const undo = form.locator('[data-action="local-course-banner-builder-undo-modal-preview-change"]');
    const redo = form.locator('[data-action="local-course-banner-builder-redo-modal-preview-change"]');
    await undo.click(); // Opacity.
    await undo.click(); // Fill.
    await undo.click(); // Fit.
    await undo.click(); // Active image switch.
    const restoredCrop = await sample(form);
    expect(restoredCrop.active).toBe(String(firstIndex));
    expect(restoredCrop.crop).toEqual(afterCrop.crop);
    await redo.click();
    await redo.click();
    await redo.click();
    await redo.click();
    const restoredOpacity = await sample(form);
    expect(restoredOpacity.active).toBe(String(secondIndex));
    expect(restoredOpacity.settings[String(firstIndex)].imagecropenabled).toBe(true);
    expect(restoredOpacity.settings[String(secondIndex)].fitmodeoverride).toBe(afterFill.settings[String(secondIndex)].fitmodeoverride);
    expect(restoredOpacity.settings[String(secondIndex)].imageopacity).toBe(
        afterOpacity.settings[String(secondIndex)].imageopacity
    );

    await form.screenshot({path: path.join(env.artifactRoot, 'crop-08-history-final.png')});
});
