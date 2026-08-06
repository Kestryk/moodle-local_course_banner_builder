# Accessibility test protocol

## Scope

Course Banner Builder targets WCAG 2.2 Level AA for the user interface owned by
the plugin. Automated checks must target plugin regions rather than the whole
Moodle page so that failures from the active theme or Moodle core are reported
separately.

Primary regions:

| Surface | Stable selector |
| --- | --- |
| Administration pages | `.local-course-banner-builder-admin` |
| Open plugin modal | `.modal[id^="local-course-banner-builder-"].show` |
| EasyEdu guide | `.local-course-banner-builder-easyedu-guide` |

Hidden modals must not be included in an axe scan. A selector used by an
automated test must resolve to one visible element.

## Automated smoke checks

### Moodle Behat

The first Moodle-native smoke scenario is in
`tests/behat/accessibility.feature`. It:

- logs in as the Behat administrator;
- opens the course banner administration page;
- verifies the plugin administration region with Moodle's axe integration.

Moodle only executes the axe assertion when axe is enabled in the Behat
configuration. The scenario is tagged `@javascript` and `@accessibility` as
required by Moodle.

Run it with the configured Chrome profile:

```bash
moodle-plugin-ci behat --profile chrome
```

Until a dedicated Behat suite is available, use the tag:

```bash
php admin/tool/behat/cli/run.php --profile chrome --tags @local_course_banner_builder
```

### Playwright and axe

`tools/playwright/accessibility-smoke.spec.js` is an opt-in smoke test for a
prepared local Moodle site. It does not contain credentials and skips itself
when the required environment variables are absent.

Install the runner outside the plugin package or in a local tooling directory:

```bash
npm install --save-dev @playwright/test @axe-core/playwright
npx playwright install chromium
```

PowerShell example:

```powershell
$env:CCB_MOODLE_URL = "http://localhost"
$env:CCB_MOODLE_USERNAME = "Admin"
$env:CCB_MOODLE_PASSWORD = "<local-password>"
npx playwright test tools/playwright/accessibility-smoke.spec.js
```

Optional variables:

| Variable | Default | Purpose |
| --- | --- | --- |
| `CCB_ACCESSIBILITY_PATH` | `/local/course_banner_builder/admin_manage.php` | Authenticated page to scan |
| `CCB_ACCESSIBILITY_SELECTOR` | `.local-course-banner-builder-admin` | Visible plugin region |

The smoke test blocks on critical and serious axe violations. It is manual
until login, data preparation, and browser versions are deterministic in CI.

### Shared Moodle 5.1 credentials

Do not create `EASYEDU_MOODLE_*` or `CCB_MOODLE_*` user or machine
environment variables. The one-time setup stores a Windows DPAPI-protected
credential file outside the repository:

```powershell
.\Configure-CCBMoodle51Credentials.ps1
```

For later Playwright specs, use the shared wrapper. It imports the saved
credential only into the wrapper and Node child process, then removes the
variables before returning to the parent shell:

```powershell
.\Invoke-CCBPlaywrightWithSavedCredentials.ps1 `
    -Spec accessibility-smoke.spec.js `
    -PlaywrightArgument @('--reporter=line', '--workers=1')
```

Batch-specific `EASYEDU_CCB_*` variables may be set in the current process when
the scenario requires them. They describe the fixture and evidence location;
the password itself must always come from the wrapper. Fixture runners remain
responsible for creating and clearing dynamic IDs, so category IDs and course
mutation settings must never be persisted globally.

### Public title semantics (Batch 2F-A.1 and 2F-B.1)

The authoritative harness is
`local/course_banner_builder/tools/playwright/ccb-banner-public-title-accessibility-2fa.spec.js`.
It uses the stable Moodle 5.1 course 11 and CMID 12, creates a disposable QA
source category dynamically, and restores the course and plugin settings in
`finally`. Artifacts and headed Chrome profiles stay outside Git under
`%LOCALAPPDATA%\EasyEdu\artifacts`.

Batch 2F-A.1 validates the public H1/H2 contract at 100%. Batch 2F-B.1 is the
single genuine 200% continuation. Its supervised runner verifies `--list`
selection of exactly one test before acquiring the CCB fixture lease, captures
sanitised JSON evidence, and removes only its owned profiles and processes.

```powershell
$env:EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT = Join-Path $env:LOCALAPPDATA 'EasyEdu\artifacts'
& powershell -NoProfile -ExecutionPolicy Bypass `
    -File .\Invoke-CCB2FB1Supervised.ps1 -WatchdogSeconds 900
