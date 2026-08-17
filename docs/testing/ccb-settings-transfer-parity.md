# CCB Settings and Transfer parity

## Scenario

`ccb-settings-transfer-parity` is a single, read-only Playwright scenario for
the Moodle 5.1 CCB preview. The current reconciliation preserves the seven-file
functional payload from `37d3335` on cumulative preview base `6745ca9`, along
with the later Moodle selector corrections already present on that base.

The scenario lives at:

`tools/playwright/ccb-settings-transfer-parity.spec.js`

## Contract

The scenario authenticates as the configured CCB administrator and then:

- opens Moodle's human-facing CCB category route and checks that General
  settings use one compact, bounded panel with a compact identity header and
  flat setting rows;
- checks that Delete settings has readable contrast, can receive keyboard
  focus and does not navigate;
- checks that Transfer renders two aligned, equal-width desktop panels and
  that visible checkboxes use Moodle's `form-check` structure and
  `form-check-input` class;
- checks that `replaceall` stays unchecked and that its checkbox and label are
  vertically aligned, locating the row through Moodle's `.fitem` structure
  rather than relying on an optional generated wrapper ID, and explicitly
  ignoring Moodle's hidden fallback input with the same field name;
- checks the real Moodle `.fp-btn-choose` control has at least 12 px of bottom
  margin after Moodle's asynchronous filepicker initialization completes.

The scenario never clicks Delete settings, opens the filepicker, changes a
checkbox, submits an import/export form or saves settings. After login it also
records state-changing HTTP methods directed at the Settings category, direct
Settings, Reset and Transfer routes, and fails if any are observed.

## Execution gate

Do not execute this scenario directly against the shared runtime. Platform
must first add `ccb-settings-transfer-parity` to the CCB scenario allowlist,
publish the containing integration commit and acquire the CCB runtime lease.

The expected Playwright selection is exactly one test:

```text
ccb-settings-transfer-parity.spec.js: ccb-settings-transfer-parity
```

No success screenshot is produced by the scenario. Playwright-managed trace,
video or failure screenshot output, when enabled by the Platform profile,
must remain in the external artifact root.

## Required environment

- `CCB_MOODLE_URL`
- `CCB_MOODLE_USERNAME`
- `CCB_MOODLE_PASSWORD`

The credentials remain process-local and must not be committed or written to
the test output.
