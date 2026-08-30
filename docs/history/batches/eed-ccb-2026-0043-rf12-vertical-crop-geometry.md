# EED-CCB-2026-0043 RF12 - Vertical Crop geometry

## Problem

The modal Crop result is stable through corner handles, but human review found
that top and bottom handles could show a free-height editor box and then
reshape the image when the Crop was applied.

## Correction

Vertical handles now run through the same aspect-constraining geometry as
corner handles before the Crop state is rendered. The selection the learner
sees is therefore the selection used by the canonical Apply path; Apply does
not need to reshape it afterwards.

## Validation boundary

The contract checks the top/bottom constraint and the absence of the old early
return. A human preview review must Crop top-to-bottom and bottom-to-top, then
Save and reopen, at desktop and narrow width.
