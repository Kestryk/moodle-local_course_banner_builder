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

## RF1 - 2026-09-01

Wave 10 human review accepted Source Preview loading, fixed action height and
the general states, but exposed two final-cascade failures:

- Save alone still received a later regular-size rule, leaving Delete at
  0.78 rem and Save at 0.88 rem. All three controls now carry one dedicated
  compact class with identical final font, weight and line-height; Delete uses
  the accepted Save-style light hover/focus/pressed treatment.
- The Image/Border/Overlay loading primitive was applied to a legacy wrapper
  with `flex: 1`, stretching the ring. The wrapper now only centres the real
  nested `.spinner-border`, whose bounded shared ring owns geometry. The modal
  now sets and clears `is-loading` and `aria-busy` through success, error and
  close teardown.

The RF1 contract rejects the retired flex-growing ring, verifies all three
common action classes and checks the generated AMD lifecycle.

## RF2 - 2026-09-02

Both selected-source action families now finish on the same 0.78 rem type,
1.2 line-height and fixed 2.15 rem geometry. Destructive actions reuse the
accepted Save-style light interaction state, while row Edit and Delete retain
pointer cursors. After the accepted Image/Border/Overlay loader is removed,
the loaded body receives one 0.24 second shared content reveal; reduced-motion
users receive the content immediately without animation.
