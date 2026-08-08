/**
 * Source-level Guide focus-return contract.
 *
 * The Guide controller is an AMD module and its user-facing dialog behavior is
 * covered in the leased browser matrix. This focused Node test protects the
 * non-browser contract that must hold before that matrix runs: normal close
 * and Escape restore focus, while interface and guided-path transitions do
 * not steal focus back from their intended destination.
 */

import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import test from 'node:test';

const sourceUrl = new URL('../../amd/src/easyedu_guide.js', import.meta.url);
const source = await readFile(sourceUrl, 'utf8');

test('Guide captures the active opener before focusing its modal', () => {
  assert.match(source, /const returnFocus = !modal\.contains\(document\.activeElement\) \? document\.activeElement : null;\s+root\.easyeduGuideReturnFocus = returnFocus;[\s\S]*?modal\.focus\(\{preventScroll: true\}\);/);
});

test('normal Guide close and Escape restore focus to a visible opener', () => {
  assert.match(source, /const closeModal = \(root, preserveHighlight = false, restoreFocus = true\) =>/);
  assert.match(source, /const returnFocus = root\.easyeduGuideReturnFocus;\s+root\.easyeduGuideReturnFocus = null;\s+if \(restoreFocus && isVisibleElement\(returnFocus\)\) \{\s+returnFocus\.focus\(\{preventScroll: true\}\);/);
  assert.match(source, /if \(close && root\.contains\(close\)\) \{[\s\S]*?closeModal\(root\);/);
  assert.match(source, /if \(event\.key === 'Escape'\) \{\s+event\.preventDefault\(\);\s+closeModal\(root\);/);
});

test('interface and guided-path transitions close without restoring the opener', () => {
  assert.match(source, /const targetButton = event\.target\.closest\(SELECTORS\.showTarget\);[\s\S]*?closeModal\(root, true, false\);/);
  assert.match(source, /const startPath = event\.target\.closest\(SELECTORS\.startPath\);[\s\S]*?closeModal\(root, false, false\);/);
});
