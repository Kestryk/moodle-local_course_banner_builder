# EED-CCB-2026-0043-RF9 — Modal Recrop accepted geometry

## Reported behaviour

RF8 correctly retained the outer geometry selected by a first Crop in the Add
layer Image modal. A second Crop of that same image could still calculate its
result against the reconstituted original-image frame. Reducing that second
selection could therefore change the accepted image's apparent size or aspect.

## Correction

When a Recrop stays inside an already accepted Crop, the modal now composes the
new selection with that accepted outer geometry: relative size and offset are
calculated from the prior accepted rectangle. A first Crop, and a Recrop that
deliberately reframes outside its previous selection, retain the existing
absolute-editor calculation.

Only the Add layer Image modal apply path changes. Source-preview Crop maths,
Filemanager lifecycle, the Kit, markup and unrelated preview/title behaviour
are out of scope.

## Evidence before preview

- AMD bundle and source map regenerated from `amd/src`;
- JavaScript source and generated bundle syntax checked;
- PHP syntax checked;
- image-modal transformation, draft-selection, Crop placement and history
  contracts passed; and
- `git diff --check` passed.

## Human review after authorised preview

Crop an image and apply it. Reopen Crop on the same image, reduce the existing
selection, then apply. The result must retain the newly reduced accepted
rectangle with its expected aspect and position. Reopening Recrop, Cancel,
Fit, Undo/Redo and switching between existing draft images must retain their
previously accepted behaviour.
