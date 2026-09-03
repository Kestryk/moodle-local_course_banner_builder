# EED-CCB-2026-0049-S3A - Elastic grid plane

Date: 2026-09-03

## Product boundary

This slice makes the large editor materially larger than the published banner
without changing the classic general preview. It remains a view and workspace
composition change over the one existing live source editor.

## Implementation

- Moves the one real `[data-source-preview-frame="1"]` through an exact comment
  placeholder into a CSS-only checkerboard plane; no clone or second document
  state is introduced.
- Draws and localises the published-area boundary while permitting existing
  layers to remain visible outside that boundary.
- Sizes the logical plane from the published frame and current layer extents,
  with one-banner working margins and hard limits matching the persisted
  `-1000..1000` offsets.
- Applies 25-400% zoom only to the plane. Toolbar, mode controls and the source
  property panel remain unscaled. Fit measures and centres the published frame.
- Lets a primary drag pan only on proven empty plane/stage targets. Space-drag
  keeps its broader viewport behavior, so layer, resize, selection and Crop
  gestures continue to own their targets.
- Restores the frame and editor through their exact placeholders and restores
  the viewport attributes that existed before the mount.

## Explicit exclusions

- no change to the classic general preview or public renderer;
- no new payload, route, capability, sesskey or server write;
- no final Save/Cancel snapshot transaction;
- no Border/Overlay property-panel recomposition or new snap targets;
- no V2 public-preview mode inside the large editor.

## Validation contract

- official Sass and bounded AMD builds must be regenerated together;
- PHP/JavaScript syntax, all plugin PowerShell contracts and all Node UI
  contracts must pass;
- managed preview and human visual/interaction acceptance remain separate from
  source readiness.
