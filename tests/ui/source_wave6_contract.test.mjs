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

test('unlocked image rows omit the empty auxiliary-status slot', async() => {
    const manager = await read('classes/manager.php');
    const template = await read('templates/admin_selected.mustache');

    assert.match(manager, /\$haslayerstatusindicators = \$islockedlayer \|\| \$iscroppedlayer \|\| \$iscenterfixedlayer;/u);
    assert.match(manager, /'haslayerstatusindicators' => \$haslayerstatusindicators,/u);
    assert.match(template, /\{\{#haslayerstatusindicators\}\}/u);
    assert.match(template, /class="local-course-banner-builder-layer-sort-indicators"/u);
    assert.match(template, /\{\{\/haslayerstatusindicators\}\}/u);
});

test('large authoring workspace mounts the one live editor and restores it', async() => {
    const php = await read('admin_manage.php');
    const source = await read('amd/src/admin_manage.js');

    assert.match(php, /data-action' => 'local-course-banner-builder-show-large-source-preview'/u);
    assert.match(source, /function localCourseBannerBuilderShowLargeSourcePreview\(button\)/u);
    assert.match(source, /function localCourseBannerBuilderRestoreLargeSourcePreviewMount\(modal\)/u);
    assert.match(source, /parent\.insertBefore\(placeholder, sourcePanel\)/u);
    assert.match(source, /workspace\.appendChild\(panel\)/u);
    assert.match(source, /body\.appendChild\(mount\.workspace\)/u);
    assert.match(source, /mount\.placeholder\.parentNode\.replaceChild\(mount\.panel, mount\.placeholder\)/u);
    assert.match(source, /localCourseBannerBuilderShowModal\(modal\)/u);
    assert.doesNotMatch(source, /function localCourseBannerBuilderCloneReadonlySourcePreview\(/u);
    assert.doesNotMatch(source, /sourcePanel\.cloneNode\(true\)/u);
    assert.match(source, /var isModalPreview = !!root\.closest\('\.local-course-banner-builder-source-chain-preview-modal'\)/u);
    assert.match(source, /if \(sourcekey && !isModalPreview\)/u);
    assert.match(source, /localCourseBannerBuilderSourcePreviewModes\[sourcekey\] = mode/u);
});

test('large authoring workspace hides its nested launcher and keeps the footer empty', async() => {
    const source = await read('amd/src/admin_manage.js');
    const adapter = await read('scss/components/_easyedu-adapter.scss');

    assert.match(source, /document\.documentElement\.clientWidth < 1024/u);
    assert.match(source, /largeeditorrequiresdesktop/u);
    assert.match(adapter, /\[data-source-preview-large-workspace="1"\][\s\S]*?\[data-action="local-course-banner-builder-show-large-source-preview"\][\s\S]*?display: none;/u);
    assert.match(adapter, /\.local-course-banner-builder-source-chain-preview-modal-footer \{[\s\S]*?&:empty \{\s*display: none;/u);
    assert.match(adapter, /\[data-action="local-course-banner-builder-show-large-source-preview"\]\.is-focus-returned/u);
});

test('large authoring workspace isolates one published frame inside a checkerboard plane', async() => {
    const source = await read('amd/src/admin_manage.js');
    const adapter = await read('scss/components/_easyedu-adapter.scss');

    assert.match(source, /frame\.parentNode\.insertBefore\(mount\.framePlaceholder, frame\)/u);
    assert.match(source, /mount\.framePlaceholder\.parentNode\.replaceChild\(mount\.frame, mount\.framePlaceholder\)/u);
    assert.match(source, /plane\.appendChild\(frame\)/u);
    assert.match(source, /workspace\.appendChild\(panel\)/u);
    assert.match(source, /availableWidth \/ mount\.frameWidth/u);
    assert.match(source, /event\.target === mount\.plane \|\| event\.target === mount\.stage/u);
    assert.doesNotMatch(source, /frame\.cloneNode\(/u);
    assert.match(adapter, /\.local-course-banner-builder-large-workspace-plane[\s\S]*?transform: scale\(var\(--local-course-banner-builder-large-workspace-zoom\)\)/u);
    assert.match(adapter, /\[data-large-workspace-published-frame="1"\]/u);
    assert.doesNotMatch(adapter, /\.local-course-banner-builder-source-preview-panel\[data-source-preview-large-workspace="1"\][^{]*\{[^}]*transform: scale/u);
});

test('large authoring workspace keeps a majority scene and an independently scrolling dock', async() => {
    const source = await read('amd/src/admin_manage.js');
    const adapter = await read('scss/components/_easyedu-adapter.scss');

    assert.match(adapter, /grid-template-columns: minmax\(0, 1fr\) minmax\(12rem, clamp\(12rem, 16vw, 16rem\)\)/u);
    assert.match(adapter, /grid-template-rows: minmax\(18rem, 1fr\) auto auto auto/u);
    assert.match(adapter, /\.local-course-banner-builder-source-preview-controls \{[\s\S]*?overflow-y: auto;[\s\S]*?scrollbar-gutter: stable;/u);
    assert.match(adapter, /\.local-course-banner-builder-source-preview-controls[\s\S]*?\.local-course-banner-builder-source-preview-button \{[\s\S]*?max-height: var\(--local-course-banner-builder-action-height, 2\.45rem\);/u);
    assert.match(source, /localCourseBannerBuilderUpdateSourcePreviewFilmstripNav\(filmstrip\)/u);
    assert.doesNotMatch(adapter, /data-source-preview-large-workspace="1"[^}]*?\.local-course-banner-builder-large-workspace-plane[^}]*?grid-template-columns/u);
});

test('large authoring workspace materialises and centres stable published geometry', async() => {
    const source = await read('amd/src/admin_manage.js');

    assert.match(source, /var desktopRatios = \{/u);
    assert.match(source, /var stableDesktopHeight = stableDesktopWidth \/ desktopRatio/u);
    assert.match(source, /function localCourseBannerBuilderSyncLargeWorkspaceFrameMode\(mount\)/u);
    assert.match(source, /mount\.mobileFrameWidth : mount\.desktopFrameWidth/u);
    assert.match(source, /mount\.plane\.style\.width = mount\.planeWidth \+ 'px'/u);
    assert.match(source, /mount\.frame\.style\.width = mount\.frameWidth \+ 'px'/u);
    assert.match(source, /Math\.floor\(\(stageWidth - scaledPlaneWidth\) \/ 2\)/u);
    assert.match(source, /frameInlineStyle: frame \? frame\.getAttribute\('style'\) : null/u);
});
