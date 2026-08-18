# Async editor actions

## Scope

EED-CCB-2026-0013 reduces avoidable page reloads in Course Banner Builder
without bypassing Moodle forms, capabilities, sesskeys or server validation.
The plugin declares Moodle 4.5 as its minimum version. The active preview target
is Moodle 5.1; Moodle 4.5 executable coverage remains required before release.

All actions below enter through `admin_manage.php`, after the system-level
`local/course_banner_builder:manage` capability check. State-changing forms
carry a Moodle sesskey. An action is eligible for a local refresh only when the
server can return the complete selected-source fragment needed to replace the
affected interface safely.

## Read-only action audit

| Action family | Validation and persistence | Current response | Async decision |
| --- | --- | --- | --- |
| Site/course enablement and banner-format settings | Typed Moodle parameters, sesskey, plugin configuration setters | Redirect and Moodle notification | Deferred: changes affect page-wide navigation, disabled controls and previews. |
| Title and source settings | Typed parameters, sesskey, source resolution and manager validation | Redirect and Moodle notification | Deferred: the complete dependent settings state must be returned, not reconstructed in JavaScript. |
| Inline and bulk layer-row saves | Typed arrays/fields, sesskey and manager row-update methods | Redirect and Moodle notification | Candidate for a later tranche after dirty-state and row-error contracts are defined. |
| Visual-editor save | JSON payload parsed server-side, sesskey, stored-source prerequisite and manager validation | Redirect; warning when source settings do not exist | Next save tranche: return authoritative preview state and preserve undo/redo plus dirty state. |
| Layer modal save | Moodle form validation, draft files, crop state and manager persistence | Redirect with success/warning | Deferred: file drafts and validation errors require a dedicated form-response contract. |
| Delete one visual-preview layer | Confirmation, POST, sesskey, capability and manager deletion | JSON selected-source fragment | Implemented first: safe local replacement, shared busy state, Moodle toast and focus restoration. |
| Delete selected table layers | Confirmation, POST, typed element IDs, sesskey, capability and manager deletion | JSON selected-source fragment | Implemented first with the same interaction contract. |
| Delete every layer for the selected source | Confirmation, POST, sesskey, capability and source-image deletion | JSON selected-source fragment | Implemented first; source rules are preserved exactly as before. |
| Delete one table layer through its legacy URL | Sesskey, capability and manager deletion | Redirect and Moodle notification | Deferred until it can reuse the proven JSON deletion contract without changing row ownership. |
| Delete source layers or complete source rules | Moodle confirmation form, POST, sesskey and manager cascading deletion | Redirect and Moodle notification | Deferred: persistence and inherited-source consequences require their own fixture and rollback plan. |

## First tranche contract

- Cancellation performs no request and restores focus to the initiating button.
- During a confirmed request, the administration root exposes `aria-busy=true`
  and the shared EasyEdu `Loading in progress` indicator; the initiating action
  is disabled against double submission. The indicator uses the established
  EasyStud fixed bottom-end placement rather than a page-top modal cue.
- The server remains authoritative and returns rendered `admin_selected`
  markup plus a translated result message.
- Success replaces only the selected-source region, rehydrates its existing
  CCB behavior, updates open layer modals and announces a Moodle success toast.
- When that replacement leaves no layers, the layer-list empty state retains
  the shared Kit surface and centred message with enough vertical space to read
  as a completed local update rather than a collapsed table gap.
- Failure preserves the existing DOM and dirty state, announces an error toast
  and restores focus to the initiating action.
- The three JSON mutations accept POST only and retain the existing capability,
  sesskey and typed-parameter gates.

Official references consulted: Moodle 4.5 JavaScript/AJAX guidance and Moodle
security guidance for sesskeys and state-changing requests.

## Static validation

Run the focused source/generated contract from the plugin root:

```powershell
.\tools\test-async-editor-contract.ps1
```

The check proves that all three first-tranche mutations remain POST-only with
sesskey/source gates, that translated messages and Moodle notifications are
  present, that the source declares its canonical AMD module, and that the
  generated AMD/CSS outputs preserve the expected dependencies and shared
  busy-state selectors. It does not replace a Moodle 5.1 interaction
review or future Moodle 4.5 compatibility execution.

## Moodle 5.1 supervised scenario

`tools/playwright/Invoke-CCBAsyncEditorActionsValidation.ps1` discovers and
runs exactly one `ccb-async-editor-actions` scenario under the shared Moodle
fixture lease. It creates the existing disposable CCB layer fixture, verifies
cancelled and confirmed selected/all deletion, then removes the fixture category
in `finally`. Its browser profile, screenshots, logs, cleanup record and
artifact manifest remain outside Git.
