# EED-CCB-2026-0043 RF18 - Contained Crop aspect toggle

## Observed failure

After accepting a vertical or horizontal one-edge Crop, toggling Keep
proportions or Allow stretch could draw the selection outside the visible
image. Repeated Recrop and persistence were already accepted; this RF does not
replace those coordinate systems.

## Root cause and correction

The aspect helper compared two candidates by absolute delta. In a tie it could
choose the candidate that enlarged the other axis beyond the accepted Crop
rectangle. The helper now performs a contained aspect fit: it keeps the full
width only when the proportional height fits, otherwise it keeps the full
height and reduces the width. Existing callers retain the same centre.

The local selected-layer pseudo-border also uses explicit border-box sizing so
its stroke cannot paint beyond the image box under a Moodle theme cascade.

## Preserved boundaries

- one-edge handles remain unidirectional;
- signed reverse-direction Crop normalisation remains unchanged;
- Crop percentages, repeated Recrop, Undo/Redo and draft persistence remain
  the existing source of truth;
- no Filemanager, server save or public banner code changes;
- no preview, browser, fixture, cache or runtime work in this source batch.

## Source validation

```powershell
node --check amd/src/admin_manage.js
node --check amd/build/admin_manage.min.js
.\tools\test-ccb-crop-aspect-selection-rf-contract.ps1
.\tools\test-ccb-crop-history-contract.ps1
git diff --check
```
