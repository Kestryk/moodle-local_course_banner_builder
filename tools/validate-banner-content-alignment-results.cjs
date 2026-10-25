/* eslint-env node */
const assert = require('node:assert/strict');

const fs = require('node:fs');

const path = require('node:path');

const directory = process.argv[2];
assert(directory, 'Usage: node tools/validate-banner-content-alignment-results.cjs ARTIFACT_ROOT');

const read = file => JSON.parse(fs.readFileSync(path.join(directory, file), 'utf8').replace(/^\uFEFF/, ''));

const matrix = read('after-final.json');
const dashboard = read('dashboard-drawer-final.json');
assert(!matrix.failure && !dashboard.failure, 'Both supervised runs must finish');
assert.deepEqual(matrix.errors, []);
assert.deepEqual(dashboard.errors, []);
const formats = ['standard', 'contentwide', 'fullwidthtop', 'fullwidthtopcompact', 'fullwidthtopinset'];
const contexts = ['dashboard', 'catalogue', 'course', 'student', 'settings'];
const rows = [];
let samples = 0;
let actualDrawerCycles = 0;
let maxDelta = 0;

for (const format of formats) {
  for (const width of [1600, 768]) {
    for (const context of contexts) {
      const input = context === 'dashboard' ? dashboard : matrix;
      const matches = input.rows.filter(row => row.format === format && row.width === width && row.context === context);
      assert.equal(matches.length, 1, 'Exactly one evidence cell: ' + [format, width, context]);
      const row = matches[0];
      assert(row.states.length > 0, 'Geometry samples required');

      for (const state of row.states) {
        assert(state.shell && state.oracle, 'Banner and independent content landmark required');

        for (const edge of ['left', 'right']) {
          const delta = Math.abs(state.shell[edge] - state.oracle[edge]);
          assert(Number.isFinite(delta) && delta <= 0.5, JSON.stringify({
            format,
            width,
            context,
            state: state.state,
            edge,
            delta
          }));
          maxDelta = Math.max(maxDelta, delta);
        }

        samples++;
      }

      if (context !== 'catalogue') {
        const settled = row.states.filter(state => state.state === 'initial' || state.state.endsWith('settled'));
        assert(settled.some(state => state.drawers.some(drawer => drawer.open)), 'Actual drawer must open: ' + context);
        assert(settled.some(state => !state.drawers.some(drawer => drawer.open)), 'Actual drawer must close: ' + context);
        actualDrawerCycles++;
      }

      rows.push({ ...row,
        evidence: context === 'dashboard' ? 'explicit native dashboard commands' : 'main native matrix'
      });
    }
  }
}

const result = {
  cases: rows.length,
  samples,
  actualDrawerCycles,
  maxDelta,
  errors: [],
  rows
};
fs.writeFileSync(path.join(directory, 'final-combined.json'), JSON.stringify(result, null, 2));
console.log(JSON.stringify({ ...result,
  rows: undefined
}));
