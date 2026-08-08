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

## Manual keyboard protocol

Run the following checks on Moodle 4.5 and 5.1, with a standard Moodle theme
and the EasyEdu theme when available.

### Administration navigation

- Reach every primary navigation entry with `Tab`.
- Confirm the active view is not communicated by colour alone.
- Confirm focus remains visible at 200% browser zoom.
- At compact widths, open the CCB Navigation rail, close it with Escape and
  confirm that focus returns to its trigger.
- From the compact rail, open the CCB Guide and confirm its modal is above the
  panel, uses a viewport-sized surface, and returns focus to its launcher on
  close.
- Open and close the course or site banner options with the keyboard.
- Confirm disabled options cannot be activated and expose their disabled state.

### Source and layer management

- Select a category or custom-field source without a mouse.
- Reach layer actions, enabled states, and bulk actions in a logical order.
- Reorder layers with the provided move-forward and move-back controls.
- Confirm drag and drop is never the only way to reorder a layer.
- Confirm status icons have an accessible name or adjacent text.

### Preview editors and modals

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
