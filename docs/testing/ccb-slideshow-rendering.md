# CCB Slideshow rendering audit

## Purpose

`EED-CCB-2026-0002` prepares a reproducible visual audit of the Course Banner
Builder Slideshow administration and public rendering surfaces. The initial
scenario is deliberately a **preflight**, not the complete responsive matrix.

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

## Initial preflight assertions

The one selected test confirms that the Course and Site cards, their visual
previews, overlay settings and configured limit fields render together. It
captures one CDP image and rejects console or failed-network errors. It does
not claim full option coverage, public rendering coverage or responsive
acceptance; those belong to the later human-approved matrix.

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
The scenario now starts its request-failure window only after the Slideshow
page is network-idle and redacts URL parameters in its JSON evidence. This is
still not visual acceptance or a rendering-matrix result.
