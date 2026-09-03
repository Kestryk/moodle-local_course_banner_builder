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

## Slice 3A: elastic grid plane and published frame

The large authoring viewport now owns a CSS-only checkerboard plane around the
one live published banner frame. The frame is moved through its own exact
placeholder inside the already-portalled editor; it is never cloned and is
restored before the editor itself returns to the standard page position. A
localised **Published area** label and outline make the public clipping boundary
explicit while layers using the existing `-1000..1000` offset contract remain
visible outside it.

Zoom is applied only to the logical plane. The modal toolbar, source controls
and property panel keep their normal dimensions, and Fit targets and centres
the published frame rather than the complete editor panel. The plane grows to
cover the current layer extents plus one banner of working margin, capped by
the persisted offset contract; its grid is painted with CSS gradients rather
than repeated DOM nodes.

A primary-button drag on proven empty grid pans the viewport directly. Space
plus drag continues to pan from anywhere in the viewport, preserving layer,
selection, resize and Crop pointer ownership. These changes exist only under
the large-authoring modal selector; the classic general preview remains
unchanged.

## Slice 3B: large-scene and dock composition

The accepted workspace wireframe is expressed with the existing editor nodes:
the transient Fit/zoom toolbar stays fixed at the top, the flexible majority
track belongs to the checkerboard viewport, and the established source-control
panel forms a bounded right dock. The dock remains outside the plane transform,
uses the complete available height and scrolls vertically without moving the
scene. Its buttons retain their fixed control height instead of stretching with
the dock.

Inside the left track, the viewport receives the flexible height. The existing
visibility/action rail, horizontal filmstrip and Save/Delete row remain below
it in their current DOM order and keep their accepted typography, Motion and
business hooks. Filmstrip overflow controls are recalculated after the live
editor enters the modal and whenever its workspace geometry changes.

This slice uses CSS composition around the same
`[data-source-visual-editor="1"]`; it introduces no secondary dock state and
moves no additional product control out of that root. The classic general
preview, server payload, final Save/Cancel transaction, Border/Overlay direct
properties, new snaps and V2 remain outside the slice.

## Source contract

- exactly one `[data-source-visual-editor="1"]` root;
- exact placeholder restoration before launcher focus;
- no cloning, duplicated id, listener rebind or new AJAX route;
- large mount only at 1024 px or wider;
- Fit on open, manual zoom bounded to 25–400%, and a direct 100% control;
- Space-drag changes only viewport scroll offsets;
- a plain primary drag pans only when its target is the empty plane or stage;
- layer, selection, resize and Crop targets never start direct plane pan;
- the flexible scene and independently scrolling right dock remain descendants
  of the one live editor;
- dock buttons retain their fixed height and the filmstrip recalculates after
  mount and workspace resize;
- numeric zoom is clamped to the same 25-400% endpoints as range/buttons;
- no viewport transform or state on the classic general preview;
- source and generated AMD/CSS must be rebuilt together;
- managed preview and browser evidence remain separate from static readiness.

## Wave 17 RF2 render correction

The first human review exposed a runtime geometry collapse: the moved banner
could remain as a tiny top-left fragment while the plane and published boundary
were not visibly usable. RF2 now derives desktop frame height from the
authoritative banner-format ratio, materialises frame and plane geometry on the
transient nodes, and centres a scaled plane whenever it is smaller than the
viewport. The original inline frame style is captured before the move and
restored exactly on close.

This correction does not change stored layer coordinates, Crop data, public
rendering, the classic general preview or the one-live-editor rule. Focus is
returned immediately after modal teardown rather than after a second visual
delay. Browser acceptance remains required because source contracts cannot
prove the final painted geometry or focus indicator.

## Wave 18 action-toolbar correction

The large shell relocates only the existing primary Save/Delete action group
beside Fit and zoom while it is open. A placeholder restores that exact DOM
node on every close path, so listeners, disabled states and the classic editor
layout are neither duplicated nor rewritten. The nested `Source visual
editor` heading is hidden only under the large-workspace marker because the
modal header already names the surface.

The persistent pan instruction is removed from the toolbar; the viewport's
accessible label and the documented direct-grid and Space-drag gestures remain
authoritative. Outside the large workspace, the general-preview actions regain
their roomier shared height. Genuine Edit/Delete descendants of draggable
layer rows keep a pointer cursor instead of inheriting the row drag affordance.
