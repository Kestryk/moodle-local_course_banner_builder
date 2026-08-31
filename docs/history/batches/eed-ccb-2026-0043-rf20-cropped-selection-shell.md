# EED-CCB-2026-0043 RF20 - Cropped Keep proportions selection shell

## Observed failure

After moving the bottom Crop edge upward, applying **Keep proportions** kept
the image pixels cropped correctly but the blue selection frame returned to the
pre-Crop extent.

## Root cause and correction

The modal placed renderer used complete-source dimensions while the independent
draft visual used effective cropped dimensions. Keep-proportion refreshes could
therefore give the selection shell a different aspect from the pixels it
outlined. Both placed render paths now resolve the effective cropped dimensions.
When Crop editing is active, both paths still return before placed geometry is
calculated so the editor remains on its stable complete-source coordinate
canvas.

## Preserved boundaries

- bottom-up and all other one-edge Crop interactions remain unidirectional;
- active Crop coordinates, repeated Recrop, Undo/Redo and Apply/Cancel remain
  unchanged;
- only `amd/src/admin_manage.js`, its generated AMD bundle/map, contracts,
  changelog and batch registry documentation are in scope;
- no browser, runtime, fixture, cache or preview activity was performed.

## Source validation

```powershell
node --check amd/src/admin_manage.js
node --check amd/build/admin_manage.min.js
.\tools\test-ccb-crop-selection-shell-rf20-contract.ps1
.\tools\test-ccb-crop-placement-contract.ps1
.\tools\test-ccb-crop-edit-coordinate-contract.ps1
.\tools\test-ccb-crop-aspect-selection-rf-contract.ps1
.\tools\test-ccb-crop-history-contract.ps1
git diff --check
```
