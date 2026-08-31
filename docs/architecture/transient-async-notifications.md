# Transient feedback for CCB asynchronous actions

## Scope

`EED-CCB-2026-0057-RF1` standardises feedback for the CCB mutations that
already refresh part of the page without a full reload. It changes feedback
only; it does not change request payloads, server transactions, focus-owned
editing controls or preview geometry.

The client uses Moodle's `core/toast` with a four-second delay, matching the
EasyStud temporary notification behavior. The toast type maps CCB's error
state to Moodle's `danger` style. The existing `core/notification` API is a
compatibility fallback for themes that do not expose the toast module.

Error paths remain actionable: the existing inline parent-source validation
message is retained, the modal stays open, and focus returns to its Save
control. Other asynchronous errors keep their initiating control focused.
Toast announcements are therefore supplementary feedback, not the only error
communication.

## Covered mutation routes

Only routes already proven to be asynchronous are covered:

| Mutation | Request marker | Local update | Feedback |
| --- | --- | --- | --- |
| Save selected-source visual preview | `updatepreviewlayersajax=1` | Selected-source fragment | Success or error toast |
| Delete selected preview layer | `deletepreviewlayerajax` | Selected-source fragment | Success or error toast |
| Delete all source layers | `deletealllayersajax=1` | Selected-source fragment | Success or error toast |
| Delete selected table layers | `deleteselectedlayersajax=1` | Selected-source fragment | Success or error toast |
| Change configured-source parent | `updatesourceparentfield=1` + XMLHttpRequest | Source table and, when relevant, selected-source fragment | Success or error toast plus inline validation |

The route allowlist intentionally excludes classic redirect forms for create,
edit, delete, source settings, banner settings and title settings. It also
excludes read-only source-preview and layer-modal loading, draft-file-manager
requests, and client-only toolbar changes. Those routes do not currently have
the complete no-reload response contract required for this lot.

## Accessibility and lifecycle

- Moodle's toast API supplies the temporary notification surface and automatic
  dismissal; the four-second delay is explicit.
- Errors remain visible in the parent-change modal with `role="alert"` and
  `aria-live="assertive"`.
- The initiating control or the parent-change Save control receives focus after
  an error; successful fragment replacement keeps the existing focus contract.
- Existing shared busy locking, confirmation dialogs, server validation,
  sesskey checks and fragment replacement are unchanged.
- No page reload, geometry mutation, transaction change or new focus trap is
  introduced.

## Validation

Run from the plugin root:

```powershell
.\tools\test-ccb-transient-notifications-contract.ps1
node --check amd\src\admin_manage.js
node --check amd\build\admin_manage.min.js
```

The generated AMD file must begin with Moodle's canonical `define` wrapper and
must contain no top-level `import` or `export` declaration.
