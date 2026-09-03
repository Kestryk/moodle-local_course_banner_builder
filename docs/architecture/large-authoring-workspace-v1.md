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

The opening snapshot, final Save/Cancel semantics, boundary snapping and
Border/Overlay property composition are intentionally deferred.
Until the transaction slice, a close returns the unsaved draft with the same
node to the standard editor.

## Slice 2: view-only viewport controller

The large shell now wraps the live editor in a transient viewport that owns
only presentation state. It opens in Fit, exposes a bounded 25–400% zoom,
provides a direct 100% control and uses native overflow scrollbars whenever the
scaled stage exceeds the available space. Holding Space before a primary-button
drag pans those scrollbars instead of changing layer coordinates.

The controller scales the same live node. The stage mirrors its scaled width
and height so browser overflow remains authoritative, while existing layer
drag, resize and Crop calculations continue to consume rendered rectangles.
Fit follows modal or editor-size changes until the user chooses a manual zoom.

All listeners, observers, view attributes and the transient shell are removed
before the exact placeholder restoration. The normal source editor and the
classic general preview receive no viewport selector, transform or persisted
zoom/pan value.

Opening snapshot/final Save/Cancel semantics, workspace composition,
boundary snapping and shared Border/Overlay property entry points remain
separate slices.

## Wave 15 viewport correction

The transient controller now keeps its complete control row compact, exposes a
bounded numeric zoom entry and disables only the endpoint action that cannot
move further. Manual zoom remains manual across viewport resize; Fit remains
the only resize-following mode. Empty numeric input restores the current zoom
instead of unexpectedly selecting the minimum.

Space plus primary drag remains the sole pan gesture. A plain primary drag on
the canvas is intentionally left to the existing selection, layer movement and
Crop engine because an empty-region direct-pan contract has not yet received
runtime proof. The selected Image outline is synchronised immediately after
the first Fit paint, and modal close retains the explicit focus-return cue.

## Source contract

- exactly one `[data-source-visual-editor="1"]` root;
- exact placeholder restoration before launcher focus;
- no cloning, duplicated id, listener rebind or new AJAX route;
- large mount only at 1024 px or wider;
- Fit on open, manual zoom bounded to 25–400%, and a direct 100% control;
- Space-drag changes only viewport scroll offsets;
- ordinary canvas drag never starts viewport pan without Space;
- numeric zoom is clamped to the same 25-400% endpoints as range/buttons;
- no viewport transform or state on the classic general preview;
- source and generated AMD/CSS must be rebuilt together;
- managed preview and browser evidence remain separate from static readiness.
