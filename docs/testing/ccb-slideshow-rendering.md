# CCB Slideshow rendering audit

## Purpose

`EED-CCB-2026-0002` prepares a reproducible visual audit of the Course Banner
Builder Slideshow administration and public rendering surfaces. The initial
scenario is deliberately a **preflight**, not the complete responsive matrix.

## Label icon authority

The Slideshow administration preview and its label-colour samples use Moodle
activity monologos, rather than Font Awesome approximations: Forum for Course
Forum and Site Announcements, Assignment for Assignment, and Quiz for Quiz.
Site Announcements are Forum posts, so the Forum monologo is intentional. The
same component mapping is used by the public banner runtime.

When a selected banner format makes the large administration-preview label
text small, that preview keeps a 1rem minimum icon size. This editor-only
readability floor does not alter a public Slideshow label or its configured
proportions.

## Owned test sources

- `tools/playwright/ccb-slideshow-rendering-fixture.php`
- `tools/playwright/ccb-slideshow-rendering.spec.js`
- `tools/playwright/Invoke-CCBSlideshowRenderingValidation.ps1`

The fixture creates one hidden temporary course, records all existing plugin
configuration entries beginning with `slideshow_course_` or `slideshow_site_`,
applies a contrasting valid profile, then restores exactly those records and
removes the course in the runner's `finally` block. It never uses course 2,
an EasyStud fixture, a Sources/Layers fixture, or a browser profile inside Git.

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

The administration preview uses the same Moodle monologo-to-text proportion
as the public Slideshow label. It must not impose an editor-only icon size
floor, because that would make a label icon visually larger than its matching
public Course banner when the preview canvas is smaller. The colour-reference
samples remain an administration aid and are not a public rendering contract.

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
