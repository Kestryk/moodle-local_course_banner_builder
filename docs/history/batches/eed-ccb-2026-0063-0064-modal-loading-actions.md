# EED-CCB-2026-0063 / EED-CCB-2026-0064 — modal loading and preview actions

Date: 2026-08-31

## Delivered source slice

- Source Preview and image/border/overlay layer-editor loading placeholders
  consume the embedded Kit `native-modal-loading` composition.
- `native-modal-loading` and the persistent bottom-right operation feedback
  now share `busy-indicator-ring`; geometry, colours and rotation are defined
  once in the embedded Kit.
- Save layers, delete all layers and delete selected layers consume the same
  `action-button(small)` state family and the same fixed compact geometry.
- Existing Bootstrap semantic variants, form ids, data hooks, confirmation,
  Ajax locking, disabled controls, fragment replacement and focus return are
  preserved.

## Validation boundary

The official Sass asset and focused source contracts are required for this
candidate. No runtime, browser, cache or managed-preview operation belongs to
this source worktree; visual and lifecycle review remains a separate gate.
