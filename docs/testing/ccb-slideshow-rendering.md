# CCB Slideshow rendering audit

## Purpose

`EED-CCB-2026-0002` prepares a reproducible visual audit of the Course Banner
Builder Slideshow administration and public rendering surfaces. The initial
scenario is deliberately a **preflight**, not the complete responsive matrix.

## Owned test sources

- `tools/playwright/ccb-slideshow-rendering-fixture.php`
- `tools/playwright/ccb-slideshow-rendering.spec.js`
- `tools/playwright/Invoke-CCBSlideshowRenderingValidation.ps1`
- `tools/playwright/ccb-slideshow-public-rendering-fixture.php`
- `tools/playwright/ccb-slideshow-public-rendering.spec.js`
- `tools/playwright/Invoke-CCBSlideshowPublicRenderingValidation.ps1`

The administration-preview fixture creates one hidden temporary course, records
all existing plugin configuration entries beginning with `slideshow_course_` or
`slideshow_site_`, applies a contrasting valid profile, then restores exactly
those records and removes the course in the runner's `finally` block. It never
uses course 2, an EasyStud fixture, a Sources/Layers fixture, or a browser
profile inside Git.

## Required process-local configuration

The supervised runner intentionally accepts machine-dependent paths only from
the environment; no local root or secret is versioned.

- `EASYEDU_PLATFORM_ROOT`: checkout containing the orchestration module.
- `EASYEDU_MOODLE_ROOT`: active Moodle 5.1 root containing `config.php`.
- `EASYEDU_CCB_PLAYWRIGHT_ROOT`: optional node-module root when it differs
  from this clean test worktree.
- `EASYEDU_CCB_CREDENTIAL_LOADER`: DPAPI-backed credential loader path.

The runner generates its own temporary Playwright configuration inside the
external run directory. This keeps its isolated worktree runnable without
depending on a configuration file from the active CCB checkout.

Credentials are loaded only into the runner process and removed before it
exits. Raw captures, profiles and logs are written beneath
`%LOCALAPPDATA%\EasyEdu\artifacts\ccb\slideshow\supervised` unless an approved
external artifact root is supplied.

The runner waits for the shared Moodle 5.1 fixture lease for up to ten minutes
by default. It never kills another window's test process or reclaims a lease
from a live owner.

## Rendering-matrix assertions

The one selected test confirms that the Course and Site cards, their visual
previews, overlay settings and configured limit fields render together. It
walks the desktop (1600 px), tablet (768 px) and mobile (390 px) viewports,
captures an external CDP image for each, and proves that the labels, title,
body and action remain visible, non-empty and inside each preview on all four
edges. It also
records the two contexts and the nine side-panel option groups per context.

This is an administration-preview matrix. It does not claim public-runtime
coverage with live Forum, Assignment, Quiz or Site-announcement slides: the
disposable fixture deliberately does not create those Moodle activities. A
later public-rendering fixture must create and clean up those sources before
that claim can be made.

## Prepared public-course fixture

`ccb-slideshow-public-rendering-fixture.php` is the separate source fixture
for the future Course public-rendering gate. It has not been executed by this
administration-preview batch. Under the Moodle 5.1 fixture lease, its `setup`
command will create one hidden disposable course, retain its native
Announcements forum, add one real announcement and enrol the test administrator
explicitly with the Moodle Student role in that course. It also enables only
the generated native course-banner and Course Slideshow configuration needed
to mount the public Slideshow on the real course page.

Its manifest records the temporary course, forum discussion, forum post and
enrolment identifiers. `cleanup` restores exactly the captured CCB
configuration, deletes the whole disposable course and proves that the course,
discussion and enrolment are gone. The fixture deliberately does not create a
Site announcement, Assignment or Quiz; those source families need their own
scoped fixture additions before any complete public matrix is claimed.

## Prepared public Course scenario

`ccb-slideshow-public-rendering.spec.js` and
`Invoke-CCBSlideshowPublicRenderingValidation.ps1` prepare exactly one Moodle
5.1 scenario: `CCB Slideshow public Course fixture renders a real forum
announcement at the requested browser zoom`. At `-Zoom 100` it uses headless
Chromium; at `-Zoom 200` it uses a dedicated temporary Chrome window, applies
the real Chrome zoom shortcut and proves the effective 200 percent scale from
the browser metrics. Neither mode uses a Windows desktop capture: its visual
proof is a stable post-transition CDP capture of the page only. Both modes use
the public fixture above, move from the intentional empty banner slide to the
real Forum announcement, then verify the Forum label, title, action URL,
geometry and page-overflow containment.

