# EED-CCB-2026-0043 RF11 - Canonical modal Crop placement

## Problem

RF10 attempted to recompute later modal Recrops from saved relative Crop
percentages. Human validation showed that this still regressed at the second
Crop. The source-preview Crop path did not have that failure because it keeps
one rule: the visible editor selection becomes the next outer placement.

## Correction

The image modal now uses the same direct rule. At every Apply, it reads the
visible crop editor box relative to its frame and writes that rectangle as the
outer custom placement. The crop attributes remain the active editor state and
continue through draft state and the normal final Save path.

There is no first-, second- or third-Crop branch. This removes the stale
coordinate conversion rather than adding another conditional correction.

## Validation boundary

The source contract checks the shared visible-selection invariant and rejects
the removed modal-only Recrop coordinate helpers. Human preview review must
perform at least three successive Recrops on one image, then save and reopen
the result at desktop and narrow width.
