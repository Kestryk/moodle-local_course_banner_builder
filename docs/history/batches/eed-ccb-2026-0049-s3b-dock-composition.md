# EED-CCB-2026-0049-S3B - Large-scene and dock composition

Date: 2026-09-03

## Product boundary

This internal slice implements the accepted large-editor wireframe without
changing the classic general preview. Most modal space belongs to the S3A
checkerboard scene; the current source-control panel remains a compact dock.

## Implementation

- Composes the one existing source-preview layout as a flexible scene and a
  bounded 12-16 rem right dock only while the live editor is in the large
  workspace.
- Gives the scene flexible height while retaining the visibility rail,
  filmstrip and Save/Delete row below it in their original DOM order.
- Makes the right dock fill available height and scroll independently with
  stable scrollbar space.
- Freezes every dock action at the accepted control height so container height
  can never stretch a button.
- Recalculates filmstrip overflow navigation after large-editor mount and each
  workspace resize.

## Explicit exclusions

- no classic general-preview or public-renderer change;
- no second editor, moved product control, new payload or server route;
- no final Save/Cancel transaction, Border/Overlay direct property entry,
  extended snapping, persisted zoom/pan or V2;
- no change to S3A frame, Fit, zoom, pan, Crop or selection geometry.

## Validation contract

- official Sass and bounded AMD artifacts must be rebuilt;
- the focused dock contract, every existing PowerShell contract, all Node UI
  tests and PHP/JavaScript syntax must pass;
- managed preview and visible human acceptance remain separate gates.
