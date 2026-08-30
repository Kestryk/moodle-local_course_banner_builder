# EED-CCB-2026-0043 RF17 - Direction-independent Crop proportions

## Observed failure

Keep proportions could corrupt the visible Crop frame after an edge was
dragged through its opposite edge, notably bottom-to-top or left-to-right.
The pointer state briefly contained a negative width or height; the generic
clamp reduced that dimension to one percent without moving the origin to the
new leading edge.

## Correction

The shared Crop-state normaliser first converts signed rectangles to canonical
top/left plus positive width/height geometry. Existing bounds, unidirectional
edge handles, Crop persistence and public rendering remain unchanged.

## Scope and proof

RF17 changes only Crop rectangle normalisation and its focused static contract.
It does not implement automatic Fit or the preview-bound selection treatment,
which are separately registered as EED-CCB-2026-0058 and 0059.
