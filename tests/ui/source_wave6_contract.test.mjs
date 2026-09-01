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
    assert.match(source, /body\.appendChild\(sourcePanel\)/u);
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
