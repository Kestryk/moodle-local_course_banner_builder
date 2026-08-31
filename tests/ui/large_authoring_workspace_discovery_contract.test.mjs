// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import test from 'node:test';

const read = relativePath => readFile(new URL(`../../${relativePath}`, import.meta.url), 'utf8');

test('large-workspace discovery maps the existing editor services', async() => {
    const php = await read('admin_manage.php');
    const source = await read('amd/src/admin_manage.js');
    const manager = await read('classes/manager.php');
    const design = await read('docs/architecture/large-authoring-workspace.md');

    assert.match(php, /function local_course_banner_builder_render_source_visual_editor\(/u);
    assert.match(php, /name' => 'previewlayerpayload'/u);
    assert.match(source, /function localCourseBannerBuilderStartSourcePreviewInteraction\(/u);
    assert.match(source, /function localCourseBannerBuilderFindPreviewSnap\(/u);
    assert.match(source, /function localCourseBannerBuilderBuildSourcePreviewHistorySnapshot\(/u);
    assert.match(source, /function localCourseBannerBuilderSaveSourcePreviewChanges\(/u);
    assert.match(manager, /function update_source_visual_editor_layers\(/u);

    assert.match(design, /Do not create a second stateful clone with duplicate form ids\./u);
    assert.match(design, /Zoom and pan are excluded from `previewlayerpayload` and document undo\/redo\./u);
    assert.match(design, /No third-party canvas\/editor dependency is introduced\./u);
});

test('prototype remains explicitly disposable until seven decisions are approved', async() => {
    const source = await read('amd/src/admin_manage.js');
    const design = await read('docs/architecture/large-authoring-workspace.md');
    const geometry = await read('docs/architecture/banner-geometry.md');
    const batch = await read('docs/history/batches/eed-ccb-2026-0049-contained-large-authoring-preview.md');
    const registry = await read('docs/history/batch-registry.md');
    const changelog = await read('CHANGELOG.md');

    assert.match(source, /function localCourseBannerBuilderCloneReadonlySourcePreview\(sourcePanel\)/u);
    assert.match(source, /panel\.setAttribute\('data-source-preview-readonly', '1'\)/u);
    assert.match(design, /## Seven human decisions/u);

    const decisionRows = design.match(/^\| [1-7] \|/gmu) || [];
    assert.equal(decisionRows.length, 7);
    assert.match(design, /They are not final\s+until the human accepts or changes them\./u);
    assert.match(design, /No interactive code or CSS is added by this discovery batch\./u);
    assert.match(geometry, /must remain disposable and\s+must not be extended into an interactive clone\./u);
    assert.match(batch, /No product code, persistence contract, runtime promotion or managed preview is\s+authorised/u);
    assert.match(registry, /Discovery complete - seven human decisions pending/u);
    assert.match(changelog, /No\s+product behavior changed; seven human decisions remain before implementation\./u);
});
