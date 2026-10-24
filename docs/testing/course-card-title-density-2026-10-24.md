# Smaller course thumbnail titles

Course thumbnail typography uses a density factor of `0.88` after the existing
responsive base and configured course-title percentage. The percentage continues
to apply; it is not replaced by a fixed pixel size. This is a thumbnail-only
adjustment and does not change full-size banner titles.

The title frame's configured padding uses `em`, so its actual padding follows
the smaller font once. Do not multiply that padding by `0.88` a second time.
The outer overlay's gutter uses `rem` and is reduced explicitly by the same
factor. The rounded frame, intrinsic width, centering, image anchor and
padding-free two-line text clamp are unchanged.

## Verification

Compare two real local Moodle catalogue courses, one with a short title and one
with a long title, at 1600, 768 and 390 pixels. Use the parent stylesheet and
candidate stylesheet on the same DOM/configuration for before/after results.
Measure computed font size, frame padding, frame dimensions, center deltas and
the long title's clamped height. The font and frame padding ratios should be
`0.88` within rounding tolerance. The short title's intrinsic frame becomes
smaller; a long title can still fill the available width while getting shorter
vertically. Verify that the clamped child has zero padding and paints at most
two lines, with no fragment of a third line.

Also run the existing real Moodle fragment checks for source and built AMD.
Compare generated CSS declarations: only the thumbnail overlay font sizes and
gutters should differ; full-size banners, frame shape and clamping rules must
remain identical. Restore the local runtime, settings and synthetic data after
the native check. Keep generated screenshots and detailed measurements outside
the repository.

## Result for this change

The real Moodle Card fragment passed 12 before/after renders (short and long
titles at all three viewport widths), with zero JavaScript errors. At 1600px
and a configured title size of 144.7%, the font measured 21.9944px before and
19.3551px after. Short-title frame size changed from 87.25 × 56.02px to
77.5 × 50px. Font and frame-padding ratios were 0.88; the long title retained
its padding-free two-line clamp. The existing source/built-AMD checks passed
all eight cases. Only the two thumbnail overlay rules changed in generated CSS.

The authenticated local catalogue attempt did not produce a usable thumbnail
title before its timeout and is not counted as a native-page pass. Its synthetic
courses/account and configuration were cleaned up, and the runtime was restored.
The measurements above are explicitly fragment-rendering evidence, not a claim
that the complete Moodle catalogue or remote sites were verified in this run.
