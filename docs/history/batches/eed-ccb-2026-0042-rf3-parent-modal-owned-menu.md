# EED-CCB-2026-0042-RF3 - Parent modal owned menu

Status: source candidate pending one scoped validation.
Parent: preview integration `8f2140115ba059d691cf7d478f2e7f91b4d33cef`.

## Evidence

Human review found that the Parent source search list still painted beyond the
opaque Change parent modal. The existing body-level portal fixes the Bootstrap
`aria-hidden` ancestor for the modal itself, but the dropdown was still a
positioned Popper-style surface. Human review also found that this modal's
close control did not visually match the shared EasyEdu modal-close control.

## Correction

The modal remains portalled to `document.body` while open. Its Parent source
list now stays in normal flow below the field, inside the modal's own
scrollable body. Long lists therefore remain reachable without escaping the
modal, the backdrop, or the viewport. The existing close action now declares a
local CCB close class that consumes the embedded shared
`easyedu.modal-close-button` primitive; it preserves the existing Cancel,
Escape, backdrop and focus-return hooks.

## Required validation

Run the existing one-test Parent source modal scenario once against a
disposable three-source chain. It must verify the modal body owns the list,
the searched list is fully within the opaque modal bounds, keyboard/Escape and
backdrop closure still work, and focus returns to the originating pencil at
desktop and a narrow viewport.

### QA harness note

The first authorised RF3 run stopped at Moodle login, before it created any
Parent-modal evidence. The test now uses the same explicit post-login HTTP
navigation wait as the established supervised CCB scenarios. This is a
QA-only reliability correction: it does not alter product behaviour and needs
one newly authorised, non-retry validation run. No user-facing changelog entry
is required for this harness-only correction.

## Allowlist

- `templates/admin_manage.mustache`
- `scss/components/_easyedu-adapter.scss`
- `styles.css`
- `tools/playwright/ccb-parent-source-modal.spec.js`
- `tools/test-parent-source-modal-contract.ps1`
- `docs/testing/functional-protocol.md`
- `docs/history/batches/eed-ccb-2026-0042-rf3-parent-modal-owned-menu.md`
- `docs/history/batch-registry.md`
- `CHANGELOG.md`