```

The supervisor loads the saved DPAPI credential automatically. Moodle URL and
credentials remain process-local environment variables and are never written
to the command history or artifacts. B2F.1 passed on 2026-07-27
with a 2.00 width ratio and 2.00 device-pixel-ratio ratio, one accessible H1,
one contextual H2, no horizontal overflow, and complete cleanup. The passing
run is `ccb-2fb1-supervised-20260727T143916775Z-9640`.

The corresponding 2F-A.1 run uses the same supervisor with `-Batch 2FA1`.
Discovery-only mode is available for either batch and does not touch Moodle:

```powershell
.\Invoke-CCB2FB1Supervised.ps1 -Batch 2FA1 -DiscoveryOnly
```

The headed browser uses native Chrome host zoom. CDP page captures remain
external and non-foreground so evidence collection does not focus the user's
desktop or trigger the known black-screen capture problem.

### Manual public screen-reader protocol

This protocol is not replaced by DOM checks or an accessibility snapshot.
Use NVDA on the workstation (or record the available equivalent), at 100% and
200% browser zoom, with a course banner whose title is long enough to wrap.

1. Open the course page with CCB title replacement off, then use heading
   navigation. Hear one level-1 course heading and no second spoken duplicate
   from the visual banner title.
2. Turn replacement on, reload, and repeat. Hear the same single level-1
   heading even though it is no longer visibly printed in the Moodle header.
3. Read continuously from the page header through the banner. Decorative image
   file names, overlays, borders and title-frame styling must not be announced.
4. On an activity with a distinct course-and-activity banner title, verify the
   activity stays level 1 and the additional course context is level 2 exactly
   once. On site pages, check that an equal site/h1 title is not repeated.
5. Tab through any slideshow controls. Each must have a name, visible focus,
   state, normal exit with Tab/Shift+Tab, and must never become unreachable
   because of `aria-hidden`.
6. At genuine 200% Chrome zoom, check the long title, focus ring, banner frame
   and page width for clipping or horizontal overflow.

Record the screen reader/version, browser/version, Moodle/theme, fixture,
zoom, and any spoken duplicate. Do not claim screen-reader validation from the
automated snapshot alone.

## Manual keyboard protocol

Run the following checks on Moodle 4.5 and 5.1, with a standard Moodle theme
and the EasyEdu theme when available.

### Administration navigation

- Reach every primary navigation entry with `Tab`.
- Confirm the active view is not communicated by colour alone.
- Confirm focus remains visible at 200% browser zoom.
- Open and close the course or site banner options with the keyboard.
- Confirm disabled options cannot be activated and expose their disabled state.

### Source and layer management

- Select a category or custom-field source without a mouse.
- Reach layer actions, enabled states, and bulk actions in a logical order.
- Reorder layers with the provided move-forward and move-back controls.
- Confirm drag and drop is never the only way to reorder a layer.
- Confirm status icons have an accessible name or adjacent text.
- Focus every source/layer status or help indicator and confirm its contextual
  help becomes available through `aria-describedby` without blurring the
  trigger. Tab away and confirm the tooltip is dismissed.
- At narrow mobile width, confirm table action, inherited-source, and bulk
  controls keep a 44px minimum target while the table remains scrollable.

### Preview editors and modals

- In the selected source editor, tab to **Preview mode**, activate Desktop or
  Mobile with Space/Enter, and move between buttons with Arrow keys, Home and
  End. Confirm the active button exposes `aria-pressed="true"` and the help
  text explains that mobile simulation is local and not saved.
- Reload the page after choosing Mobile and confirm the editor defaults back
  to Desktop; the read-only source-chain preview offers the same switch but
  must not expose source/layer editing controls.
- Open each image, border, overlay, title, and slideshow modal by keyboard.
- Confirm focus moves into the modal and returns to the opener after closing.
- Confirm the modal has an accessible name and a keyboard-operable close path.
- Confirm sticky previews and footers do not cover the focused control.
- Reach accordions, sliders, numeric inputs, colour fields, and preview tools.
- Confirm linked numeric inputs provide a non-pointer alternative to sliders.

### Image crop and placement

- Start and cancel crop mode without a mouse.
- Confirm crop validation and cancellation have accessible names.
- Confirm crop handles do not remove access to numeric or button alternatives.
- Confirm image order and position can be adjusted without drag and drop.

Crop geometry cannot be validated by axe alone. Pointer, keyboard, and visual
checks remain required until a stable component-level automation fixture
exists.

### EasyEdu guide

- Open and close the guide with the keyboard.
- Navigate slides with buttons and arrow keys.
- Confirm locked slides explain their prerequisite without colour alone.
- Start a guided path and complete its checklist without pointer-only actions.
- Confirm `Show in interface` moves focus or provides a clear return path.
- Confirm `Return to guide` restores the previous guide context.
- Confirm temporary highlights do not trap focus or remain after dismissal.

## Visual and contrast checks

- Verify text and controls at 200% zoom without loss of information.
- Check desktop, tablet, and narrow mobile widths.
- Verify focus indicators against image, overlay, and neutral backgrounds.
- Test default configured colours and representative light/dark extremes.
- Verify error, warning, success, disabled, inherited, and locked states without
  relying only on colour.
- Verify uploaded images use decorative empty alternatives when they do not
  communicate content, and meaningful alternatives where content is conveyed.

## Exclusions policy

There are no permanent axe rule exclusions.

Any future exclusion must document:

1. axe rule id;
2. exact selector;
3. whether the owner is Moodle core, the theme, or this plugin;
4. a linked issue and expiry condition;
5. the manual check that replaces the automated rule temporarily.

Do not expand the target to `body` merely to simplify a test. Plugin-owned
regions are the contract for CCB accessibility gates.

## Known automation gaps

- There is no versioned Behat fixture for a complete source inheritance chain.
- There is no versioned upload set covering PNG transparency, oversized images,
  invalid MIME types, and file-size rejection.
- Crop movement, resize handles, and multi-image draft switching need a
  deterministic Playwright fixture.
- Theme-dependent contrast needs prepared EasyEdu and standard-theme sites.
- Touch and long-press behaviour requires a mobile browser project.

These gaps are tracked in `functional-protocol.md` and must not be hidden by
making an unstable test mandatory.
