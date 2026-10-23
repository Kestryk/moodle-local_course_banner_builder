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

The title overlay keeps its existing absolute position, padding and font roles.
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