The `-Zoom 200` mode records `zoomEvidence` alongside the page capture and a
`slideshow-public-course-forum-200-browser-cleanup.json` proof. The runner
marks the run failed unless that proof confirms the dedicated Chrome zoom was
reset, in addition to the fixture configuration/course cleanup and external
profile removal.

### Moodle 5.1 public Course, native 200 percent technical result

The 2026-08-10 native-zoom run passed exactly one public Course scenario in
32.3 seconds. It rendered the disposable Course Forum announcement after
Chrome changed from a 1540 px CSS viewport at device-pixel-ratio 1.25 to a
770 px CSS viewport at device-pixel-ratio 2.5: both independent ratios are
exactly 2.0. The stable CDP capture and JSON evidence are external under
`<EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT>\ccb\slideshow\public\supervised\ccb-slideshow-public-200-20260810T082731096Z-36648`.

The scenario found no console errors, no request failures and no horizontal
page overflow. Its cleanup confirms that the Chrome zoom was reset, the
temporary profile was removed, the temporary course/forum/enrolment were
removed and the captured public Slideshow configuration was restored. It uses
an authenticated Moodle administrator who is also enrolled as Student in the
temporary course; it therefore includes the administrator's global Moodle
chrome and does not prove the separate ordinary-Student chrome/drawer layout.
This is technical evidence for one real Forum source at native 200 percent,
not an acceptance of other public source families, mobile rendering or Moodle
4.5/5.2 coverage. Human visual review remains required.

### Prepared admin/public Course configuration parity gate

`Invoke-CCBSlideshowPublicRenderingValidation.ps1 -Parity` prepares one
focused comparison at the requested zoom. Unlike the existing public fixture
profile, parity mode starts from the saved Course Slideshow configuration. It
preserves its visual values (colours, typography, dimensions, alignment and
positions), then forces only the disposable-test prerequisites: the Course
Slideshow is enabled, its real Forum source is enabled, other source families
are disabled, autoplay is paused and the navigation needed to reach the Forum
slide is available. The fixture records both the saved configuration and this
short forced-values list, then restores the exact captured plugin records.

The one selected scenario captures the Course editor preview after its modal is
fully settled and the matching real Course Forum slide. It writes the two CDP
captures, the saved/forced configuration proof and the observed admin preview
style variables into the external run directory. The title and background are
still source-specific content, so this gate proves shared settings and makes
their visual result comparable; it does not claim byte-for-byte image identity.
It is prepared and statically validated only. No Moodle fixture, browser or
localhost preview has been run for this parity gate yet.

The initial Moodle 5.1 run at 100 percent selected exactly one scenario and
completed its course, Forum, enrolment, configuration and external-profile
cleanup. It isolated a fixture conversion error rather than a product defect:
the manager returns the saved overlay opacity as a fraction (`0.38`), while
its write API accepts a percent. Parity setup now converts that one value to
the manager input convention before it writes the disposable configuration.
The scenario records and asserts the primary opacity input, its side-panel
proxy and the preview CSS variable against the saved value. A subsequent
lease-authorized execution is required before replacing the earlier `0.00`
evidence.

It also records the Forum label text size, icon bounds, padding and gap in
both surfaces. The ratio between the native Moodle monologo and its label text
must match between the settled administration preview and the real Course
banner; this protects visual parity while permitting the two canvases to have
different physical widths.

### Prepared Site provenance-label parity gate

`Invoke-CCBSlideshowPublicRenderingValidation.ps1 -SecondaryLabelParity`
selects the same single public source scenario in a Site-specific mode. The
fixture snapshots both Course and Site Slideshow records, preserves the saved
Site appearance, enables only the Assignment source required for the test and
creates one visible disposable source course. The authorized test account is
enrolled in that course as a Student and receives one real future Assignment.

The Site Slideshow is the context that aggregates assignments and quizzes from
the learner's enrolled courses. It therefore renders the Assignment type label
together with the source course shortname through `secondaryLabel`. A Course
Slideshow intentionally queries assignments and quizzes only from the current
course, so the generic `COURSE101` administration example must not be compared
to a same-course Forum slide.

