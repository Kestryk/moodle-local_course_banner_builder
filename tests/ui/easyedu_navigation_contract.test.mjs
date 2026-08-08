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

test('responsive destination glyphs do not create an icon tile', async() => {
    const responsive = await read('scss/easyedu/components/_responsive.scss');
    const iconRule = responsive.match(/> \.fa:first-child \{(?<body>[\s\S]*?)\n  \}/u);

    assert.ok(iconRule?.groups?.body, 'The compact icon rule must remain explicit.');
    assert.match(iconRule.groups.body, /background:\s*transparent;/u);
    assert.match(iconRule.groups.body, /border:\s*0;/u);
    assert.match(iconRule.groups.body, /border-radius:\s*0;/u);
    assert.match(iconRule.groups.body, /flex:\s*0 0 1rem;/u);
    assert.doesNotMatch(iconRule.groups.body, /1\.85rem/u);
});
