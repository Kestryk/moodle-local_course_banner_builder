# EED-CCB-2026-0043 RF15 - Crop frame and saved source-preview geometry

## Observed failures

RF14 retained the Crop data through repeated Keep proportion toggles, but the
active Crop frame could remain visually measured against the preceding preview
geometry. Separately, the direct selected-source preview rebuilt saved layers
with the default 4:1 aspect ratio even when the configured public banner used
another format. This affected placement for cropped and uncropped layers.

## Correction

While Crop stays active, a proportion toggle now refreshes the existing Crop
box and action rail from the unchanged Crop state. The selected-source exporter
now supplies its actual public banner aspect to the common preview-layer
exporter, matching the frame rendered on the page and the public geometry
contract.

## Scope and proof

The batch changes only Crop selection remeasurement and saved source-preview
geometry. It does not alter the crop rectangle, file data, public-banner
format, or unrelated modal controls. Static contracts prove both handoffs.
Human review must crop, toggle Keep proportion, then save and reopen both a
cropped and uncropped image, including a non-standard banner format.