The scenario opens the Site administration preview after it has settled, then
the real site banner. It requires the Assignment label, its native Moodle icon,
the source-course shortname label, the expected Assignment destination URL,
contained geometry and no console or request errors. The source-course label
keeps its distinct course-origin colours, but must share the adjacent
source-type label's height, typography, padding, border and radius in both
surfaces. At the 1600 by 900 default-zoom desktop gate, it also requires the
two admin label font sizes to match their public counterparts. The editor
canvas is intentionally narrower than the public banner, so this is a physical
text-size check rather than a canvas-relative check. Cleanup removes the Assignment by deleting its disposable source
course, removes the temporary Student enrolment and restores all captured CCB
configuration. The first lease-authorized Moodle 5.1 execution passed on
2026-08-13 with complete cleanup. It exposed a 27.234 px primary-label height
against a 25.703 px source-course-label height; the focused parity guard now
requires those adjacent labels to use the same geometry on both surfaces.

The runner performs discovery first, requires exactly one selected test and
acquires `moodle51-active-fixture-write` before it creates the fixture. It
loads credentials only into its own process, keeps its isolated Chromium
profile and all raw evidence outside Git, and requires four cleanup facts:
course removed, Forum discussion removed, Student enrolment removed and CCB
configuration restored. It preserves the existing watchdog and drains both
Node output streams from process start to avoid an output-buffer deadlock.
Its one-test Playwright timeout is 150 seconds: the external runner watchdog
remains the upper ownership boundary, while the longer test allowance avoids
misclassifying a settled admin/public capture as a rendering failure on a busy
shared Moodle 5.1 runtime.

### Planned proportional-rendering audit (P2)

The current gate proves the Site five-to-one label pair at one desktop viewport.
A subsequent P2 matrix must exercise every banner format and supported screen
size, then compare title, body, action and label typography, icons, controls,
spacing and positions against the public banner. It must prove that each
element stays proportional to the selected banner frame without claiming that
this focused label correction validates the rest of the matrix.

This prepared scenario is limited to a 1600 by 900 default-zoom public Course
page. It does not claim mobile, native 200 percent browser zoom, Site
announcements, Assignment, Quiz, non-administrator permissions or any full
public rendering matrix.

On 2026-08-09, one lease-authorized Moodle 5.1 execution passed in 32.5
seconds. It selected exactly this scenario, rendered the real Forum
announcement at a 1600 by 900 default-zoom viewport, reported no console or
non-aborted request failure, and completed all four fixture-cleanup checks plus
profile removal. Its CDP capture was accepted by human visual review. This does
not validate the deferred public matrix variants.

At widths of 768 px and below, the administration modal uses an editor-specific
working height instead of inheriting the public banner's very wide ratio. It
also retains readable minimum sizes for labels, title, body and action. This
rule is deliberately limited to the large administration preview; it does not
change the public Slideshow layout or its configured ratios.

The editor also measures its rendered label group after the modal is visible
and after a preview or label resize. If the group would cross the visible
preview edge, it applies a temporary pixel offset to the administration
preview only. The saved `labelx` value and public Slideshow CSS remain the
authority, so this does not silently rewrite a banner's configured position.

At the 390 px mobile viewport, the matrix requires a preview at least 200 px
high, label text at least 12 px, title text at least 16 px, and body/action
text at least 13 px. At the tablet and mobile viewports, the preview action is
also at least 96 px wide and 36 px high. These are administration-preview
floors only: a stored public banner action remains governed by its configured
size. The complete label group must remain within the preview.

Moodle may emit backup-controller diagnostics on stderr while its CLI process
returns success during course deletion. The runner records that stream but uses
the fixture process exit code and its JSON restoration proof as the cleanup
authority. Browser requests explicitly aborted by navigation are recorded as
non-failures; all other failed requests remain test failures.

## Current execution status

The first real preflight on 2026-08-05 reached the administration page and
created its CDP capture, but initially classified login-navigation background
calls as Slideshow failures. Apache access evidence shows the affected AJAX and
banner-image URLs answered `200`; this was a test-interaction defect, not a
Slideshow failure. Its initial cleanup path also incorrectly promoted a
successful Moodle stderr diagnostic; targeted recovery confirmed that the
temporary course was removed and the Slideshow configuration was restored.
The scenario starts its request-failure window only after the Slideshow page
is network-idle and redacts URL parameters in its JSON evidence. Human visual
acceptance is still required from the CDP images; the matrix's geometry checks
do not replace it.

On 2026-08-07, the first localhost preview run correctly stopped at the tablet
Course cell because the complete label group was 24.5 px outside the left
preview edge. The source correction above is awaiting a fresh approved preview
promotion and the same single-scenario rerun; no public rendering result is
claimed.
