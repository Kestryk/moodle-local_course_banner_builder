# Catalogue thumbnail title anchor

The catalogue uses an actual `img` inside `.courseimage`, whereas dashboard Cards
use a background-image surface. Using the image's arbitrary parent as the title
containing block lets a taller parent centre the title below the image.

The progressive enhancer now wraps managed images in one image-only inline-block.
It retains native image sizing, lazy replacement and the existing link parent.
Critical anchor styles are established before inserting the overlay, including
when Moodle's aggregate CSS is still loading. Background-image Cards retain their
existing path. The title and scrim share the image-only anchor; typography and the
padding-free two-line clamp are unchanged. Repeated scans reuse the wrapper.

## Evidence and limits

Production's night audit measured a 27px painted overflow beneath a 100x45 image.
The local synthetic catalogue's parent naturally measures 45px, so that exact
production height mismatch is not asserted reproduced by the ordinary local course.
The regression fixture explicitly reproduces its essential condition: a 120px
parent with a 45px native catalogue image. Both source and built AMD must anchor
the title exactly to the image, at 1600/768/390 with short and long names.

`tools/test-course-card-catalog-anchor.cjs` tests that case. The existing real
dashboard Mustache fragment test separately retains accessible-label extraction,
font transport and clamp assertions. Set `EASYEDU_PLAYWRIGHT_MODULE` and optionally
`EASYEDU_CHROME_PATH` to installed headless tooling.

Native Moodle 5.3 verification covers short/long course names and a course without
a managed banner at all three widths. Final managed overlay bounds are exactly
100x45px and match the image; long text remains clamped to two lines. Source-only
fixture proof is distinguished from authenticated native catalogue screenshots.
All synthetic courses/accounts/category are removed, plugin configuration is
unchanged, and runtime files are restored. No production deployment is included.
