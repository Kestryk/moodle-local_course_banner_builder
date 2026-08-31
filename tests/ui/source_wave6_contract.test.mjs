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

test('large authoring preview reuses an isolated readonly modal renderer', async() => {
    const php = await read('admin_manage.php');
    const source = await read('amd/src/admin_manage.js');

    assert.match(php, /data-action' => 'local-course-banner-builder-show-large-source-preview'/u);
    assert.match(source, /function localCourseBannerBuilderShowLargeSourcePreview\(button\)/u);
    assert.match(source, /localCourseBannerBuilderCloneReadonlySourcePreview\(sourcePanel\)/u);
    assert.match(source, /localCourseBannerBuilderShowModal\(modal\)/u);
    assert.match(source, /function localCourseBannerBuilderCloneReadonlySourcePreview\(sourcePanel\)/u);
    assert.match(source, /sourcePanel\.cloneNode\(true\)/u);
    assert.match(source, /panel\.setAttribute\('data-source-preview-readonly', '1'\)/u);
    assert.match(source, /var isModalPreview = !!root\.closest\('\.local-course-banner-builder-source-chain-preview-modal'\)/u);
    assert.match(source, /if \(sourcekey && !isModalPreview\)/u);
    assert.match(source, /localCourseBannerBuilderSourcePreviewModes\[sourcekey\] = mode/u);
});

test('large preview has no nested launcher and no empty footer rail', async() => {
    const source = await read('amd/src/admin_manage.js');
    const adapter = await read('scss/components/_easyedu-adapter.scss');

    assert.match(source, /\[data-action="local-course-banner-builder-show-large-source-preview"\],[\s\S]*?'form'/u);
    assert.match(adapter, /\.local-course-banner-builder-source-chain-preview-modal-footer \{[\s\S]*?&:empty \{\s*display: none;/u);
    assert.match(adapter, /\[data-action="local-course-banner-builder-show-large-source-preview"\]\.is-focus-returned/u);
});
