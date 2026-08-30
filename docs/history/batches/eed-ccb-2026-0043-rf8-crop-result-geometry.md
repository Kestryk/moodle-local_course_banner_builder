# EED-CCB-2026-0043-RF8 — Accepted Crop result geometry

## Reported behaviour

In the Add layer Image modal, accepting a Crop preserved the Crop data but
rendered the result using the pre-Crop outer placement size. The visible Crop
therefore looked correct inside the image while the image appeared to grow
back to its original rectangle. The main source preview already derived its
result geometry from the accepted Crop selection.

## Correction

The modal Crop apply path now uses the accepted Crop selection as the source
of its resulting placement geometry. The source-preview path already used
that mode; no Crop maths, markup, Filemanager lifecycle, Kit primitive or
server data contract changes.

## Evidence before preview

- official AMD build and source map regenerated from source;
- JavaScript syntax checks passed for source and generated bundle;
- PHP syntax passed;
- image-modal transformation, draft-selection, Crop placement and history
  contracts passed; and
- `git diff --check` passed.

## Human review after authorised preview

Crop a non-square image in Add layer Image and accept it. The displayed outer
image rectangle must remain the accepted Crop rectangle rather than expanding
back to its original dimensions. Compare the result with the established main
source-preview Crop behaviour at desktop and a narrow width.

## Boundary

This is a visual geometry correction. It does not run a browser scenario by
default: the previously accepted Fit, Recrop, Undo/Redo and multi-image history
behaviours stay unchanged unless the human comparison reveals a functional
regression.
