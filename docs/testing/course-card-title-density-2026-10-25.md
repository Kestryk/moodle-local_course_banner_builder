# Course thumbnail density: proportional text and frame reduction

The thumbnail title now uses a further density factor of `0.72` relative to
its previous `0.88` density: the cumulative factor is `0.6336`. The configured
course-title percentage still multiplies the responsive thumbnail font. This
change is scoped to course thumbnails, including the catalogue and Cards;
full-size banner titles retain their existing settings and CSS.

Frame padding and radius already use `em`, so they follow the smaller font
once. Do not multiply them again. Shrink the available text/frame width by the
same factor too: each horizontal overlay gutter is the previous gutter times
`0.72`, plus `14%` of the image width. The remaining content width is exactly
`72%` of its previous value. This prevents a long title's pill from growing
into the space released by smaller padding. Short titles still hug their text.

Keep the centered rounded frame, image-only containing block, unchanged
foreground/background colours and padding-free two-line text clamp. A large
configured title size still applies; this is not a fixed pixel-size override.

## Verification

Use three controlled courses with short, medium and long names on the actual
Moodle catalogue, at 1600, 768 and 390 pixels. Compare the parent and candidate
stylesheets on the same native DOM and course-title settings. Record computed
font, frame padding, dimensions and center deltas. All font/padding ratios
must be `0.72`, every pill must become smaller, and frame dimensions should
scale proportionally within browser rounding. The short name stays on one
line; the long name must genuinely exceed its two-line clamp, with no third
line visible and no padding on the clamped child.

Also compare every compiled CSS rule to the parent: only the two thumbnail
overlay font-size/padding rules may change. Run the existing source/built-AMD
real-Moodle-fragment checks. Keep all media and detailed measurements outside
Git; restore settings, site-admin membership and temporary courses/accounts.

A 100px native catalogue thumbnail with a title percentage of 150% is a
controlled local reference, not a promise of identical dimensions to another
site with different theme typography or settings. Report actual native values
rather than substituting a previously observed remote measurement.

## Verified local result (2026-10-25)

Authenticated Moodle 5.1 catalogue: all nine before/after pairs passed
(three course-name lengths at 1600/768/390). At a configured size of 150%,
font size changed from 16.896 to 12.1651px on desktop and 13.0944 to
9.42797px at 768/390. The short-title pill changed from 58.84 x 38.42 to
42.34 x 27.64px on desktop; the long-title pill changed from 88.75 x 57.84
to 63.91 x 41.63px. Maximum center deviation was below 0.01px.

All font/frame-padding ratios were 0.72 within rounding. Every pill became
narrower and shorter. Short titles retained one line; long titles were
truncated at two lines on an unpadded text node. All nine final frames fit
inside their image overlay. Screenshots were inspected at desktop and mobile.
There were no JavaScript errors or HTTP error responses during the matrix.

All eight existing real-Moodle-fragment checks passed for source and built
AMD. A full compiled-rule comparison found only the two thumbnail overlay
font-size/padding rules changed; full-size banner CSS was identical. No PHP
or JavaScript product file changed and no AMD rebuild was necessary.

Settings and site-admin membership were restored exactly, with zero synthetic
courses or active accounts remaining. No runtime source file was deployed or
modified: only the candidate stylesheet was applied in the isolated browser.
Remote deployment and human acceptance remain separate from this local proof.
