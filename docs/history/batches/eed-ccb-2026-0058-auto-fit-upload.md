# EED-CCB-2026-0058 - Automatic Fit to preview for new uploads

## Scope

When an image is newly uploaded in the Add image modal, its first draft state
now uses the existing Fit to preview geometry: `cover`, centred placement,
100% custom width and height, and Keep proportions enabled.

The default is assigned only when the file has no stored draft state (or its
stored URL no longer matches). Existing images, reopened modals, user edits,
Crop state, Keep proportions behavior and saved transforms are outside this
change and remain untouched.

## Implementation

- `amd/src/admin_manage.js` changes only
  `localCourseBannerBuilderGetDefaultDraftPreviewState`.
- `amd/build/admin_manage.min.js` and its source map are regenerated from the
  AMD source.
- The focused image-modal static contract now checks the Fit geometry and the
  one-time existing-state guard.

No EasyEdu UI Kit or reusable interaction contract changes are needed: this is
a CCB-owned default-state correction inside the existing upload transaction.

## Validation

Source-only validation for this batch:

```powershell
node --check amd/src/admin_manage.js
node --check amd/build/admin_manage.min.js
.\tools\test-image-modal-transform-contract.ps1
git diff --check
```

No browser, preview, cache purge, fixture mutation or runtime lease is part of
this batch. A later authorized IMG-06 review should upload a new image, verify
the initial Fit state, edit it, trigger a filemanager refresh, and confirm the
edited state remains unchanged before save/reopen.

## Handoff status

Implementation is source-ready and validation is static-only pending the
authorized preview/browser gate. Keep this batch separate from RF17 Crop and
0059 preview-bound selection chrome.
