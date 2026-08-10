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

test('Guide launcher uses one visible help treatment', async() => {
    const template = await read('templates/easyedu_guide.mustache');

    assert.match(template, /aria-label="\{\{guideopenlabel\}\}"/u);
    assert.doesNotMatch(template, /data-easyedu-navigation-popover|data-easyedu-hover-help|\stitle=/u);
});

test('compact Navigation trigger has an explicit 44px token contract', async() => {
    const tokens = await read('scss/easyedu/_tokens.scss');
    const navigation = await read('scss/easyedu/components/_navigation.scss');

    assert.match(tokens, /--easyedu-touch-target-min:\s*2\.75rem;/u);
    assert.match(navigation, /block-size:\s*var\(--easyedu-touch-target-min\);/u);
    assert.match(navigation, /inline-size:\s*var\(--easyedu-touch-target-min\);/u);
});

test('responsive destination glyphs use the EasyStud icon tile states', async() => {
    const responsive = await read('scss/easyedu/components/_responsive.scss');
    const iconRule = responsive.match(/> \.fa:first-child \{(?<body>[\s\S]*?)\n  \}/u);

    assert.ok(iconRule?.groups?.body, 'The compact icon rule must remain explicit.');
    assert.match(iconRule.groups.body, /background:\s*var\(--easyedu-surface\);/u);
    assert.match(iconRule.groups.body, /border:\s*1px solid var\(--easyedu-card-border\);/u);
    assert.match(iconRule.groups.body, /flex:\s*0 0 1\.85rem;/u);
    assert.match(responsive, /&\[aria-current="page"\] > \.fa:first-child,[\s\S]*?background:\s*var\(--easyedu-primary\);/u);
});

test('expanded trigger width accommodates the CCB localized label', async() => {
    const tokens = await read('scss/easyedu/_tokens.scss');
    const navigation = await read('scss/easyedu/components/_navigation.scss');

    assert.match(tokens, /--easyedu-navigation-trigger-expanded-width:\s*22rem;/u);
    assert.match(navigation, /inline-size:\s*min\([\s\S]*?var\(--easyedu-navigation-trigger-expanded-width\)/u);
});

test('CCB desktop destinations compose the shared flat Kit rail', async() => {
    const navigation = await read('scss/easyedu/components/_navigation.scss');
    const adapter = await read('scss/components/_easyedu-adapter.scss');

    assert.match(navigation, /@mixin navigation-desktop-rail[\s\S]*?background:\s*transparent;[\s\S]*?border:\s*0;[\s\S]*?border-radius:\s*0;[\s\S]*?box-shadow:\s*none;/u);
    assert.match(navigation, /@mixin navigation-desktop-item[\s\S]*?\[aria-current="page"\][\s\S]*?background:\s*transparent;[\s\S]*?inset 0 -0\.2rem 0 var\(--easyedu-primary\)/u);
    assert.match(navigation, /\[data-easyedu-navigation-desktop\] \{[\s\S]*?@include navigation-desktop-rail;/u);
    assert.match(navigation, /\.easyedu-navigation__item \{[\s\S]*?@include navigation-desktop-item;/u);
    assert.doesNotMatch(adapter, /\.easyedu-navigation \[data-easyedu-navigation-desktop\]/u);
});
