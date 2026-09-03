# EED-CCB-2026-0042-RF4 - Portalled Parent modal shared actions

Status: source candidate; managed preview and human review pending.
Base: cumulative CCB Wave 11 source `3c8c6c3daec1828c5cf62c64ce9f3e921341806d`.

## Evidence

The Change parent modal is portalled from the CCB administration root to
`document.body` while open. RF3 correctly kept its Parent search disclosure
inside the opaque modal, but the Save action still consumed
`easyedu.save-action-button` only below `.local-course-banner-builder-admin--native`.
That selector no longer matched after the portal move, so Bootstrap/Moodle
could own the visible Save states. The Close action also retained a raw
typographic multiplication character even though the embedded component
contract rejects raw `x` controls.

## Correction

The Parent modal keeps its existing portal, dropdown, request, dismissal and
focus-return behavior. Its Save action now consumes the already embedded
`easyedu.save-action-button` from the generic CCB modal scope, after the shared
footer geometry rule. The Close action remains on
`easyedu.modal-close-button(small)` and uses the canonical `fa-times` icon.

No token, mixin, component variant, JavaScript hook or Kit source changed.
Crop, layer editing and the CCB 0049 authoring workspace are outside this lot.

## Validation

- `tools/test-parent-source-modal-contract.ps1` verifies the portal-owned
  dropdown, shared primitives and final generated-CSS cascade.
- `tools/playwright/ccb-parent-source-modal.spec.js` retains RF3 functional
  assertions and adds Close centring, exact shared Save hover/focus colours and
  390 px containment.
- Official Sass must be rebuilt and `git diff --check`, PHP/JavaScript syntax
  and the focused source contract must pass before commit.
- No fixture, browser, cache purge or runtime preview belongs to the source
  worktree. The existing leased Parent scenario remains the later preview gate.

## RF5 compact transaction feedback

The portalled footer now applies the shared small action geometry after the
generic modal rule, so Save and Cancel cannot be enlarged by the later
Bootstrap-facing cascade. Save toggles the administration root's established
bottom-end busy feedback. A successful response also returns and rehydrates
the complete selected-source fragment, keeping its general preview visible and
current without changing the guarded Parent endpoint, sesskey or capability.

## Allowlist

- `templates/admin_manage.mustache`
- `scss/components/_easyedu-adapter.scss`
- `styles.css`
- `tools/test-parent-source-modal-contract.ps1`
- `tools/playwright/ccb-parent-source-modal.spec.js`
- `docs/testing/functional-protocol.md`
- `docs/history/batches/eed-ccb-2026-0042-rf4-parent-modal-shared-actions.md`
- `docs/history/batch-registry.md`
- `CHANGELOG.md`
