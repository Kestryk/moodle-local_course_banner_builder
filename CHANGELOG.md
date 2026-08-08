# Changelog

All notable changes to Course Banner Builder are documented in this file.

The format is inspired by [Keep a Changelog](https://keepachangelog.com/).
Entries are grouped by day so related changes stay together instead of creating
many small sections.

## Unreleased

### 2026-08-06

#### Fixed

- Kept the Slideshow administration modal preview readable at mobile widths by
  giving the editor a minimum working height, preserving minimum text sizes and
  keeping the complete label group inside the preview with an ephemeral
  measured editor-only offset. Saved label coordinates, public Slideshow
  banners and their configured ratios are unchanged.
- Extended the Slideshow matrix with the matching 390 px readability thresholds
  and a settled-modal capture wait, so visual evidence is captured after the
  opening animation rather than during it.

### 2026-08-05

#### Tests

- Extended the isolated Slideshow check into the Course/Site administration
  preview matrix at desktop, tablet and mobile widths. It records external CDP
  captures, all side-panel groups and geometry evidence for labels, title,
  body and action; public-runtime slides remain explicitly out of scope.
- Added the isolated Slideshow rendering-audit fixture, one-test Playwright
  preflight and DPAPI-compatible supervised runner. The fixture snapshots and
  restores only Slideshow configuration and removes its own temporary course;
  it does not use the shared course 2 fixture.
- Made fixture cleanup rely on the Moodle CLI exit code and JSON restoration
  proof rather than stderr alone, because Moodle may emit backup-controller
  diagnostics during a successful temporary-course deletion.
- Scoped Slideshow request-failure evidence to the stable target page and
  redacted sensitive URL parameters, preventing Moodle login-navigation calls
  from being misclassified as Slideshow regressions.

### 2026-07-18

#### Changed

- Contained the configured-source table inside its administration panel at
  laptop and tablet widths, while allowing open inline source menus to escape
  the horizontal scroll shell.
- Aligned source-chain list controls and empty-source feedback with the shared
  EasyEdu table toolbar and empty-state surfaces.
- Hid the global source-chain collapse action when the configured-source list
  contains only independent root sources and therefore has nothing to fold.
- Harmonised source and layer row actions with neutral and destructive EasyEdu
  variants while retaining the existing edit, preview and confirmation flows.

#### Documentation

- Added Course Banner Builder-specific functional, accessibility and release
  test protocols aligned with the EasyEdu Moodle quality gates.
- Documented the current system-level capability boundary, the missing
  deterministic fixtures and the staged promotion path for Moodle 4.5 and 5.1
  matrix jobs.

#### Tests

- Added an opt-in Playwright/axe smoke test scoped to the visible Course Banner
  Builder administration region.
- Added a Moodle-native Behat accessibility smoke scenario for the course
  banner administration page.
- Added a fixture-free Behat smoke scenario covering administrator access and
  rendering of the Course Banner Builder administration root.

### 2026-07-17

#### Documentation

- Added mandatory human and AI guidance for safe single-owner,
  commit-and-push development handoffs between workstations.

### 2026-07-16

#### Changed

- Included the exact 768 px viewport in the compact administration and
  Slideshow modal layouts so preview-side actions stack below the preview
  instead of retaining the cramped desktop rail at the breakpoint.
- Removed duplicated late accordion styling from the CCB adapter by excluding
  disclosure triggers from generic preview command-button rules, leaving the
  shared EasyEdu accordion mixin as the visual source of truth.
- Anchored horizontally scrollable administration navigation to its start edge
  on compact viewports so the guide launcher and first navigation action remain
  reachable instead of being clipped by centred overflowing flex content.

### 2026-07-15

#### Changed

- Harmonised Course, Activity and Site title editor controls with the shared
  EasyEdu colour, numeric-input and preview-side-panel surfaces while keeping
  title frame sizing, responsive positioning, drag and resize calculations
  unchanged.
- Kept title activation and Moodle-title replacement actions together on one
  compact row in course title editors.
- Harmonised transfer import controls with the EasyEdu option-list rhythm,
  including a dedicated archive surface and a clear warning state for the
  destructive replace-all option without changing Moodle form validation.
- Stacked export and import panels before their file and option controls become
  cramped, while keeping the two-column transfer workspace on wide screens.

### 2026-07-14

#### Changed

- Harmonised the Image layer options in add/edit modals with compact EasyEdu
  select, helper and numeric-control surfaces while preserving Moodle select
  indicators and every crop, resize, drag and positioning interaction hook.
- Synchronized EasyEdu UI kit `0.4.38` and applied its native Moodle QuickForm
  colour-group contract so border swatches and textual values remain aligned
  on one compact row without changing form submission or validation hooks.
- Corrected the dynamically loaded border editor heading so edit actions no
  longer reuse the add-border label.
- Synchronized EasyEdu UI kit `0.4.37` and kept semantic accent rails inside
  rounded administration panels without hiding menus or popovers that escape
  their panel surface.
- Rebuilt the embedded guide AMD artifact as a genuinely minified production
  module with its source map kept in sync with the readable source.
- Excluded embedded EasyEdu development documentation, guide references and
  motion-kit sources from production archives while retaining them in Git for
  maintenance and future synchronisation.

### 2026-07-13

#### Changed

- Synchronized the embedded EasyEdu UI contracts to version `0.4.36`, adding
  the form action, segmented-choice, balanced administration navigation and
  modal history primitives while preserving CCB-specific popover, accordion
  and motion refinements.
- Harmonised Course and Site Slideshow administration cards with equal heights,
  content-sized settings sections, context-coloured appearance actions and a
  shared action zone anchored at the bottom of each card.
- Clarified checked, unchecked, focus and disabled Slideshow control states and
  restored the semantic section borders previously suppressed by an undefined
  kit token.
- Aligned image, border, overlay and slideshow preview-modal content with the
  shared EasyStud `1rem` horizontal rhythm while preserving the wider action
  rail and every interaction-owned preview dimension.
- Stabilised source, layer and inheritance table headers with the compact
  EasyStud Mass Import typography, natural column sizing and no mid-word
  wrapping.
- Kept Border style open on its first selection in the add-layer modal while
  allowing the user to close it for the rest of that modal session.
- Separated layer-type visibility from preview-side accordion disclosure so
  border and overlay form synchronisation can no longer reopen a panel that the
  user has closed.

### 2026-07-12

#### Changed

- Synchronized the EasyEdu guide action contract with compact rounded
  Previous, Next, Show in interface and Start guided path controls.
- Completed the embedded EasyEdu motion reference package and added a dedicated
  animated inheritance-chain learning slide to the course banner guide.

### 2026-07-11

#### Changed

- Introduced the shared EasyEdu administration typography roles and aligned
  option, slideshow, transfer, table and modal headings without changing
  user-configurable banner or slideshow text.
- Vendored the cancellable EasyEdu motion runtime and used it for the
  course/site banner options disclosure, including repeated-click cleanup and
  native reduced-motion support.
- Replaced timeout and `max-height` orchestration in image, border, overlay,
  title and slideshow preview-side accordions with the shared cancellable
  motion runtime, while closing sibling panels atomically to keep preview
  layouts stable.
- Made slideshow accordion target state authoritative during interrupted
  transitions so rapid open/close/open input cannot desynchronise the panel and
  its trigger.
- Reworked preview-side accordion headers as stable editor disclosures with a
  compact semantic icon tile, end chevron and calmer hover/active treatment.
- Matched collapsed preview-side accordions to adjacent command heights and
  removed the grid gap between each expanded header and its content panel.
- Applied the shared accordion contract to slideshow controls at initial render
  instead of waiting for their first interaction.
- Harmonised banner format choice cards and their selected/focus states while
  preserving every simulated banner dimension shown in the format previews.
- Reorganised each slideshow administration card into a distinct activation
  band plus labelled Content and Controls sections, with contextual active
  toggle states and compact numeric field rows.
- Made image, border and overlay edit modals acknowledge clicks immediately
  with an accessible loading surface while Moodle prepares the dynamic form.
- Added short-lived hover and keyboard-focus prefetching for layer editors so
  frequently opened forms can become usable in roughly one transition.
- Reduced AJAX-only modal work by skipping title-preview and upload-guidance
  calculations that are not rendered in edit-form responses.
- Hardened dynamic form reordering when identity fields are moved below the
  preview, preventing an intermittent `insertBefore` exception during modal
  preparation.
- Harmonised border, overlay and image preview-side accordions as integrated
  EasyEdu components with stable semantic accents and no secondary close row.
- Unified modal colour controls and linked slider/number rows while preserving
  the existing CCB input bindings, ARIA state and live preview behaviour.
- Verified border and overlay sliders, accordion open/close and preview
  geometry in Moodle 5.1 with no browser console errors.
- Synced EasyEdu UI kit 0.4.27 non-guide visual contracts and applied the
  EasyStud-derived contextual modal chrome to CCB preview editors.
- Added semantic accent rails to Transfer and Slideshow administration panels,
  and distinct source, layer and inheritance-chain table surfaces without
  changing their data, drag/drop or action-menu contracts.
- Promoted the tested CCB preview modal, side accordion and table shell
  primitives back into the canonical EasyEdu kit.
- Extended the embedded EasyEdu guide with explicit action-owned checklist
  completion and reusable animated learning scenes shared with EasyStud.
- Synced the latest EasyEdu guide contract for solid primary guide actions,
  checklist return labels, stronger animated learning scenes and safer guided
  checklist wrapping.
- Synced the refined EasyEdu guide slide spacing and larger introductory
  flow/card learning scenes.
- Replaced the rounded, raised admin navigation treatment with shorter labels
  and a stronger EasyEdu application rail using tiled icons, a flat filled
  active state and responsive control wrapping instead of horizontal scrolling.
- Separated contextual banner status actions from the primary navigation with
  explicit active and disabled states, state-based wording and popovers carried
  by the controls instead of separate question-mark buttons.
- Refined the primary rail into traditional flat bordered tabs with a lighter
  type treatment and a fine active-view accent, then joined the tabs across the
  full rail height and increased their typography for clearer navigation.
- Restyled the destructive settings action as a transparent guide-adjacent
  icon-only control, centred the primary tabs between it and the guide launcher,
  and added a reusable red EasyEdu warning popover variant.
- Grouped site, course and activity title editors in a dedicated trailing area
  of the secondary navigation.
- Reworked the status area into responsive General, Images, Generation and
  Titles groups, removed redundant enabled/disabled wording, and made active,
  inactive and unavailable states visual rather than textual.
- Centred the flatter full-height primary navigation between the unchanged
  guide launcher and icon-only reset action, while returning navigation text to
  the Moodle theme font.
- Moved the active primary-navigation accent to the bottom edge, lightened its
  typography, restored the guide launcher popover and aligned both edge actions
  to the same square footprint.
- Made course and site banner options collapsible and closed by default, with
  their title-edit modals kept outside the collapsed DOM ancestor.
- Anchored navigation popover arrows to their actual trigger even when a long
  warning popover is clamped against the viewport edge.
- Added the soft EasyEdu context gradient to the primary rail, refined its
  translucent hover state and corrected the shared popover mixin so dynamic
  arrow anchoring is no longer overwritten by a static 50% rule.
- Added a reduced-motion-aware opening animation to the collapsed banner
  options and normalised the content padding and group heights.
- Added a CCB-specific UI harmonisation baseline that classifies administration
  views and runtime-sensitive selectors before broader EasyEdu adoption.
- Rebuilt the transfer screen as responsive Export and Import task panels,
  removed its duplicated page heading and grouped export checkboxes through a
  reusable EasyEdu option-list primitive.
- Harmonised the Course and Site slideshow settings cards with the same
  administrative panel/header contract and reduced the visual weight of their
  enable controls without changing form behaviour.
- Added non-structural EasyEdu table surfaces to the configured-source, layer
  and inherited-layer tables while preserving their column sizing, action
  overflow, row selectors and drag-and-drop hooks.
- Matched the configured-source table corners to the layer table by rounding
  corner cells directly, preserving the source table's required visible
  overflow for action menus and popovers.
- Documented the preview-side accordion contract so an opened action becomes
  the persistent panel header instead of spawning a duplicate close button.
- Implemented integrated preview-side accordions for Image layer options,
  Border style and Overlay settings: each existing action now stays above and
  visually joins its opened panel while retaining the original open/close
  engine and form controls.
- Aligned opened preview-side panels with their collapsed triggers by using the
  same white surface, fine border and restrained active tint instead of a
  separate heavy blue content rail.
- Extended the integrated-header contract to title and slideshow side panels
  while keeping their existing selection, animation and form-update engines.
- Introduced a shared EasyEdu shell for layer, title and slideshow preview
  modals, standardising viewport dimensions, flex containment and footer
  surfaces while leaving each preview grid and sticky calculation untouched.
- Refined long EasyEdu-style popovers with balanced inner spacing, centred
  headings and justified body copy.
- Added richer EasyEdu guide scenes to source selection, source settings, layer
  guided paths and slideshow save flows, and retargeted the source slide to the
  source picker controls instead of a potentially empty configured-source list.
- Synced the hardened EasyEdu guide scene layouts so long translated guide
  labels and guided-path buttons wrap inside their containers instead of
  overflowing.
- Synced the animated guide pointer refinement so context-menu learning scenes
  keep their motion without causing horizontal overflow.
- Synced the restored EasyEdu guide action and checklist styling so guide
  buttons stay blue, learning surfaces are centred, guided-path cards use a
  stronger green surface without an extra rail, and checklist rows avoid
  internal text overflow.

### Verified

- Rebuilt the plugin stylesheet in the Moodle 5.1 workspace and purged Moodle
  caches.
- Validated all modified language files with PHP lint and checked the Git diff.

## Unreleased - 2026-07-09

### Changed

- Reworked the EasyEdu admin navigation styling on Moodle 5.1 so top-level
  admin buttons use a sober non-wrapping navigation rail, while course/site
  status controls use a separate secondary action style.
- Aligned Course Banner Builder hover popovers with the EasyStud/EasyEdu
  popover surface without changing the existing popover JavaScript engine.
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
