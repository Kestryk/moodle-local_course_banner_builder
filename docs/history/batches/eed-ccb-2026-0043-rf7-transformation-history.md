# EED-CCB-2026-0043-RF7 — multi-image transformation history

## Scope

RF7 repairs the Add-layer Image modal history for images already present in its
Filemanager. It records reversible visual transformations across image changes:

- Crop/Recrop and accepted Crop coordinates;
- Fit and Fill;
- placement, custom size and aspect choice;
- opacity, image ordering/options; and
- the active draft-image selection.

`Fit to preview` preserves a previously accepted Crop. Undo and Redo restore
the complete selected-image state at each chronological boundary instead of
only the last active form values.

## Explicit boundary

This batch does not implement Filemanager Add/Delete Undo/Redo. Moodle's
current draft-file delete action is physically destructive and offers no safe
restore API. A file added after a snapshot remains present, and a deleted file
is not recreated by history restoration. That lifecycle is reserved for
`EED-CCB-2026-0056`.

## Implementation

- `amd/src/admin_manage.js` stores versioned `draft-transformations` snapshots
  with the active draft index and the reversible state of every existing draft.
- Snapshot restoration merges only into available non-deleted draft indexes and
  rerenders the restored active image.
- The event boundary is captured before keyboard/pointer changes to image
  controls, crop handles, sliders, options and image selection.
- The existing pointer-release isolation remains unchanged.

## Required validation

Static source checks must pass before browser work:

- `tools/test-image-modal-transform-contract.ps1`;
- `tools/test-image-modal-draft-selection-contract.ps1`;
- `tools/test-ccb-crop-placement-contract.ps1`; and
- `tools/test-ccb-crop-history-contract.ps1`.

Rebuild the generated AMD from its source with `includeSources` enabled in the
source map. The placement and selector contracts use that map to verify the
official minified output without assuming that Terser keeps local function
names in the bundle.

The dedicated one-test `CROP-08` runner first discovers exactly one test, then
creates a disposable source with one edit-image layer containing exactly two
existing images. It opens that layer through its normal edit route; it must not
use Filepicker to add/delete during assertions. Cleanup removes both the
temporary modal draft and the disposable source. It must capture desktop and
narrow states before/after Crop, Fit/Fill, image switching and each Undo/Redo
step. Human review must confirm that Crop stays visually constrained, Fit does
not remove Crop, and restoration moves chronologically across both images.

## Out of scope

No PHP, Mustache, Kit, Filepicker deletion, server API, preview, cache, lease
or browser change belongs to this source candidate.
