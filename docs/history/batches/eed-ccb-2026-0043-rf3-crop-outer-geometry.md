# EED-CCB-2026-0043-RF3 — Crop outer-geometry preservation

Status: source-ready pending static validation.
Base: `1c5104b244aa32205949379792c16a3e7c0db03a` (clean CCB preview equivalent).

## Evidence

The one-time QA2 run `ccb-crop-recrop-20260829T171717154Z-50960` reached its
first product assertion at 1440 px. It recorded the active and visual image at
71.25 × 18.9375 px before Crop, then 0.703125 × 0.1875 px after Crop, while all
saved placement fields remained `original`, 100%, centred and offset 0. The
crop payload was correctly changed to 1% by the deliberately narrow gesture.

The render paths multiplied natural dimensions by the Crop percentage before
calculating the outer image box, then applied the Crop transform to the inner
image too. This made Crop shrink the whole visible image.

## Correction

Both add-image modal render paths now calculate the outer box from natural
image dimensions. Existing `localCourseBannerBuilderApplyCropToImageStyles()`
continues to enlarge and translate only the image inside that unchanged box.
The source-preview aspect helper remains untouched: it may still use Crop
aspect solely to draw the editor selection, never to choose modal placement.

## Required Platform allowlist

- `amd/src/admin_manage.js`
- `amd/build/admin_manage.min.js`
- `amd/build/admin_manage.min.js.map`
- `tools/test-ccb-crop-placement-contract.ps1`
- `docs/history/batches/eed-ccb-2026-0043-rf3-crop-outer-geometry.md`
- `docs/testing/ccb-ui-harmonisation.md`
- `docs/history/batch-registry.md`
- `CHANGELOG.md`
