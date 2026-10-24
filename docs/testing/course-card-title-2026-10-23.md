# Native course-card title extraction and clipping

Moodle's overview card does not have a plain-text `.coursename` node. The
rendered `block_myoverview/view-cards` template extends `core_course/coursecard`
with a complete screen-reader name and a shortened visual name. The link also
contains the favourite icon's accessible text and a course-name label. Reading
all its `textContent` paints those labels and repeats the name.

Use `.coursename .multiline[title]` as the complete name when present. Do not
use a generic `[title]` descendant: the star has its own title. Other card
layouts fall back to a cloned name node after removing explicit accessible
labels (`visually-hidden` and legacy `sr-only`), hidden nodes, favourite-icon
content and icon markup. Preserve the visible `aria-hidden="true"` name span:
it is visible text, even though the adjacent complete name serves assistive
technology. Never mutate the native card's accessible markup.

The title overlay keeps its padding and compact font scale. K23-32 bis centers
the padding-free text box on both axes inside the thumbnail, replacing the
previous bottom strip. Configured alignment still controls text alignment.
A padding-free `course-card-title__text` child owns the two-line clamp; padding
on the clamped node can expose part of a third line in Chromium. The complete
name stays in its text content; CSS clips only its visual presentation. Plain
course images and disabled title settings keep their previous behavior.

## Regression test

`tests/fixtures/coursecard-moodle53.html` is a synthetic card rendered by
Moodle 5.3's actual `block_myoverview/view-cards` template, including the nested
coursecard, favourite icon, hidden full name, visible shortened name, and native
action menu. It is intentionally not a simplified DOM fixture. No real user,
session or course record is included. Its full-name test includes punctuation,
an ampersand and a typographic apostrophe.

Run `tools/test-course-card-title-native-fragment.cjs` with an installed
Playwright module through `EASYEDU_PLAYWRIGHT_MODULE` and optionally a Chrome
executable through `EASYEDU_CHROME_PATH`. Chromium runs headless, blocks external
requests, and executes both the source AMD and the actual generated AMD file.
Eight cases cover full-name extraction, a cleaned fallback without a title
attribute, unmanaged images, disabled titles, rescan idempotence and a padding-free
text box holding at most two lines. This is a component regression check,
separate from authenticated native Moodle acceptance.

For native acceptance use isolated short/long/no-banner courses at 1600, 768 and
390 pixels on Moodle 5.3. Await real rendered cards instead of overview skeletons;
dismiss any native first-visit tour before screenshots. Assert exact full-name
text, image decode/non-white content, no title on the unmanaged control, no
horizontal overflow and no console/page errors. Record the child text box's
padding, line height and height as well as the painted screenshot. Restore the
runtime with a compare-before-write guard and remove only the owned fixtures.

Typography transport uses raw CSS from the existing strict font-family allowlist.
Do not HTML-escape it before JSON/DOM assignment, or before passing the whole
attribute to html_writer: quoted font names otherwise become literal `&quot;`
tokens and the browser rejects the font-family declaration. HTML output remains
escaped once by html_writer. The compact non-bold weight is 500, matching the
native banner; bold remains 800. Native banner font transport receives the same
single-escape correction. No new font input or permissive CSS parser is added.

`tools/test-course-card-title-style.php` checks quoted-family transport, both
weights/capitalization settings, invalid-family rejection and disabled titles.
The native-fragment test also checks actual text-box centering to 0.5px, font,
weight and colour for both shipped and source AMD. No runtime AMD changes are
needed for K23-32 bis.

The portable authenticated candidate is
`tools/playwright/candidates/ccb-course-card-title-native.cjs`. Set absolute
`EASYEDU_TEST_OUTPUT` and `EASYEDU_TEST_FIXTURE` paths, an owned
`EASYEDU_TEST_BASE_URL` and the same Playwright/Chrome variables as above.
Keep the private fixture (username/password and short/long/plain course IDs)
outside the repository. On a freshly purged Boost theme, the first stylesheet
request may receive Moodle's previous fallback CSS while the new CSS compiles
(`theme/styles.php`). Retain that first-run failure and verify the actual new
child's computed clamp after generation in a fresh browser context. Do not
silently inject a replacement stylesheet to certify a native deployment.

Build the existing SCSS entry with Sass 1.89.2 and rebuild the named
`local_course_banner_builder/coursecards` AMD module and source map. No PHP,
Moodle core, banner-generation or cache policy change belongs to this fix.

## Acceptance result

On Moodle 5.3 the authenticated short/long pairs pass at 1600, 768 and 390px
(six title cases), alongside three unmanaged-image controls. The entire overlay
text equals the complete expected course name, with no accessible-label prefix
or duplicate. Only the long visual presentation is elided by CSS. The text
child has zero padding and clamp 2: the long frame is 34.9375px at 1600
(17.48px line height), and 26.46875px at 768/390 (13.248px line height).
Raster inspection confirms no third-line fragment. Image decode and native
no-banner fallback pass, with zero document overflow or JS/console errors.

Eight fragment cases pass for source and built AMD; the same test fails on
the predecessor at its first full-name assertion. The 14 historical thumbnail
contracts pass. ESLint and syntax checks pass; PHP codechecker is not relevant
because no PHP source was changed. Keep the first theme-fallback CSS run
separate from the final compiled-CSS acceptance. Runtime restoration and fixture
cleanup evidence live outside Git in the native review report.

## K23-32 bis — centered and configured title, 24 October

Authenticated Moodle 5.1 acceptance covers short/long owned courses at
1600/1024/768/390px with two configured styles: Trebuchet MS / #123456 / weight
500 / normal case, then Courier New / #9a315f / weight 800 / italic / uppercase.
All sixteen title cases match the full native banner's computed typography.
Maximum text-box center displacement drops from 43.7890625px to 0.0078125px.
Native image URLs and decoded pixel summaries remain identical to the baseline.
Long titles retain their two-line clipped frame; intrinsic scroll width is not
a measure of painted overflow when text is intentionally clamped.

The first stylesheet request after purge served the previous theme fallback.
That run is retained separately; acceptance waits for the actual flex overlay
from the newly compiled stylesheet in a fresh browser context. No CSS is
injected into the acceptance pages. All 2,798 CSS rules outside this title family
are unchanged. Native console/page errors: zero. Source/built AMD fragment
contracts: 8/8; thumbnail contracts: 14/14; quoted-family PHP contract: PASS.

The runtime CCB files, touched settings and preferences are restored after the
test, and both temporary courses are deleted. No remote deployment is included.
Detailed evidence is outside Git in the review report
`RAPPORT-K23-44-K23-32BIS-20261024.md`.

## Thumbnail shadow policy (K23-70, 24/10/2026)

The text shadow is removed from the full-size course banner title only
(`.local-course-banner-builder-banner-title-overlay`, course page), where it
was unconfigured and unwanted. Course-card thumbnail titles
(`.local-course-banner-builder-course-card-title`, catalogue/dashboard/My
courses) keep their shadow — it preserves legibility of the title over the
gradient card image. Verified headless at 1600px: computed `text-shadow` is
`none` on the full banner title and a configured value (not `none`) on the
thumbnail title; `tools/test-course-card-title-native-fragment.cjs` asserts
the thumbnail side.
