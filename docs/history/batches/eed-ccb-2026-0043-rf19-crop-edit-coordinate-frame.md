# EED-CCB-2026-0043 RF19 - Stable Crop edit coordinates

## Observed failure

After moving the bottom Crop edge upward, toggling **Keep proportions** could
leave the image placement correct while drawing the Crop selection below the
image.

## Root cause and correction

The active Crop editor expands its DOM layer to the complete source image so
its percentages have one stable coordinate system. A draft/keep-proportion
refresh passed that same layer through the normal placed-image renderer while
Crop was still open. The renderer therefore replaced the source canvas with
the outer placement and the existing Crop percentages were effectively
applied in a second coordinate system.

The standalone renderer now preserves an active source-coordinate Crop canvas.
It refreshes the selection and controls from the unchanged Crop state, while
the independent draft visual layer continues to paint the live placed image.

## Preserved boundaries

- all four edge handles remain unidirectional;
- corner resizing, repeated Recrop, Undo/Redo and Apply/Cancel are unchanged;
- Keep proportions and saved placement remain the existing sources of truth;
- no Filemanager, server save or public-banner code changes;
- no browser, fixture, cache or runtime execution belongs to this source RF.

## Source validation

```powershell
node --check amd/src/admin_manage.js
node --check amd/build/admin_manage.min.js
.\tools\test-ccb-crop-edit-coordinate-contract.ps1
.\tools\test-ccb-crop-aspect-selection-rf-contract.ps1
.\tools\test-ccb-crop-history-contract.ps1
git diff --check
```
