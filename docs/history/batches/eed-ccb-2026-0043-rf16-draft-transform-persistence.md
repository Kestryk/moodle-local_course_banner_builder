# EED-CCB-2026-0043 RF16 - Draft image transform persistence

## Observed failure

The image modal keeps a stable browser index for every draft so Crop history
survives switching or removing images. Moodle reads its stored draft files at
submit time and can compact that list. The two index spaces can diverge,
leaving a saved image with another image’s position/proportions or defaults.

## Correction

Each per-image draft transform now carries the stored filename. Save resolves
the settings against that filename first for both a single image and multiple
images, and retains the former numeric mapping only for old payloads that lack
the filename.

## Scope and proof

No Crop geometry, image data, file lifecycle or public renderer is changed.
The focused static contract checks the browser-to-server identity handoff.
Human review must save/reopen a cropped and an uncropped image after changing
the multi-image list, and compare their general-preview placement/dimensions.
