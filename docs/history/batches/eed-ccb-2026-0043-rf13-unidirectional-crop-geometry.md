# EED-CCB-2026-0043 RF13 - Unidirectional Crop geometry

## Human correction

RF12 made top and bottom handles preserve an aspect ratio by changing width
and recentering the selection. That removed the deliberate one-edge Crop
interaction. The requested behavior is different: every edge handle keeps the
opposite dimension and border stable while the chosen edge alone moves.

## Correction

RF13 restores the existing one-edge pointer path. On Apply, the raw visible
box is written as the custom placement and temporarily bypasses the placement
aspect lock. The image therefore keeps the exact freeform Crop geometry that
was visible before Apply, without a later resize or deformation.

## Scope and proof

This is restricted to Crop edge-handle geometry and its Apply conversion. It
does not change corner Crop, Fit, Fill, source selection, undo/redo, files or
the public banner. Static contracts cover both the unidirectional pointer
path and the freeform Apply path. Human preview proof must exercise all four
edge handles, then Apply, Save and reopen at desktop and narrow widths.
