# EED-CCB-2026-0057-RF1 — Transient mutation notifications

## Scope

This corrective lot standardises feedback for the CCB mutations that already
run through AJAX and update a selected-source fragment without reloading the
page. It does not convert redirect forms or read-only loaders into AJAX.

Covered routes are selected-source preview save, selected-preview-layer
deletion, delete-all-source-layers, bulk selected-layer deletion and the
configured-source parent change. The existing server validation, sesskey
checks, confirmations, busy lock, fragment replacement and focus ownership
remain unchanged.

## Implementation

- CCB `admin_manage` now prefers Moodle `core/toast` with the EasyStud delay of
  four seconds and maps errors to Moodle's `danger` toast type.
- The established `core/notification` path remains only as a compatibility
  fallback when the toast renderer is unavailable.
- Parent-source failures retain an inline `role="alert"` / assertive live
  region, keep the modal open and return focus to Save; the toast supplements
  that actionable message.
- Classic create/edit/delete/settings/title forms, read-only source preview and
  layer-modal loading, draft-file-manager calls and client-only controls are
  explicitly excluded because they do not have this no-reload mutation
  contract.

## Files

- `amd/src/admin_manage.js`
- `amd/build/admin_manage.min.js`
- `amd/build/admin_manage.min.js.map`
- `templates/admin_manage.mustache`
- `docs/architecture/transient-async-notifications.md`
- `tools/test-ccb-transient-notifications-contract.ps1`
- `CHANGELOG.md`

## Validation

Passed from the plugin worktree:

- `tools/test-ccb-transient-notifications-contract.ps1`
- `tools/test-async-editor-contract.ps1`
- `tools/test-general-preview-async-contract.ps1`
- `tools/test-parent-source-modal-contract.ps1`
- `node --check amd/src/admin_manage.js`
- `node --check amd/build/admin_manage.min.js`
- `php -l admin_manage.php`
- `git diff --check`

The generated AMD was produced by Moodle 5.1 Grunt in an isolated full
checkout using Node 22.11.0. Grunt completed with `--force` because the base
CCB module carries pre-existing ESLint debt; the targeted source/build syntax,
canonical `define` wrapper, dependency and no-top-level-ESM checks passed.
No runtime, cache purge, browser or managed-preview check was run in this
source-only lot.

## Handoff

Source branch: `work/port4719pg3/eed-ccb-2026-0057-rf1`.

Human review should verify one successful AJAX save/delete and one failed
parent-source request: each must show a temporary toast, with no page reload;
the failed parent request must also remain visible in the modal and return
focus to Save.
