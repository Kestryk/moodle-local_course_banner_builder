# Large authoring workspace V1

Batch: `EED-CCB-2026-0049`

## Accepted product boundary

V1 is a desktop-first editor, available from a CSS viewport width of 1024 px.
The standard editor remains the fallback below that threshold. The document
keeps the existing `-1000..1000` offset contract and public output remains
clipped by the stored banner format.

The accepted interaction target combines visible scrollbars and Space-drag,
25–400% zoom opening at Fit with a 100% reset, current peer snapping plus the
banner centre and edges, and one selected Image layer. Save/Cancel will be one
explicit transaction: no server write before Save and the opening document
state restored on Cancel or Escape.

V2 must additionally expose the general preview in this large-screen space.
That is a distinct presentation mode over the same document, not permission to
create a second editor.

## Slice 1: shell and live mount

The standard editor is already the stateful document controller. Slice 1
therefore portals its one DOM root into the large modal and restores it through
an exact placeholder. Moving the node retains listeners, form ids, history,
selection and draft payload. `cloneNode()` is forbidden for authoring.

The shell reuses the existing modal close/focus lifecycle and keeps the nested
large-editor launcher hidden while mounted. The existing Save action remains
the only server-write entry point. No new persistence or payload path exists.

The opening snapshot, final Save/Cancel semantics, view controller, boundary
snapping and Border/Overlay property composition are intentionally deferred.
Until the transaction slice, a close returns the unsaved draft with the same
node to the standard editor.

## Source contract

- exactly one `[data-source-visual-editor="1"]` root;
- exact placeholder restoration before launcher focus;
- no cloning, duplicated id, listener rebind or new AJAX route;
- large mount only at 1024 px or wider;
- source and generated AMD/CSS must be rebuilt together;
- managed preview and browser evidence remain separate from static readiness.
