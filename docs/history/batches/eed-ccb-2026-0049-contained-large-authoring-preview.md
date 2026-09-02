# EED-CCB-2026-0049 - Large authoring workspace V1

## Scope

Build a desktop-first authoring workspace from the selected source's existing
visual editor. One document controller, one rendered editor and one payload
must serve both the standard and large layouts.

The human accepted the seven V1 decisions: full editing from 1024 px, existing
`-1000..1000` offsets, visible scrollbars plus Space-drag, 25–400% zoom opening
at Fit with a 100% reset, existing snapping plus banner centre/edges, one Image
selection, and an explicit Save/Cancel transaction with no write before Save.

V2 must also let this large space act as a large-screen general preview. That
future preview role does not weaken the V1 single-editor rule.

## Implementation

- Renames the launcher and heading as a large editor.
- Moves the one live `[data-source-visual-editor="1"]` node into the dedicated
  modal body; no `cloneNode()`, duplicate ids or rebinding is used.
- Leaves all current layer selection, drag, resize, Crop, history, filmstrip,
  action and form hooks attached to that same node.
- Restores the node through an exact comment placeholder before focus returns
  on Close, Escape or backdrop dismissal.
- Gates the large mount at 1024 px. Narrower viewports receive a localised
  explanation while the normal editor remains untouched.
- Keeps the existing Save control as the only possible server write. Slice 1
  intentionally adds no automatic write and no new payload.
- Adds the Slice 2 transient view controller only inside the large authoring
  modal: Fit on open, 25–400% zoom, a direct 100% control, native overflow
  scrollbars and Space-drag panning.
- Keeps zoom and pan outside the source payload. Closing removes the viewport
  listeners/observer and restores the untransformed editor through the Slice 1
  placeholder.
- Leaves the classic general preview and the standard editor layout untouched.

## Remaining implementation slices

1. Workspace composition for existing Crop/history/action rail/filmstrip.
2. Shared Border/Overlay property entry points without copied form state.
3. Opening snapshot plus explicit Save/Cancel restoration transaction.
4. Snap expansion to banner centre/edges and the complete accessibility,
   reduced-motion, persistence and public-clipping matrix.

## Validation

- PHP and JavaScript syntax checks.
- Focused Node and PowerShell source contracts.
- A dedicated Slice 2 contract for zoom bounds, Fit/100%, pan precedence,
  cleanup, localisation, generated assets and classic-preview isolation.
- Sass/source and generated-asset consistency.
- Browser, runtime and managed-preview checks intentionally deferred to the
  cumulative preview owner.

## Non-regression boundary

No persistence schema, public renderer, source payload, Crop state, layer
geometry, toast, motion or source-row action is changed. Until the transaction
slice lands, closing the workspace returns the current unsaved draft to the
standard editor; it does not claim final Cancel semantics.
