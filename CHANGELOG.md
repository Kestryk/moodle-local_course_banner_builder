# Changelog

All notable changes to Course Banner Builder are documented in this file.

The format is inspired by [Keep a Changelog](https://keepachangelog.com/).
Entries are grouped by day so related changes stay together instead of creating
many small sections.

## Unreleased - 2026-07-09

### Changed

- Refactored the image crop workflow on Moodle 5.1 so crop editing now keeps a
  session-only source rectangle and placement snapshot instead of rebuilding the
  next crop state from multiple competing DOM/data sources.
- Reworked crop action placement so apply/cancel controls are positioned as a
  fixed overlay beside the crop box instead of being forced back inside the
  cropped area when space is tight.
- Protected draft visual/context layers during crop editing so temporary crop
  layouts are no longer mirrored back into background preview layers during
  multi-image editing.
- Stabilised second-crop behaviour in the add-image modal: reopening and
  applying a crop without changing the selection now restores the exact previous
  visible placement instead of progressively shrinking or drifting.
- Suppressed the trailing click generated at crop release time so enlarging a
  crop over another image no longer selects the background image by accident in
  modal previews.

### Verified

- Rebuilt `amd/build/admin_manage.min.js` and its source map from the updated
  AMD source.
- Checked `amd/src/admin_manage.js` and the rebuilt artifact with
  `node --check`.
- Verified in headless Chromium:
  - crop action buttons stay outside the crop frame in the general preview;
  - second crop of the same image in the add-layer modal no longer drifts when
    apply is clicked without moving the crop;
  - switching between multiple draft images no longer re-expands the previously
    cropped visual layer;
  - releasing a crop drag over another image no longer changes the active draft
    selection.

## Unreleased - 2026-07-06

### Changed

- Added an early course banner guide slide explaining the plugin glossary:
  sources, source chains, inheritance, layer types, composition modes and
  overrides.
- Retargeted the EasyEdu guide source setup steps to the actual source dropdown
  wrapper instead of configured-source result tables that may be empty for new
  users.
- Synced the embedded EasyEdu guide contracts and documentation for
  `highlightTarget`, temporary show-in-interface highlights and temporary
  guided checklist highlights that leave the checklist visible.
- Updated the guide flow so dismissing or auto-hiding the temporary
  "Return to guide" panel clears the interface selector, while guided checklist
  highlights disappear after a delay without closing the checklist.
- Aligned locked checklist steps with the EasyEdu locked navigation style:
  blocked steps now use the same striped visual language and name the required
  previous checklist step in the overlay.

## Unreleased - 2026-07-05

### Added

- Synced EasyEdu UI Kit AI contracts into `easyedu-kit-docs/ai/`, including
  kit-first implementation rules, guide parity checks and Moodle plugin review
  rules for future Codex handoffs.

### Changed

- Realigned the active EasyEdu guide selector/highlight engine with the kit
  implementation, including viewport-fixed target highlighting, target state
  class handling and transition/mutation refresh support.
- Excluded `easyedu-kit-docs/` from Git archive exports so development
  contracts remain versioned without shipping in production packages.

## Unreleased - 2026-07-03

### Added

- Embedded EasyEdu UI Kit `v0.4.24` as a plugin-local copy so Course Banner
  Builder can use shared EasyEdu UI primitives without adding a runtime
  dependency on another plugin.
- Added the EasyEdu guide launcher, guide modal, rich horizontal guide
  navigation, progress bar, visual guide cards, guided path checklist and
  highlight system to the course/site banner and slideshow administration pages.
- Added Moodle AMD integration for the EasyEdu guide foundation, including
  Moodle-specific storage keys, strings and target selectors.
- Added multilingual guide strings for the supported plugin languages.
- Added richer first-step guide slides for course banners, site banners and
  slideshow settings.
- Added short guided path descriptions for banner and slideshow walkthroughs.
- Added locked guide slide unlock paths with dependent checklist steps, source
  selection persistence across page reloads and separate "Unlock: ..." paths.

### Changed

- Updated the SCSS structure documentation to describe the embedded EasyEdu kit,
  its version and the plugin-scoped integration rules.
- Scoped EasyEdu token defaults to Course Banner Builder admin and modal roots.
- Applied EasyEdu dropdown/menu primitives to source dropdown menus while
  preserving Bootstrap show/hide behaviour.
- Reworked the guide launcher as an icon-only accessible control.
- Aligned the Course Banner Builder guide template with the EasyEdu `v0.4.24`
  contract for navigation icons, guided checklist messages and return actions.
- Improved guided checklist behaviour with feedback text, dock refresh support
  and minimise/expand icon state.
- Added a visual flow to the first slideshow guide slide and aligned checklist
  minimise state with the EasyEdu accessibility pattern.
- Added EasyEdu-style guide navigation categories, per-slide nav icons, a
  stronger "Show in interface" action style and tighter fixed-position target
  highlights for guided paths.
- Aligned the guide "Show in the interface" flow with EasyEdu's fixed viewport
  anchoring model and added the sticky "Return to guide" panel after jumping to
  a highlighted interface target.
- Synced the embedded EasyEdu guide kit sources with the local guide anchoring
  and return-panel improvements.
- Synced the embedded EasyEdu guide kit documentation and SCSS with the
  dependency-aware unlock checklist pattern.
- Reduced Course Banner Builder guide-specific SCSS overrides so navigation
  cards, show-in-interface buttons, return dock and guided checklist styling now
  come directly from the embedded EasyEdu kit.
- Rebuilt `styles.css` and the EasyEdu guide AMD build artifact.

### Verified

- Checked the guide AMD source with `node --check`.
- Checked `admin_manage.php` and `admin_slideshow.php` with `php -l`.
- Rebuilt SCSS successfully with `scss/build.ps1`.
- Verified the guide modal, keyboard navigation and guided checklist with a
  targeted headless browser pass.
- Verified the "Show in the interface" action in headless Chromium: the guide
  modal closes, the fixed highlight stays on the target, and the return panel
  reopens the guide.

## 0.6.23 - 2026-07-01

### Fixed

- Polished post-production banner rendering after the first production pass.
- Fixed title preview interactions in banner title editing modals.
- Improved crop editing handles and crop action placement.
- Corrected French language encoding issues.
- Improved contextual banner title rendering for site administration, site
  navigation pages and course management pages.
- Improved banner title frame sizing so dynamic titles adapt more consistently
  to translated text and contextual page titles.

### Changed

- Kept post-production changes on a dedicated branch so production can remain
  stable while further visual polish continues.

## 2026-06-13

### Fixed

- Hardened configuration import/export handling after transfer testing.
- Fixed slideshow admin AMD module definition issues.
- Improved generated Moodle course-card thumbnails so borders, overlays and
  layer ordering are represented more consistently.

### Changed

- Tuned course-card thumbnail image and border scaling for better visibility in
  My courses and available course listings.

## 2026-06-12

### Fixed

- Addressed Moodle review feedback and preview stability issues.
- Improved stability around image preview interactions, crop behaviour and
  layer controls after Moodle precheck work.

## 2026-06-11

### Added

- Added a package-level GPL license file required for Moodle plugin review.

### Changed

- Renamed database tables to use the full plugin Frankenstyle namespace.
- Moved admin page JavaScript initialisation into AMD modules.
- Rebuilt AMD artifacts so source and build outputs stay aligned.
- Replaced direct `config_plugins` DML cleanup with Moodle configuration API
  helpers.
- Replaced large full-course `get_records()` loops with recordsets where the
  dataset can grow.
- Replaced native temp file access with Moodle temp directory helpers.
- Flattened concatenated language strings so language files remain pure
  `$string['id'] = 'value';` assignments.
- Restored explicit admin stylesheet loading where needed to preserve existing
  admin UI behaviour after AMD/style review changes.

### Fixed

- Removed direct access to submitted request data where Moodle parameter APIs
  should be used.
- Reduced use of `PARAM_RAW` by switching to more specific parameter types and
  stricter validation where possible.
- Fixed Moodle precheck and coding-style issues from the first review pass.

## 2026-06-10

### Changed

- Polished banner layer modal previews and controls.
- Improved image, border and overlay preview controls in layer modals.

## 2026-06-08

### Changed

- Updated the README for the latest beta release.
- Bumped the plugin release version.
- Addressed additional Moodle prechecker findings.

## 2026-06-05

### Changed

- Improved settings transfer tooling and broader Moodle compliance checks.
- Extended export/import coverage for plugin settings and assets.

## 2026-06-04

### Fixed

- Stabilised settings import after transfer testing.
- Refreshed guided tours to better match the current administration views.

## 2026-06-03

### Added

- Added further administration QA polish for Moodle 5.1.
- Added richer shipped tours for banner administration workflows.

### Changed

- Polished banner administration workflows, including source handling,
  previews, layer lists and modal editing flows.
