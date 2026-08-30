# EED-CCB-2026-0059 - Selection frame for clipped editable images

## Scope

When a selected editable image extends beyond an editable preview canvas, the
selection indication is drawn around all four edges of that preview frame.
This applies to the general/source preview and to the Add image and Edit image
modal previews, which already share the same selection-outline helper.

The image transform, Crop geometry, clipping, pointer hit-testing, Keep
proportions behavior, persistence and public rendering are unchanged. The
indicator remains transient and disappears with the selection or hidden layer.

## Implementation

- `amd/src/admin_manage.js` keeps one `localCourseBannerBuilderSyncPreviewSelectionOutline`
  primitive for source and modal surfaces. It detects overflow from the
  rendered Crop selection bounds and switches only the indicator rectangle to
  the complete preview frame bounds.
- `scss/components/_admin-controls.scss` keeps the indicator border inside its
  frame box with explicit border-box sizing.
- `amd/build/admin_manage.min.js` and its source map are regenerated from the
  AMD source; `styles.css` is rebuilt from SCSS.
- `tools/test-ccb-selection-frame-contract.ps1` verifies shared ownership,
  overflow detection, complete-frame bounds and generated artifacts.

No EasyEdu UI Kit or Platform source changes are needed: this is a CCB-owned
transient preview affordance using the existing local selection primitive.

## Validation

Source-only validation for this batch:

```powershell
node --check amd/src/admin_manage.js
node --check amd/build/admin_manage.min.js
.\tools\test-ccb-selection-frame-contract.ps1
git diff --check
```

No browser, preview, cache purge, fixture mutation or runtime lease is part of
this batch. Human review should cover selected images extending above, below,
left and right in the general preview, Add image modal and Edit image modal,
including keyboard focus and selection clearing.

## Handoff status

Implementation is source-ready and validation is static-only pending the
authorized preview/browser gate.
