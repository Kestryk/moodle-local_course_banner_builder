# EED-CCB-2026-0043 RF14 - Crop persistence transaction

## Observed failures

Repeated Keep proportion toggles could restore an aspect snapshot without the
five Crop fields. This silently reset an active Crop. Separately, the
standalone/general preview calculated a locked image box from the original
image dimensions while the saved public renderer calculated it from cropped
dimensions, so the two views could disagree after save.

## Correction

Aspect snapshots and their stale-baseline comparison now include the complete
Crop state. The standalone preview uses the same effective cropped dimensions
as the public geometry adapter whenever a proportional placement is needed.
Freeform Crop remains freeform because RF13 disables the aspect lock only for
the committed freeform Crop box.

## Scope and proof

The batch changes Crop-state handoff only: no files, image data, public-banner
format, Fit/Fill command or unrelated modal UI is changed. Static contracts
cover both transitions. Human preview proof must repeatedly toggle Keep
proportion with Crop active, then Apply, Save and reopen; compare the modal
preview and general preview for both cropped and uncropped images.
