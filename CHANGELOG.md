# Changelog

All notable changes to Course Banner Builder are documented in this file.

The format is inspired by [Keep a Changelog](https://keepachangelog.com/).
Entries are grouped by day so related changes stay together instead of creating
many small sections.

## Unreleased

### EED-CCB-2026-0017 - 2026-08-20

#### Fixed

- Added intentional desktop and tablet separation between the banner preview
  and its action rail. Narrow modal rails now reserve space after their last
  control so Overlay settings can scroll clear of the persistent footer.

#### Validation

- Strengthened the focused modal-action-rail scenario to measure the desktop
  rail gap and prove keyboard reachability of the first and final Overlay
  controls, including the compact footer boundary.

### EED-CCB-2026-0027 - 2026-08-20

#### Fixed

- Restored Image, Border and Overlay type icons to the coloured leading rail of
  the layer list. `Sort order` now shows its localised `Locked` label, one copy
  of each status badge and the lock badge below the order label, without
  duplicating status indicators in `Banner layer`.
- Prevent duplicate same-glyph placement badges when Above overlay and Above
  inherited coexist. Chain layer rows now use the localised `Locked` label
  instead of an em dash, all CCB explanatory popover triggers use a pointer
  cursor, and unlocked Image `Layer infos & overrides` cells share their row's
  surface instead of retaining the legacy beige tint.
- Restore the order-lock icon to a real lock in every locked state, so Above
  overlay has one layer-group badge only and no link-style hover underline.
- Match the Above overlay glyph scale to the order-lock glyph while preserving
  the existing circular badge dimensions.

#### Validation

- Updated the isolated `ccb-layer-object-row` scenario for type-rail placement,
  unique status/lock badges, localised locked text, pointer cursors, matching
  disclosure-cell surface, keyboard popovers and unchanged drag/selection.

### EED-CCB-2026-0026 - 2026-08-20

#### Changed

- Updated the `ccb-layer-object-row` regression contract for the EED-CCB-2026-0021 Sort order indicator zone. It now verifies type, lock and rendered state indicators in `Sort order`, no semantic-indicator duplicates in Selection or Banner layer, keyboard-accessible popovers, and unchanged native drag and selection.

#### Validation

- The QA change requires JavaScript syntax validation and exact-one-test Playwright discovery before a separately authorised leased browser run.

### 2026-08-18

#### Fixed

- Positioned the Async editor `Loading in progress` feedback at the same fixed
  bottom-right location as EasyStud.

### EED-CCB-2026-0012 - 2026-08-18

#### Fixed

- Restored the add-image modal's original-size draft starting geometry. `Fit to
  preview` now has a distinct proportional geometry to apply, rather than
  silently rewriting the new draft's already-fitted state. Width, height and
  aspect controls are available for the selected image and convert its visible
  geometry into the existing custom placement state on first interaction.
  The changed geometry is now committed before the draft visual layer rerenders,
  so Fit, side controls and pointer resize do not revert to the preceding
  draft state. The active draft's visual twin is excluded from alignment peers,
  preventing it from repeatedly snapping a dragged image back onto itself.
  Aspect-locked vertical and corner handles now follow the axis actually moved,
  while Fit commits and rerenders the active draft once without the redundant
  generic serialization pass. Pointer frames synchronize linked sliders once,
  defer popover/handle measurement until the gesture ends and cap peer snapping
  on complex previews. The add-image form now writes Fit directly to the active
  draft state, and its selection-shell handles start the dedicated draft resize
  path instead of being intercepted by the generic layer interaction. Those two
  create-only paths now also resolve Moodle's id-less `fitmodeoverride` hidden
  input by its form-scoped name, so Fit no longer gets overwritten and resize no
  longer exits before the first pointer move. Existing edit-image, crop and
  undo/redo paths remain unchanged.

#### Validation

- Registered `IMG-06` for the single leased Moodle 5.1 add-image flow:
  pointer resize, side controls, Fit, save/reopen and cancel, with crop/drag/
  undo/redo non-regression observations. Static source and generated-AMD
  validation is required before supervised visual review.
- Added the local-supervised exact-one `IMG-06` Playwright runner and disposable
  fixture. It uploads Moodle's deterministic PNG, records before/Fit/resize
  geometry, click and pointer delivery, long tasks, screenshots and a trace,
  then removes the draft/category and releases the shared fixture lease in its
  cleanup boundary.

### 2026-08-15

#### Fixed

- Centred the selected-source empty layer-list state and added vertical space
  after an Async deletion, while retaining the shared EasyEdu Kit surface.

### EED-CCB-2026-0007 - 2026-08-14

#### Fixed

- Consolidated General settings into one compact Kit panel, corrected the
  destructive-button contrast and targeted Moodle's actual `fp-btn-choose`
  control so its lower spacing remains visible.
- Applied the same Settings panel stylesheet and single-panel framing on
  Moodle's CCB category route used from Site administration.
- Aligned the Settings save action with the panel's right edge on both the
  category and direct Settings routes.

#### Validation

- Added the single opt-in `ccb-settings-transfer-parity` Playwright scenario
  for read-only Settings and Transfer layout checks, including a guard against
  configuration-changing requests.
- Made its destructive-option assertion follow Moodle's rendered `.fitem`
  wrapper when no generated `fitem_id_replaceall` ID is present.
- Made the alignment measurement target the visible `replaceall` checkbox
  rather than Moodle's preceding hidden fallback input.
- Allowed the read-only scenario to wait for Moodle's asynchronous filepicker
  initialization without weakening its visibility or spacing assertions.
- Reconciled the seven-file Settings and Transfer payload on cumulative base
  `6745ca9` while preserving later scenario, protocol and generated CSS changes.

### 2026-08-13

#### Fixed

- Kept the visual editor's selected-layer, selected-layers and all-layers
  deletion flows on the current page. Confirmed deletions now show the shared
  `Loading in progress` feedback, replace only the selected-source region,
  announce a Moodle success/error toast and restore a useful keyboard focus.
- Restricted the existing JSON deletion paths to POST requests while
  preserving the system management capability and Moodle sesskey checks.

### Guide CCB advanced - 2026-08-12

#### Fixed

- Preserve CCB's conditional Guide contracts while adding responsive target
  selection and ordered `showopen` / `showafteropen` disclosure actions.
- Keep the Guide dialog above the compact Navigation panel and retain its
  safe-area padding without replacing CCB's advanced checklist, highlight or
  persistence behavior.

#### Validation

- Add the focused GUIDE-05 browser scenario for ordered disclosure opening,
  target highlighting and a usable return panel on the managed Moodle 5.1
  preview.

### 2026-08-11

#### Fixed

- Moved the global Course Banner Builder configuration reset out of Course,
  Site, Slideshow and Transfer pages into the general plugin settings, where a
  Moodle confirmation, capability and sesskey gate the existing destructive
  operation.
- Made Transfer use theme checkbox classes in both columns, aligned checkbox
  controls, visually distinguished the destructive import option and added
  breathing room around the ZIP file selector.
- Restyled the general Course Banner Builder settings with the embedded Kit's
  compact identity, setting panels, multi-select treatment and destructive
  action surface without changing Moodle setting behaviour.
- Restored proportional Moodle activity icons in the large Slideshow editor
  preview. The Course Forum label now keeps the same icon-to-text scale as the
  corresponding public Course banner; the label-colour reference samples are
  intentionally unaffected.
- Replaced the Slideshow administration's static Font Awesome label samples
  with the same native Moodle Forum, Assignment and Quiz monologos used by
  the public banner. Site announcements intentionally reuse the Forum
  monologo because they are Forum posts.
- Kept native Moodle label icons legible in the large Slideshow administration
  preview when its responsive label text becomes smaller; public-banner icon
  proportions are unchanged.

### 2026-08-10

#### Fixed

- Aligned the Slideshow administration preview label with the public Moodle
  native Forum monologo, including the shared activity-icon dimensions.

- Restored the single framed desktop navigation rail shared with EasyStud.
  CCB destinations now use plain glyphs and labels inside that rail, without
  individual icon capsules; the compact panel remains unchanged.
- Scoped responsive destination tiles exclusively to the compact panel and
  delegated desktop hover, focus and active motion directly to the EasyStud
  `admin-primary-nav-action` contract.

#### Changed

- Reordered the Slideshow administration identity to match EasyStud Mass Group
  Import: the EasyStud mark, page title and concise description now precede
  Navigation. The loading shell reserves the same header sequence and heights,
  preventing a header jump during the Skeleton-to-page handoff.
- Consumed the immutable UI Kit Navigation Skeleton primitive from
  `c9277a82fb471018f4cc07b24dd336d2adfa310d`: the CCB Navigation frame stays
  static while only its decorative cues shimmer.

#### Documentation

- Documented the CCB-only page-identity boundary, the Mass Import parity
  reference and the immutable UI Kit snapshot used by this consumer.

### 2026-08-09

#### Fixed

- Corrected the Slideshow administration Skeleton to render before the live
  page instead of flashing after it: CCB now follows the proven EasyStud
  server-first lifecycle, keeps the Skeleton visible through initial visual
  settling, performs an ordered fade, retains a bounded fail-open and restores
  the historical bottom-end `Loading in progress` indicator.
- Aligned the Slideshow placeholder treatment with the Student Management
  reference: stable pale surfaces now receive the same 108-degree overlay
  sweep, direction, two-second cycle and soft/highlight contrast as EasyStud,
  while the already accepted CCB page geometry remains unchanged.
- Kept large Navigation and Slideshow-preview placeholders as the same pale,
  static EasyStud panels and moved the shimmer onto their internal content
  cues. This preserves the approved CCB geometry while avoiding the large-area
  repaints that made the otherwise identical loop appear jerky.

#### Documentation

- Recorded the EasyStud/UI Kit loading audit, the missing consumer primitives
  and the exact CCB-owned readiness boundary for future Skeleton consumers.
- Recorded the visual-parity drift found between EasyStud, the published UI
  Kit and its CCB consumer so the shared theme can be corrected at its source.

### 2026-08-08

#### Added

- Added the fail-open Slideshow administration page Skeleton: an accessible,
  local loading shell with a dedicated AMD lifecycle, stable navigation and
  card placeholders, reduced-motion and forced-colors handling, and a future
  lease-gated validation scenario. It does not alter Slideshow settings,
  previews or public rendering.

#### Changed

- Replaced the temporary CCB-only desktop Navigation override with the shared
  EasyEdu UI Kit flat-rail primitive. Course, Site, Slideshow and Transfer now
  consume the same transparent desktop destination states, while compact
  Navigation keeps its tile buttons and the Guide launcher remains distinct.
- Restored keyboard focus to the visible Guide opener when the CCB Guide closes
  normally or with Escape, while preserving intentional focus handoff for
  `Show in interface` and guided-path starts.
- Corrected the CCB consumer Navigation rail so Course format and Site format
  are first-class rail entries rather than a second legacy bar beneath it.
- Restored the accepted EasyStud-style visual behavior in the CCB adapter:
  plain fixed-slot rail icons, a non-shifting desktop Guide label capsule,
  unclipped Guide shadow, and the left-centred responsive half-pill trigger.
- Made the compact Guide launcher one full-width gradient row with a single
  icon-and-label target, including its inverse hover/focus treatment.
- Removed the redundant Guide hover popover while retaining its animated label
  and accessible name, preserved plain desktop icons, restored the EasyStud
  neutral/active icon treatment inside the compact panel, and fixed the compact
  half-pill at the shared 44px touch target instead of an unresolved
  token-dependent size.
- Made the responsive Navigation handle's expanded width consumer-configurable
  so the complete localized Course Banner Builder label remains visible.

#### Fixed

- At responsive widths only, returned the `Selected source` **Deselect**
  action to its intrinsic, centre-aligned size while capping it to the sticky
  tray. This keeps the action contained and usable at 320 px and 390 px
  without changing desktop geometry or source-selection behaviour.

#### Tests

- Added the 320 px mobile cell to the supervised Selected source responsive
  matrix, alongside its existing 390 px and desktop cells.

#### Documentation

- Recorded the planned relocation of the destructive all-settings reset into a
  dedicated general Plugin Settings danger zone; the existing secure action is
  unchanged in this Navigation correction.
- Recorded the vendorable Navigation rule for plain desktop icons, EasyStud
  compact icon states, a fixed 44px trigger with configurable expanded width
  and a single Guide help label.

### 2026-08-07

#### Changed

- Replaced the duplicated Course Banner Builder administration destination
  buttons on Course, Site, Slideshow and Transfer with the shared EasyEdu
  Navigation rail, while retaining product-specific format and reset actions.
- Added the CCB Navigation/Guide adapter: the existing CCB Guide launcher is
  projected into the desktop and compact rail and its modal root is portalled
  above the transformed compact panel.
- Added localised labels and a CCB-owned server context so visibility, URLs,
  labels, icons and active state stay product-owned rather than being inferred
  by client-side code.
- Added only the five public Responsive helpers required by the immutable
  Navigation SCSS after the CCB embedded copy was found to predate them; no
  unrelated Responsive, Focus or Loading component was imported.

#### Documentation

- Recorded the CCB adaptation boundary, source snapshot and required manual
  Navigation/Guide validation in the local Navigation contract.

### 2026-08-06

#### Fixed

- Kept the Slideshow administration-preview action button legible and usable at
  tablet and mobile widths with a 96 px by 36 px editor-only minimum. Existing
  public-banner action dimensions and stored style choices remain unchanged.
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

- Closed the local Sources/Layers K.3 validation with the single supervised
  Moodle 5.1 `ccb-layer-object-row` scenario. It passed at 100% and genuine
  native 200%, including the final image-layer modal assertion: the obsolete
  sizing select is absent while any legacy `fitmodeoverride` field remains
  hidden for compatibility. Fixture, temporary elements, profile and runtime
  environment cleanup completed; the prior human visual review is accepted.

### 2026-08-04

#### Changed

- Hardened the local modal-action-rail supervisor so it drains Playwright
  stdout and stderr asynchronously, preventing verbose failures from filling
  Windows redirected-output pipes and stalling until the watchdog.
- Added bounded shared-runtime lease waiting to the Sources/Layers object-row
  supervisor; it now serializes its fixture work with other CCB browser runs.

- Stabilised the Selected source sticky tray with one AMD-owned state machine,
  a final-layout placeholder scroll sentinel and a CSS fallback used only when
  JavaScript is unavailable. The tray now returns cleanly from full-width
  portal mode to its original source position without reacting to its own
  layout changes.

- Kept the Sources/Layers leading rail at one constant width for idle, hover,
  focus and native-drag states. The Image type icon and six-point drag handle
  now occupy the same centered 1rem box, while table cells use only one light
  bottom separator.
- Replaced the layer-details local help trigger with the established
  Slideshow-style, body-portalled help component. It remains available while
  the disclosure is closed and now shares the compact centred body, padding,
  normal text weight and trigger arrow with the `Sort order` table-header help.
  Read-only label/value pairs retain their additional spacing.
- Rebalanced the eight configured-source columns after removing the sizing
  column, reserving 20% for Actions without changing action hooks or values.
- Constrained every inner Font Awesome type icon to the same centered 1rem
  rail box as the six-point handle. The hover/focus swap retains its fuller
  motion while reduced-motion users retain an immediate transition.
- Gave locked Overlay rows a distinct low-contrast petrol-teal stripe and
  aligned each layer metadata label left with its value right.
- Removed the remaining editable `Image sizing mode` row from the Selected
  source summary. Its persisted value and hidden source-settings submit field
  remain intact for compatibility.

#### Tests

- `Test-CCBAsyncProcessDrain.ps1` passed with 1 MiB on each output stream;
  it uses neither Moodle nor a browser.
- The one-test Moodle 5.1 Sources/Layers object-row scenario passed at 100%
  and native 200%, with complete fixture and profile cleanup.

- The one-spec supervised Moodle 5.1 responsive scenario passed all five
  100%/native-200% cells, including repeated sticky entry/release oscillations,
  full-width portal geometry, normal-flow restoration, overflow, console and
  request checks. Disposable fixture, profile and process-local environment
  cleanup completed. Human visual acceptance of scrolling was recorded on
  2026-08-05.

- The single supervised Moodle 5.1 `ccb-layer-object-row` scenario passed at
  100% and genuine native 200%. It covered the centred image/handle swap,
  constant rail, petrol-teal Overlay stripes, both closed-disclosure and
  `Sort order` help controls, their actual `document.body` portal, compact
  centred normal-weight/padded help body and trigger arrow, left/right metadata
  layout, absence of every visible sizing control, source-action layout,
  native drag and keyboard ordering, overflow, console/request checks and
  complete disposable-fixture/profile cleanup. Human visual review is still
  required before K.3 is accepted.

### 2026-08-03

#### Changed

- Refined the CCB Sources/Layers row surface with aligned low-contrast locked
  stripes, a light row separator, a wider semantic rail and white type icons.
  Reorderable images retain their image icon at rest and reveal the six-point
  drag handle on hover or keyboard focus.
- Redesigned `Layer infos & overrides` as a fully aligned metadata disclosure
  with a keyboard-accessible contextual-help control. It no longer exposes the
  image sizing control.
- Removed the visible image sizing mode controls from source settings and
  Sources/Layers summaries while preserving submitted and persisted sizing
  values through the existing hidden field and data model.
- Aligned the local layer-modal action rail with the top edge of its preview
  frame; header and footer boundaries remain unchanged.

#### Tests

- The supervised Moodle 5.1 Sources/Layers scenario passed at 100% and genuine
  native 200%, covering rail/stripe continuity, image-icon to drag-handle
  transition, source-sizing-control absence, disclosure/help behavior, drag and
  keyboard ordering, overflow, console/request checks and fixture/profile
  cleanup. The final modal-form assertion is pending the release of the shared
  Moodle runtime; human visual review remains pending.

### 2026-08-02

#### Changed

- Harmonised locked source-layer rows into one lighter continuous surface,
  keeping the Overlay sample unstriped and removing the legacy competing rail.
- Moved ordering-lock status into the sort-order cell; locked rows now show a
  layer-type icon while reorderable image rows show the shared six-point grip.
- Applied the embedded EasyEdu selection-checkbox contract to CCB table
  selectors, title-modal controls and native CCB mforms without changing
  existing toggle switches.

#### Tests

- The focused Moodle 5.1 layer-row test passed at 100% and genuine native
  200%, covering locked-row continuity, Overlay sample isolation, layer-type
  identity, drag/keyboard ordering, selection controls, no overflow,
  console/request checks and complete fixture/profile cleanup.

### 2026-08-01

#### Changed

- Centered the transient layer-modal loading status within the available modal
  body. The empty loading body now uses its full flex surface and does not
  reserve space for an action rail that has not yet been rendered.
- Restored the visible top edge of expanded Image, Border and Overlay side
  panels. Their trigger and panel now remain distinct rounded surfaces instead
  of overlapping into a single borderless control.
- Refined CCB source-layer drag rows to use the shared six-point handle and a
  single leading-cell rail. Reorderable rows now have a restrained whole-row
  hover/focus state; locked Border and Overlay rows retain distinct striped
  states without changing native ordering or keyboard controls.

#### Tests

- The isolated Moodle 5.1 layer-modal scenario delayed one real Overlay-modal
  response and measured a centered loading spinner (0px horizontally and
  1.59px vertically from the modal-body centre), then passed the full
  Image/Border/Overlay responsive matrix with complete cleanup.
- The same isolated matrix proved each expanded side panel has a solid visible
  top border and a 2.875px trigger-to-panel gap, with no overflow, console or
  request failures at every 100% and native-200% cell.
- The one-test Moodle 5.1 layer-row scenario passed at 1600x900 at 100% and
  genuine native 200%, including two reorderable and three locked rows, native
  drag/keyboard ordering, no overflow, no console/request failure and complete
  fixture/profile cleanup.

### 2026-07-31

#### Changed

- Integrated the additive EasyEdu `object-row-cells` primitive for CCB layer
  table rows. Existing native drag/order mechanics and CCB subtype colours stay
  authoritative while hover, focus-within, drag-source, locked,
  reduced-motion and forced-colors paint states now share the Kit contract.
- Stabilised the selected-source sticky tray at its scroll threshold with a
  small entry/release band, preventing rapid portal/restore transitions when
  the holder crosses the Moodle navigation edge.
- Reserved the live Moodle right block-drawer toggle footprint for the desktop
  `Deselect` action, measured at runtime rather than hard-coded; narrow mobile
  layouts retain their separate action row.
- Unified the CCB layer-modal action-rail width with the content space it
  reserves, preventing a desktop action rail from widening over preview/form
  content. Small modals retain their full-width, normal-flow action panel, and
  native-zoom micro-viewports use their available modal surface rather than
  clipping action labels with the dense source-list ellipsis.
- Harmonised CCB layer-list actions with the source-list icon/label contract:
  each action now has a stable leading icon, a centered readable label and a
  mirrored trailing slot without changing links, confirmations or layer
  ordering mechanics.
- Harmonised CCB contextual question controls with the embedded EasyEdu Kit:
  table and layer override help now use one focusable help icon and the same
  positioned popover surface, without duplicating native browser tooltips.
- Replaced the dense inline layer sizing dropdown with a closed-by-default,
  keyboard-operable details accordion. It keeps the active sizing mode visible,
  moves read-only layer metadata into the same table cell and leaves full
  sizing edits in the layer modal.
- Aligned the visible Image, Border and Overlay modal-disclosure chevrons with
  the CCB accordion convention: the state affordance now precedes the type
  icon and label, while the existing accessible side-panel button, `data-*`
  hooks and native disclosure markup remain unchanged.
- Fixed the add-layer crop regression where Apply, action-rail Undo, then save
  could submit the prior cropped state. The restored draft now updates the
  canonical Moodle crop fields and `previewcropstate` payload before submit.

#### Tests

- Regenerated the affected AMD module with the documented Terser fallback;
  JavaScript syntax checks and `git diff --check` pass.
- Compiled the modal action-rail SCSS and ran its one-test supervised Moodle
  5.1 matrix: 1600x900, 1024x768 and 390x844 at 100%, plus 1600x900 and
  390x844 at genuine native 200%. Image, Border and Overlay modals passed
  rail/form containment, readable action labels, keyboard disclosure and
  focus, no document overflow, console/request checks, and complete
  category/profile cleanup. External CDP evidence is retained by manifest.
- The one-test Moodle 5.1 crop check now covers Apply followed by action-rail
  Undo and save. It passes the restored preview, canonical crop fields,
  `multilayerdraftsettings`, submitted payload and persisted-layer assertions.
- Added strict `-CellId` resume support to the responsive supervisor. A
  navigation-only desktop 200% interruption can now be resumed without
  repeating the other approved cells; that native-zoom page navigation allows
  60 seconds while all ordinary cells retain their 20-second threshold.
- The initial corrected matrix passed desktop/tablet/mobile 100% and mobile
  200%; its desktop 200% navigation timed out before CCB assertions. The
  targeted desktop 200% resume then passed with no drawer overlap, no
  console/request failures and complete fixture/profile cleanup. Evidence:
  `C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\responsive\supervised\ccb-source-preview-responsive-20260731T113428645Z-35944`.
- Revalidated the CCB general source-preview panels through the same isolated
  five-cell Moodle 5.1 matrix. The action rail filled its available grid
  column; primary actions stayed contained, ordered and non-clipped through
  Desktop authoring to Mobile public simulation and back; no filmstrip or
  visibility-row overlap, console/request error, or cleanup residue remained.
- The isolated one-test CCB layer-object-row scenario passed at 100% and
  genuine native 200% after asserting each layer action's left icon, centered
  label, no clipping and keyboard/drag preservation. Fixture/profile cleanup,
  console and request checks all passed. The same scenario then proved the
  corresponding configured-source Preview, Edit and destructive actions at
  both zoom levels.
- The same isolated scenario passed after focusing the layer override question
  at 100% and genuine native 200%, asserting its Kit popover, accessible
  tooltip linkage, no overflow, no console/request failure and full cleanup.
- The same isolated scenario passed after opening the layer details accordion
  with Enter at 100% and genuine native 200%, asserting the active sizing mode,
  readable metadata, no root overflow, no console/request failure and cleanup.

### 2026-07-30

#### Changed

- Synchronized the compatible Moodle 5.1 CCB primitives with the approved UI
  Kit focus system: one `0.18rem` geometry, semantic focus colours, preserved
  elevation and a forced-colors outline fallback. Product selectors and CCB's
  older embedded component boundaries remain unchanged.
- Harmonised the CCB source-selection surfaces: long dropdown labels now wrap
  inside a protected label slot, used-source markers keep a fixed icon slot,
  source tables retain rounded corners, and the selected-source holder has a
  native sticky fallback behind its drawer-aware runtime positioning.
- Unified the three layer-form accordion shells with a left chevron, explicit
  `aria-expanded`/`aria-controls`, and cancellable reduced-motion-aware
  disclosure animation without changing the form field names or modal hooks.
- Corrected the source-preview responsive assertion so mobile simulation is
  validated against containment in its owning preview surface and its logical
  390px width cap, rather than an arbitrary ratio against the full desktop
  editor root.
- Added a localized, dismissible orientation suggestion to the CCB source
  editor for narrow portrait viewports; it remains hidden on public pages,
  read-only source-chain previews, wide screens and landscape orientation.
- Shortened the supervised Batch 2F-B.1 artifact root so nested Playwright
  evidence stays within Windows path limits during manifest registration.
- Rebuilt `styles.css` with the documented Sass 1.89.2 script after the focus
  synchronization. The build passed; only the repository's existing Sass
  `@import` deprecation warnings remain.
- Added an idempotent readable SVG fixture helper for the Moodle 5.1 course
  source, preserving existing layers for visual banner review.
- Re-ran the isolated CCB source-preview responsive matrix across all five
  approved viewport/zoom cells; automated containment passed, and the initial
  run identified the 200% selected-source sticky tray as a visual correction
  target.
- Corrected the selected-source sticky tray at native 200% by removing its
  fixed horizontal offset and stacking the title/action on narrow widths;
  added post-capture containment evidence for the tray and `Deselect` action.
- Corrected the selected-source sticky tray vertically: nested Moodle scrolling
  now promotes it at the navigation edge to the physical viewport top, while
  preserving the drawer-aware width and the narrow mobile action stack.
- Updated the selected-source sticky runtime to track Moodle's actual nested
  scroll container, restore the holder at its original source position when
  scrolling back up, use the full viewport width while floating, and remain
  above the fixed navigation layer. Responsive mobile text remains centered.
- Restored the native CCB token scope on the body-level selected-source portal,
  so its floating tray retains an opaque surface, border and readable hierarchy
  instead of rendering transparently over the editor.

#### Documentation

- Documented the orientation-hint scope, responsive contract and isolated
  validation procedure in the CCB UI harmonisation records.

#### Tests

- The isolated orientation-hint scenario selected exactly one Playwright test
  and passed at 100% and genuine 200% native zoom: portrait visibility,
  keyboard dismissal, landscape/wide/public hiding, page overflow checks,
  console/request checks and fixture/profile cleanup all passed. The local
  Moodle language cache was refreshed under the runtime lease before the
  final run; visible CDP captures are pinned in the external artifact
  manifest.
- The isolated `fullwidthtopinset` desktop 200% run reached the mobile-mode
  assertion and exposed the obsolete root-width ratio (`0.6377` versus `0.70`),
  with no console or request errors. After correction, the same single-cell
  rerun passed with native 200% zoom, no overflow/overlap, no console/request
  errors and complete fixture/profile cleanup; the subsequent supervised
  five-cell rerun passed and closed the responsive gate.
- Rebuilt `amd/build/admin_manage.min.js` and its source map from the current
  `amd/src/admin_manage.js` using the documented Terser command; both generated
  files passed syntax/marker checks. The subsequent single-test responsive
  attempt stopped before fixture mutation because the shared Moodle lease was
  owned by a live EasyStud/UI Kit process; no CCB cell or browser capture ran.
- Corrected the floating selected-source width calculation to use the actual
  document viewport client width instead of `window.innerWidth`, preventing an
  8px scrollbar overrun in genuine native 200% cells.
- Refined the responsive test contract so the selected-source sticky assertions
  apply only after the holder is actually materialised in the viewport; an
  offscreen normal-flow holder at native 200% is no longer reported as a
  clipping failure.
- Implemented the selected-source sticky portal: when its natural threshold is
  crossed, the holder moves to a body-level fixed portal below Moodle
  navigation, spans the usable viewport and leaves a height-preserving
  placeholder; scrolling above the threshold restores the original DOM slot.
  Narrow responsive layouts centre the selected-source text. The supervised
  five-cell Moodle 5.1 run passed at 100% and genuine native 200% with full
  fixture/profile cleanup; evidence is retained in the external artifact
  directory documented in `docs/testing/ccb-ui-harmonisation.md`.
- Strengthened that responsive scenario to require a non-transparent sticky
  background and non-zero border. The corrected five-cell Moodle 5.1 rerun
  passed with complete fixture/profile cleanup.

### 2026-07-29

#### Changed

- Isolated the responsive geometry harness's administration preview modal in a
  fresh Playwright page so pending Moodle background requests from the public
  surface cannot block the real source-preview fetch; the modal frame and
  layer assertions remain unchanged.
- Wrapped source-preview visibility actions inside the owning surface at narrow
  effective viewports, including genuine 200% browser zoom.
- Let the source-preview surface reclaim the narrow viewport width at genuine
  200% zoom, keeping the server-provided `fullwidthtopinset` ratios unchanged.
- Made source-preview CDP captures scroll the real banner frame into the
  viewport and record the capture scroll state, so visual evidence cannot
  accidentally show an unrelated page region or only the tall mode panel.

### 2026-07-28

#### Changed

- Aligned administration preview format modifiers with the selected
  non-standard banner ratio by matching the native fallback specificity and
  clearing its minimum-height clamp; the standard 4:1 fallback remains intact.
- Materialized the CCB source-preview canvas, mode switcher, action group and
  control rail as bounded cards. Primary actions stay on one row when the
  available width permits it and reflow vertically below the responsive
  breakpoint without changing AMD hooks, data-action attributes, banner
  geometry, or the 128px policy.
- Prepared the first CCB UI harmonisation source patch: the mobile simulation
  keeps the filmstrip and visibility row independent from the logical canvas
  width, modal/slideshow action lists use a bounded responsive container with
  a shared icon/label alignment contract, and configured-source actions now
  expose an explicit label slot. The generated CSS was rebuilt from the
  current SCSS source; browser validation remains pending the controlled Gate 2
  validation window.

#### Documentation

- Synchronized the local agent contract with the shared branch/runtime handoff
  and response-routing procedure. Future windows must report the recommended
  next Codex model and task, including for corrections and blocked tests.
- Added the plan/state continuity and reusable Playwright scenario rules to the
  local agent contract for future Docker/CI visual regression work.
- Added the shared development-plan reference and scenario lifecycle guidance;
  valuable Playwright source is retained while generated media stays external.
- Added the portable EasyEdu documentation contract to the plugin instructions,
  covering technical documentation, changelog grouping, AI contracts, batch
  evidence, validation reporting and multi-machine preservation rules.
- Added the shared Playwright visual-artifact retention rule to the agent
  instructions: manifests are required for deletion, raw captures stay outside
  Git/Syncthing and legacy unmanifested media is inventory-only.
- CCB supervised Playwright runners now register completed visual artifacts
  automatically with the shared EasyEdu retention manifest.
- Added the shared lease-wait handoff rule to the agent instructions so a
  parallel Playwright window can wait with a bounded timeout instead of
  interrupting an active CCB run.
- Adopted the environment portability and inventory gate: machine, checkout,
  volume, Git identity and runtime dependencies are recorded before action;
  fixed-root legacy examples are reported rather than moved or deleted.
- Added the CCB UI harmonisation agent plan and ownership boundary. The next
  CCB-only audit covers preview-mode isolation from the filmstrip, available
  width for side action panels, consistent icon/label slots and heights across
  preview/modal/slideshow/source-list actions, and keyboard-safe controls;
  EasyStud, the EasyEdu UI Kit and Moodle/Boost remain separate handoffs.
- Added the functional validation procedure for the Gate 2 UI harmonisation
  matrix, lease/discovery contract, external artifact handling and five
  responsive/zoom cells. It records the source/build checks already run and
  keeps browser validation explicitly pending.

#### Tests

- Recorded the isolated Batch 2A.2 contentwide/tablet diagnostic and its
  follow-up acceptance evidence; the initial 4:1 administration ratio drift
  was corrected and the single rerun now measures the required 5:1 ratio on
  both preview and public surfaces, with reversible cleanup.
- Executed the focused CCB source-preview responsive matrix at 1600x900,
  1024x768 and 390x844 at 100%, plus 1600x900 and 390x844 at genuine native
  200% zoom as one sequential, one-test supervised run. The 390x844 100%,
  1600x900 200% and 390x844 200% cells passed; the desktop 100% cell failed
  during login/navigation and the tablet 100% cell timed out in `page.goto`,
  before CCB geometry assertions. Cleanup restored the course/category/format
  and removed profiles; the browser gate remains blocked pending a runtime
  navigation diagnosis. Moodle/Boost and EasyEdu UI Kit administrative menus
  remained outside this CCB validation scope.
- Correlated the Gate 2 desktop/tablet failures with read-only local Apache
  logs: Moodle returned successful 200/303 responses only after the protected
  Playwright navigation budgets, while the PHP log showed existing warnings and
  notices but no observed fatal response. The gate remains blocked pending a
  local runtime remediation decision; no timeout, Moodle, AMD or CCB
  integration source was changed.

### 2026-07-29

#### Documentation

- Documented the narrowly scoped source-preview inline geometry exception:
  only computed `--local-course-banner-builder-source-preview-*` properties
  are permitted, with matching data attributes and SCSS fallbacks. This does
  not relax the general prohibition on arbitrary inline UI styling.

#### Tests

- Re-ran the Gate 2 five-cell matrix once after the runtime became available:
  desktop/tablet/mobile 100% and mobile native 200% passed; desktop native
  200% failed before geometry assertions because `/my/` returned HTTP 500.
  Apache/PHP recorded concurrent GroupImport requests and cache-store rename
  `Access denied` warnings while the CCB lease was active. The gate is now
  explicitly blocked on global Moodle runtime lease exclusivity; no timeout,
  cache, GroupImport or CCB source was changed.

### 2026-07-27

#### Tests

- Validated the CCB Batch 2F-B.1 narrow public-title contract at genuine 200%
  native Chrome zoom, including H1/H2 accessibility, overflow, keyboard, and
  reversible fixture cleanup evidence. The validation used external artifacts
  and isolated profiles; no production banner behavior changed.
- Added a shared DPAPI-backed Moodle 5.1 credential loader and Playwright
  wrapper so later CCB specs can reuse the approved local credentials without
  prompting again or creating global password environment variables. Updated
  the accessibility and functional protocols to use the supervised runners.
- Extended the fixture-aware supervised runner to validate 2F-A.1 at 100%
  native zoom through the same disposable category and guaranteed cleanup
  path as 2F-B.1. Batch selection is explicit and discovery-only remains
  mutation-free.
- Validated 2F-A.1 end to end with one passing Playwright test, complete
  Moodle restoration, disposable-category removal, profile cleanup, and a
  passing external secret scan. Fixed the supervisor's approved nested profile
  root check so a successful child cannot be reported as a cleanup failure.

### 2026-07-22

#### Changed

- Corrected the public banner title accessibility contract: Moodle's existing
  page `h1` remains the single primary heading when a course title is visually
  replaced, decorative banner layers stay hidden individually, and distinct
  activity/site context titles use a secondary heading without altering title
  styling, geometry, responsive sizing, cards, or administration previews.
- Added a transient **Desktop authoring / Mobile public simulation** switch to
  the selected source visual editor and its read-only source-chain preview.
  The mobile surface uses a logical 390px width and the existing format ratio
  plus public non-standard floor/cap policy, without saving a setting or
  changing public runtime CSS.
- Restored the approved public native-banner responsive minimum at 128px for
  `contentwide`, `fullwidthtop`, `fullwidthtopcompact` and
  `fullwidthtopinset` in both the post-theme runtime stylesheet and compiled
  SCSS fallback. The 128px floor comes from the previously measured responsive
  policy matrix; existing ratios and maximum heights remain unchanged, and the
  `standard` 4:1 base rule is unchanged.

#### Documentation

- Documented public banner title ownership, decorative-layer treatment,
  future-control mounting, focused accessibility-tree coverage, and the manual
  NVDA/200% public-banner protocol; Moodle 4.5 remains static-review only.
- Documented the dual public-CSS authority, course/site scope, focused browser
  measurement contract, separate accessibility defect and outstanding mobile
  administration-preview work.

#### Tests

- Added the opt-in Batch 2F-A Playwright scenario for authenticated public
  heading snapshots, title replacement, keyboard control semantics, geometry
  preservation, and reversible course/title-settings cleanup on D:.
- Added the opt-in Batch 2E-B Playwright matrix for the selected-source
  desktop/mobile simulation. It keeps profiles and evidence on D:, restores
  only the existing format mutation, redacts session keys, and records the
  pre-existing configured-source table overflow as a non-regression baseline.

### 2026-07-21

#### Changed

- Added the pure, deterministic CCB banner geometry contract and focused
  PHPUnit coverage as a foundation for later rendering harmonisation. It is
  now used by public course-header HTML image overlays through a compatibility
  adapter; generated backgrounds and all other renderers remain unchanged.
- Aligned the administration source-preview frame with its existing selected
  banner-format modifier, including source-chain modal previews outside the
  native administration wrapper. The public overlay adapter and canonical
  geometry contract remain unchanged.
- Restored keyboard-accessible contextual help for Sources/Layers controls:
  focused popover triggers retain focus, expose their described tooltip, and
  close it only after focus leaves the control.
- Increased narrow-screen Sources/Layers action targets to 44px without
  changing table, drag/drop, source-chain, or modal interaction hooks.
- Restored the enabled state of the non-drag layer-order controls after a
  local preview layer is selected from the filmstrip.

#### Documentation

- Documented canonical coordinates, supported format ratios, rendering
  policies, thumbnail adaptations, rounding, and the renderer migration order.
- Recorded the Batch 2C preview-frame ownership, independent-cell DOM
  geometry command, and the separate open localisation issue for the outdated
  4:1 `fullwidthtop` help text.
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
