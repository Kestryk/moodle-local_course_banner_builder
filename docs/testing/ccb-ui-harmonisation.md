# CCB UI harmonisation validation

Status: `GATE_2_OVERLAP_DETECTED`

This procedure validates the CCB-only action contract introduced by Gate 2. It
does not validate EasyStud, the EasyEdu UI Kit, or Moodle/Boost administrative
menus.

## Scope

- desktop/mobile mode changes must not resize or reset the filmstrip,
  visibility row, selected layer, or action focus;
- modal and slideshow action panels must use their available container width;
- labelled actions must share one icon slot, label slot, height and state
  alignment;
- configured-source actions must keep icons and labels leading-aligned;
- no preview, document or modal horizontal overflow is allowed.

## EED-CCB-2026-0050 RF8 / EED-CCB-2026-0044 RF1 - 2026-08-31

This corrective source lot closes the two remaining defects reported on the
Wave 6 preview without reopening the accepted Border/Overlay or layer-detail
layout work:

- unlocked Image rows have one background owner for every direct cell. Layer
  infos & overrides no longer carries an `!important` rest/hover/focus copy,
  so Boost or another compatible Moodle theme cannot update the row while
  leaving that single cell on the former colour;
- the Layer infos disclosure's animated wrapper has no vertical padding. Its
  children retain the accepted visual spacing, while the wrapper can now move
  continuously to a real zero-height closing frame.

The shared CCB Motion controller, interruption handling and reduced-motion
policy are intentionally unchanged. Locked Border and Overlay rows retain the
existing semantic stripes and tints. The focused source contract remains
`tools/test-ccb-layer-panel-rf-contract.ps1`; it now rejects a reintroduced
Image-cell-specific surface and checks the zero-padding animation boundary in
both Sass source and generated CSS.

## EED-CCB-2026-0050 RF7 / EED-CCB-2026-0052 RF2 - 2026-08-31

This corrective source lot preserves the accepted RF6 contracts and closes two
remaining presentation defects:

- On an unlocked Image layer row, the Layer infos & overrides cell must use
  exactly the same surface as the row at rest, hover and keyboard focus. The
  Border and Overlay locked-row semantic tints remain visible.
- In Course and Site Banner Manager Source Preview, the right-hand panel shell
  must extend to the same bottom edge as the general preview canvas. Its action
  buttons keep the existing fixed height and the compact preview-to-panel gap.

The source contract is `tools/test-ccb-layer-panel-rf-contract.ps1`. It checks
the bounded disclosure columns, the fixed action height, the grid stretch
relationship, removal of the generic unlocked-Image tint, and the generated
CSS artifact. The contract is source/build evidence only: no runtime, cache,
fixture, browser or managed-preview activity is claimed here.

## EED-CCB-2026-0046 - title-colour dialog - 2026-08-29

The Course, Site and Activity title editors use the same CCB-owned, Kit-shell colour dialog. Their visible swatch does not invoke the operating-system colour picker. The existing HEX input remains the persisted form control and the outer title-editor Save remains the sole persistence action.

Future lease-gated supervised validation must record these named captures outside Git: `01-0046-course-title-colour-dialog.png`, `02-0046-site-title-colour-dialog.png` and `03-0046-activity-title-colour-dialog.png`. Each capture must show the dialog before Apply. The focused flow proves temporary palette/HEX editing, atomic Apply into the existing preview/input binding, Cancel and Escape without form mutation, focus return to the exact swatch, and a narrow/zoom no-overflow cell. Source work only: no runtime, browser, preview, cache or lease action was performed.

## EED-CCB-2026-0042-0050 cumulative supervised scenario - 2026-08-29

`tools/playwright/ccb-wave-0042-0050-cumulative.spec.js` is selected only by
`tools/playwright/Invoke-CCBWave0042To0050Validation.ps1`. The supervisor
performs exactly-one-test discovery before reading credentials or mutating a
fixture. A non-discovery run then requires a clean managed `ccb-moodle51`
preview, an `ExpectedAppliedCommit` listed by the preview status, and exactly
one `moodle51-active-fixture-write` lease without retry. It creates a
deterministic, disposable three-category source chain: exactly three source
rows, two expandable ancestors, two parent pencils, and two reorderable plus
one dynamic locked image layer on the descendant source. It provides Moodle's
official `mod/workshop/tests/fixtures/moodlelogo.png` for IMG-08.

The watchdog is fixed to 900 seconds. The external run root receives the
discovery output, phase log, fixture/restoration manifest, child logs, result
and cleanup report. Cleanup runs after success, failure or timeout and removes
the exact three owned categories, their CCB elements, the three recorded user
draft areas, the run profile directory, process-local environment variables and
lease; the cleanup report records zero remaining categories/elements/drafts.
The wrapper must not be invoked until the preview is separately authorised; it
never purges caches or switches the runtime.

`ccb-moodle51`'s scenario allowlist is an official but machine-local Platform
profile at `%LOCALAPPDATA%\EasyEdu\orchestration\profiles\runtime-preview-profiles.json`;
it is deliberately not committed in CCB or changed directly by this batch.
Before a supervised run, apply this declarative addition through Platform's
`Initialize-EasyEduRuntimePreviewProfile.ps1` procedure while retaining every
existing allowed scenario:

```diff
 "allowedScenarios": [
   "<existing approved scenario>",
+  "ccb-wave-0042-0050-cumulative"
 ]
```

The exact commands are:

```powershell
Set-Location C:\dev\easyedu-platform
.\tools\orchestration\Initialize-EasyEduRuntimePreviewProfile.ps1 `
  -ProfileName ccb-moodle51 -ProjectNamespace ccb `
  -RuntimeRepository '<runtime repository from the existing ccb-moodle51 profile>' `
  -MoodleRoot '<moodleRoot from the existing ccb-moodle51 profile>' `
  -PhpExecutable '<phpExecutable from the existing ccb-moodle51 profile>' `
  -PreviewBranch '<previewBranch from the existing ccb-moodle51 profile>' `
  -AllowedScenario '<each existing approved scenario>','ccb-wave-0042-0050-cumulative'

Set-Location <candidate-plugin-worktree>\tools\playwright
.\Invoke-CCBWave0042To0050Validation.ps1 -DiscoveryOnly
.\Invoke-CCBWave0042To0050Validation.ps1 -ExpectedAppliedCommit <applied-sha>
```

Before sensitive assertions it records these named human captures outside Git:

- `01-0042-parent-list-before-sensitive.png`;
- `02-0042-parent-modal-before-sensitive.png`;
- `03-img08-<desktop|narrow>-<add|edit>-before-geometry.png`;
- `04-0044-motion-drag-before-sensitive.png`.
- `05-0046-course-title-colour-dialog.png`, `06-0046-site-title-colour-dialog.png` and
  `07-0046-activity-title-colour-dialog.png`;
- `08-0050-source-tree-before-sensitive.png`, `09-0050-preview-loading-before-sensitive.png`,
  `10-0050-preview-ready-before-sensitive.png` and `11-0050-preview-error-before-sensitive.png`.

The sequential flow covers the 0042 parent modal/list below/search/close/Save
icon/pencils/checkerboard/inherited rows/Edit source/Collapse all contract; the
IMG-08 add/edit crop geometry through Apply, Undo, Redo, reopen and draft
switch at desktop and narrow widths; and the 0044 canvas-only Desktop/Mobile
motion, reduced-motion, disclosure, drag ghost/placeholder/drop cleanup and
locked-row alternative. It additionally covers 0046 title-colour dialog
transactions for Course, Site and Activity (including Apply, Cancel, Escape,
focus return, native-picker absence and narrow/zoom overflow), plus the 0050
three-level source tree, reduced motion, Kit preview loading/ready/error,
footer-contained Edit source, canvas-only mode switch and preview close focus return.

## EED-CCB-2026-0044 - motion and draggable-layer parity - 2026-08-29

The source-preview root publishes `data-easyedu-motion-policy="enabled"` before
first paint. Desktop/Mobile changes use the vendored cancellable Motion runtime
on the canvas surface only: the filmstrip, visibility row and action controls
remain stationary. Reduced motion resolves immediately and shared scrolling is
never forced smooth.

`Layer infos & overrides` retains its existing semantic `details` disclosure;
the Motion runtime alone owns its measured opening and closing geometry.
Slideshow side panels no longer combine CSS geometry transitions with that same
controller.

Layer reordering remains an enhancement over the existing keyboard/button
alternatives. A movable row receives a Kit-style opaque lifted preview, its
source remains an in-flow placeholder, and valid before/after insertion targets
are explicit. Border, Overlay and inherited locked rows remain non-draggable.

The cumulative supervised scenario above covers normal and reduced motion,
repeated disclosure state and a complete drag cycle. It is source-ready only;
no browser or runtime evidence is claimed here.

## Cumulative visual correction wave (`0015-RF1`, `0035-RF2`, `0042`, `0023-RF3`) - 2026-08-29

The cumulative preview must prove the following visible contracts without
changing the Slideshow engine, source persistence or modal Crop transaction:

- the Slideshow page identity title uses `--easyedu-primary`, while its
  description and Navigation placement remain unchanged;
- the Parent column and Selected source parent summary each expose one compact
  pencil with an accessible source-specific label and open the same modal;
- each Source composition mode pencil opens the source-settings modal: directly
  from Selected source and, from a configured-source row, after that source is
  selected;
- the modal Save action is compact, a successful Selected source change
  refreshes both its summary and the configured-source table, and focus returns
  to the corresponding pencil;
- inherited Border and Overlay rows use the established semantic rail,
  low-contrast locked background, type and lock indicators, Layer overrides
  disclosure and compact source-edit action;
- Collapse all and Select all retain the same secondary-action contract;
- the add/edit layer preview uses one checkerboard surface without a competing
  outer frame; its frame, action rail and image geometry do not move.

CCB semantically adopts the published `EED-KIT-2026-0001` visual foundations
from UI Kit commit `6dec8785262d9b006feeb21ea313949ef8fac01c`: its neutral
checkerboard, exact modal-close, Save and edit-pencil primitives. No other Kit
source is copied into this consumer.

Static evidence for this consumer revision: Sass 1.89.2 rebuild, PHP lint of
the plugin, module-mode AMD parsing and `git diff --check`.
There is no AMD source change; the Moodle Grunt toolchain is unavailable in
this checkout, so existing generated AMD files remain untouched. No runtime,
preview, cache, lease or browser check is part of this evidence.

The image Crop/Recrop report is intentionally excluded from this visual patch.
A focused geometry probe must first compare the image layer rectangle before,
during and after Crop/Recrop; no product correction is allowed until that probe
demonstrates which rectangle changes unexpectedly.

## Primary accordion and inline Deselect polish (`EED-CCB-2026-0031-RF4`) - 2026-08-22

Selected source, Configured sources and Course Banner options retain their
native `details` and `summary` markup. CCB-local SCSS moves each existing
chevron ahead of its title with flex ordering only: it points down while
closed, rotates upward when open and honours reduced-motion preferences. No
second trigger is introduced.

The existing Settings button remains a real, keyboard-focusable secondary
button within Selected source. Its local solid compact treatment preserves the
summary's native click and focus target while keeping the header at the same
desktop height as the other primary accordions.

The responsive source-preview scenario now proves both explicit sticky states:
the body-portal `stuck` state has no added Deselect end margin, whereas the
existing `data-sticky-state="inline"` state supplies the small end clearance.
No layout rule changes the floating tray after it becomes sticky.

The primary-accordion scenario covers desktop and 390 px chevron placement for
all three headers, native keyboard open/close state, the chevron transition and
the unchanged Settings action contract. Browser proof and human review remain a
separately authorised final preview activity.

## Responsive Selected source containment (`EED-CCB-2026-0004`) - 2026-08-08

At `max-width: 48rem`, the CCB-local sticky action now keeps the leading source
description full-width while the **Deselect** link uses its intrinsic width,
is centred with `align-self: center`, and cannot exceed the tray width. The
rule applies to both native and body-portal sticky holders. Desktop rules,
the PHP source-selection markup and the AMD sticky lifecycle are unchanged.

The focused responsive scenario now records whether the visible Deselect link
is centred in the sticky tray and asserts it only below the desktop breakpoint.
Its supervised matrix includes 320 px, 390 px and desktop/tablet cells at
100%, plus its existing native-zoom coverage. Its lease-gated execution remains
pending; this WIP contains static source, generated-CSS and syntax validation
only, with no preview, cache or browser activity.

## Crop regression scope — 2026-08-01

The focused Moodle 5.1 validation now covers the reported user flow: upload
an image, change and apply the crop, use the modal action-rail Undo, then save
the layer. The restored active preview, canonical crop fields, visual draft
layer, `multilayerdraftsettings` and `previewcropstate` payload all return to
the uncropped `false, 0, 0, 100, 100` state before save; the persisted layer is
also uncropped after reload.

### Modal and draft placement preservation (`EED-CCB-2026-0043`) - 2026-08-29

Crop coordinates are source-relative data. In the add/edit image modal, a crop
gesture must therefore change only `imagecropenabled`,
`imagecropleftpercent`, `imagecroptoppercent`, `imagecropwidthpercent` and
`imagecropheightpercent`. It must not derive a new `fitmodeoverride`, custom
width/height or offsets from the crop-box DOM rect. The same invariant applies
when an active draft crop is committed before the user switches image drafts.

The cumulative supervised scenario above is the focused IMG-08 scenario. Its
future authorised run records current/visual layer rectangles and placement
fields before initial crop, after Apply, after draft-switch commit, after
Recrop and after Undo/Redo. At desktop and narrow modal widths, placement
values and rectangles must remain stable while the five crop fields change.
Browser execution, fixture creation, cache work and lease acquisition are
intentionally outside this source-ready record.

### Outer placement correction (`EED-CCB-2026-0043-RF3`) - 2026-08-29

QA2 reached the first Crop assertion at 1440 px and proved the report: a
deliberately narrow crop left the saved placement unchanged but reduced both
the active image and its selection mirror from 71.25 × 18.9375 px to 0.703125
× 0.1875 px. The renderers first scaled their outer placement from the crop
percentage and then applied the existing inner-image crop transform.

RF3 removes Crop dimensions from those two outer-placement calculations. The
Crop transform remains wholly inside the already placed image box, so Crop
fields may change without changing the image rectangle. This does not change
Filemanager, modal layout, source-preview selection geometry or QA assertions.
`tools/test-ccb-crop-placement-contract.ps1` protects the split.

### Atomic multi-image transformation history (`EED-CCB-2026-0043-RF7`) - 2026-08-30

`Fit to preview` changes Fit and placement values only. It no longer resets
the accepted Crop rectangle. In the add-image modal, history snapshots now
capture the selected existing image plus the reversible transformation state of
every existing draft image: Crop, Fit/Fill, placement, custom size, opacity,
ordering/options and the active-image selection. Restoring a snapshot merges
only those states into still-available, non-deleted files, then rerenders the
selected draft. This prevents Undo/Redo from crossing an image switch with the
previous image's form values.

Filemanager lifecycle is intentionally not part of this transaction. An image
added after a snapshot remains added; a Moodle draft file deleted after a
snapshot is not recreated. That reversible server/file contract is separately
named `EED-CCB-2026-0056`.

Before a browser run, the fixture must open an image modal that already has
exactly two image selectors. The focused `CROP-08` scenario then records
desktop and narrow before/after Crop, B Fit/Fill/placement/size/opacity/options,
each Undo/Redo boundary, final draft JSON and current/visual image rectangles.
The visible inner Crop must remain constrained while its outer placement stays
stable. `tools/test-image-modal-transform-contract.ps1` and
`tools/test-ccb-crop-history-contract.ps1` protect the source contract.

### Recrop action-rail avoidance (`EED-CCB-2026-0023-RF2-B`) - 2026-08-28

When Crop or Recrop is active, the floating Crop and Cancel actions retain the
existing eight placement candidates. Candidate scoring now measures both the
Crop box and the visible modal action rail, and always prefers a candidate with
less rail intersection before considering Crop-box overlap and the established
priority order. This leaves the action rail in place and preserves the existing
Crop, Cancel, Undo and Redo transactions.

Static validation covers the AMD source, generated bundle and source map, plus
the existing image-modal transform contract. Runtime validation must capture
Recrop before Cancel, the restored state after Cancel, and the final Redo state;
it remains a separately authorised visual step.

## Selected-source sticky H.1 — 2026-08-04

The Selected source tray has one JavaScript positioning authority once AMD is
loaded. The no-JavaScript CSS `sticky` fallback remains available, but the AMD
runtime disables it before using its body-level portal. The original holder is
represented by one placeholder whose final height is measured after the tray
layout has settled. That placeholder is the scroll sentinel while the tray is
floating, preventing the portal from reacting to its own geometry changes.

The targeted supervised Moodle 5.1 scenario discovered exactly one spec and
passed all five responsive cells: 1600×900, 1024×768 and 390×844 at 100%, then
1600×900 and 390×844 at genuine native Chromium 200%. Each cell exercised
`stuck → inline → stuck → inline → stuck` at the entry/release threshold and
confirmed one holder only, one placeholder only when stuck, full usable
viewport width, restoration to normal flow, no horizontal overflow, no console
errors and no failed requests. At narrow 200%, the sticky title is centred and
the Deselect action stacks without overlap.

Evidence is external to Git and Syncthing:

`C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\responsive\supervised\ccb-source-preview-responsive-20260804T203520059Z-3572`

`cleanup.json` records full fixture, course, category, profile and environment
cleanup. Human visual acceptance was recorded on 2026-08-05: when scrolling
down through the original source position and back up at desktop and responsive
widths, the tray becomes full-width below Moodle navigation, returns to its
original in-column position and does not flicker between the two states.

The plugin declares Moodle 4.5 as its compatibility floor. This change was
validated on the local Moodle 5.1 runtime only; Moodle 4.5 runtime coverage is
deferred. It changes no Moodle API, public banner rendering, source-preview
geometry or 128px sizing policy.

## Sources/Layers K.3 visual refinement - 2026-08-03

The residual Sources/Layers sub-lot aligns the low-contrast stripe origin of
locked Border and Overlay rows across the complete table surface while retaining
their distinct semantic colours. A light row separator and wider coloured rail
keep rows distinguishable without restoring a cell-by-cell grid.

Reorderable image rows retain an image-type icon at rest. The icon yields to the
six-point native drag handle only while the row is hovered or keyboard-focused.
`Layer infos & overrides` is a keyboard-operable disclosure with aligned
read-only values and a contextual-help control. Image sizing mode is no longer
shown in source settings or layer summaries; its submitted and persisted value
is retained for compatibility.

The 2026-08-04 P1 refinement keeps the leading rail at one width in every row
state, centers the Image icon and the six-point grip in the same 1rem box, and
uses one light bottom separator rather than inherited cell borders. The
layer-details help is a keyboard-operable Slideshow-style control portalled to
`document.body`, so it is available while its disclosure is closed and cannot
be clipped by the responsive table shell. The `Sort order` header uses the same
compact centred help variant. Read-only label/value pairs have additional
space. Configured Sources now has eight explicit column widths, with 20%
reserved for Actions.

The follow-up visual correction constrains every inner Font Awesome type icon
to the same centered 1rem rail box as the six-point handle. Its hover/focus
swap has a fuller motion and respects reduced-motion. Locked Overlay rows now
have an intentionally petrol-teal low-contrast stripe. The metadata disclosure
keeps its `?` in the header as a sibling of the native disclosure, so it is
also available while that disclosure is closed. Its shared CCB popover is
attached to `document.body`, uses the Kit popover-body padding and normal
weight, with a trigger-aligned pointer arrow and centred short text. The
`Sort order` header consumes that same variant. An actual reusable markup/ARIA
component remains a distinct UI Kit handoff. Each metadata label stays at the
left with its value at the right. The Selected source summary no longer offers an inline `Image
sizing mode` editor; the stored source setting and hidden submit field are
unchanged.

The supervised single-spec row-surface run passed at 100% and genuine native
200%, with no page horizontal overflow, console errors or failed requests. It
used the exclusive Moodle 5.1 fixture lease, a disposable fixture, a
process-local credential wrapper, isolated profiles and CDP captures only.
Cleanup completed in `finally` and the lease was released. The current run
evidence is external to Git:

`C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\layer-object-row\supervised\ccb-layer-object-row-20260804T151431227Z-23876`

The later modal form change retains `fitmodeoverride` as a hidden compatibility
field when that modal form exposes it, while removing its select UI. Its focused
browser assertion is waiting for a free shared Moodle runtime after the
currently-owned EasyStud run; it is not yet a green K.3 confirmation.

Human visual acceptance remains required before closing K.3. Review the
`100\selected-source-summary-100.png`, `100\layer-details-accordion-100.png`,
`100\table-help-popover-100.png`, `100\sort-order-help-popover-100.png`,
`100\drag-source-visible-100.png` and their
`200\` equivalents for the absent source-sizing editor, stripe continuity and
petrol-teal Overlay state, an equal rail width, centered Image/handle swap,
help visible while its disclosure is closed, the two `?` controls sharing the
same compact Slideshow-style body outside the table shell, centred compact help
text, padding and normal text weight, a pointer arrow aligned with the `?`,
left/right metadata alignment, action-column breathing room and readable 200%
wrapping.

### K.3 closure update — 2026-08-05

The final modal compatibility assertion is now green in the same selected
scenario. Its modal exposes no `select#id_fitmodeoverride`; when the legacy
field is present, it remains a hidden compatibility field. The run selected
exactly one test and passed both 100% and genuine native 200% cells without
horizontal overflow, console errors or failed requests. Cleanup removed its
disposable category and all temporary elements and profiles; no CCB runtime
lease remains.

Evidence is external to Git and Syncthing:

`C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\layer-object-row\supervised\ccb-layer-object-row-20260805T051114204Z-37756`

The previously requested human visual review of the K.3 surface is accepted.
This closes the local implementation and validation work. The canonical
`EED-CCB-2026-0001` lifecycle record remains owned by EasyEdu Platform and is
not changed by this plugin worktree.

## Required run contract

Before an authenticated run:

1. Acquire `moodle51-active-fixture-write` through the shared EasyEdu wrapper.
2. Use an external artifact root under `%LOCALAPPDATA%\EasyEdu\artifacts\ccb`.
3. Run `playwright test --list` with the final config/spec and require exactly
   one selected test before the test process starts.
4. Use one isolated Chromium profile, one worker, and process-local runtime
   variables only.
5. Register the artifact manifest; retain raw captures outside Git/Syncthing.
6. Restore any temporary fixture in `finally` and release the lease in
   `finally`.

## Mandatory viewport matrix

- 1600x900 at 100%;
- 1024x768 at 100%;
- 390x844 at 100%;
- 1600x900 at native 200%;
- 390x844 at native 200%.

For each cell record console/request failures, document and preview overflow,
overlap, button/icon geometry, visible focus and tab order. At native 200%,
captures must be CDP screenshots only; never capture the desktop.

## Current evidence

- Sass source compilation: passed with Dart Sass 1.89.2; output was rebuilt to
  the repository `styles.css` from the current source.
- `git diff --check`: passed.
- The controlled browser run was executed as one sequential runner. Discovery
  selected exactly one Playwright test before fixture work. The five cells
  produced 3 passes (390x844 at 100%, 1600x900 at native 200%, 390x844 at
  native 200%) and 2 failures (1600x900 at 100% failed during login/navigation;
  1024x768 at 100% timed out in `page.goto`). The failures occurred before
  CCB geometry assertions; the 1024x768 failure recorded no console or failed
  requests, while the earlier desktop attempt recorded Moodle resource
  `ERR_NETWORK_IO_SUSPENDED` failures. This is not a green UI gate.
- The run used the CCB fixture lease, a disposable category, process-local
  credentials, isolated profiles and CDP screenshots only at native 200%.
  `cleanup.json` reports `complete=true`, including course/category/format
  restoration, temporary-category removal, profile removal and no cleanup
  error. Runtime variables were cleared after the runner.
- Latest evidence is retained outside Git/Syncthing at
  `C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\responsive\supervised\ccb-source-preview-responsive-20260728T125908577Z-22268`.
  Its manifest is marked failed with the approved retention window; do not
  delete or rewrite the captures during this diagnosis.
- PHPUnit, AMD build, cache purge and any EasyStud/Moodle UI Kit/browser work
  were not run by this gate.

## Read-only runtime diagnosis

The Apache access log in the corresponding local wall-clock window records
successful but delayed responses: the CCB `admin_manage.php` response is about
755 KB and appears roughly 30 seconds after the navigation sequence begins;
the generated CCB stylesheet response is about 583 KB and appears later. The
login flow returns HTTP 303 redirects and eventually `/my/` HTTP 200, but the
redirect chain exceeds the Playwright 20–30 second navigation/assertion
budgets used by the current protected scenario. No HTTP 500 was observed in
the inspected request set.

The PHP error log contains repeated pre-existing CCB notices/warnings for
`$issitebanneradmin`, an oversized `js_call_amd` argument and Moodle's missing
`toggle` string. These are recorded as runtime follow-up findings, not as a
proven cause of the Gate 2 failures and not as permission to edit the protected
AMD, Moodle or CCB integration files in this step. An EasyStud runtime lease
was also observed during the diagnostic window; it was not reclaimed or
interrupted.
- Temporary Sass compile evidence: `C:\Users\kj220291\AppData\Local\Temp\ccb-gate2-sass-75ca2ee6df0a4f2abee7dfbaf911d732.css`
  (SHA-256 `C0629FDF68368A3D0DE56C6B5A98A9A5CDF29FC81E493548C113716B5EB262F0`).

The next QA handoff must decide whether to remediate the local Moodle
navigation latency or approve a narrowly scoped, uncommitted diagnostic timeout
adjustment in the QA harness. Neither choice is made here. After that decision,
request a fresh lease and rerun only this same one-test matrix. Do not claim
Gate 2 or advance to 2A/2B until all five cells are green and the
protected-hash/worktree checkpoint is repeated.

### Latest overlap evidence — 2026-07-29

The latest run is retained at
`C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\responsive\supervised\ccb-source-preview-responsive-20260729T092709891Z-12348`.
It selected exactly one test and passed 4/5 cells; only desktop native 200%
failed before geometry checks. During that cell `/my/` returned HTTP 500, and
the Apache/PHP logs show cache-store rename `Access denied` warnings alongside
GroupImport requests. The final read-only lease snapshot showed both
`groupimport-active-runtime-write` and `moodle51-active-fixture-write` owners
  alive. This is a runtime overlap incident, not a CCB CSS verdict.

### Responsive contract correction - 2026-07-30

The isolated `fullwidthtopinset` desktop 200% cell reached the mobile preview
sub-mode and failed only the former `frame.width / editorRoot.width >= 0.70`
assertion. The observed value was `0.6377`; there were no console errors or
failed requests, and the desktop capture was contained.

That ratio compared the logical 390px mobile frame with the full desktop source
editor root, so it was not a stable layout contract. The specification now
checks that the frame remains contained by its owning source-preview surface,
and, in mobile mode, never exceeds its server-provided logical width cap. The
same single-cell desktop 200% rerun passed with one selected test, native
zoom evidence, no overflow/overlap, no console/request errors and complete
cleanup. A subsequent supervised five-cell rerun passed all approved cells;
the responsive gate is now closed.

### Narrow portrait orientation hint - 2026-07-30

The CCB administration editor now contains a hidden, localized orientation
hint. The AMD module reveals it only when the editor marker
`[data-source-visual-editor="1"]` is present and CSS `matchMedia` reports a
portrait viewport at or below 576px. It hides automatically in landscape and
wide layouts, is absent from public pages and read-only source-chain previews,
and can be dismissed with the keyboard. No Screen Orientation API, public
banner geometry, ratio, 128px policy or other Moodle plugin surface is used.

The dedicated Playwright scenario is
`tools/playwright/ccb-banner-source-preview-orientation-hint.spec.js`. It must
run as exactly one selected test with the approved local credential wrapper,
external artifacts and the Moodle 5.1 fixture lease.

The final supervised run on 2026-07-30 passed at 100% and genuine 200% native
zoom. It recorded visible localized text, keyboard dismissal, hidden states
for landscape/wide/public contexts, no page-level horizontal overflow, no
console errors or failed requests, and complete fixture/profile cleanup.
Evidence is retained outside Git at
`%LOCALAPPDATA%\EasyEdu\artifacts\ccb\responsive\supervised\ccb-source-preview-orientation-hint-fixture-20260730T095716362Z`;
the two visible portrait CDP captures are pinned in `artifact-manifest.json`.
The evidence keeps source-editor layer geometry metrics separate from the
page-level overflow assertion so intentionally oversized editable layers do
not become a false orientation-hint failure.

### Source preview matrix rerun - 2026-07-30

The supervised `fullwidthtopinset` source-preview matrix selected exactly one
Playwright test and passed all five cells: desktop, tablet and mobile at 100%,
plus desktop and mobile at genuine native 200% zoom. The run recorded no
console errors, failed requests, preview overflow, preview overlap or
out-of-surface preview elements. The fixture, course format, temporary
category, isolated profiles and process-local environment were restored or
removed in `finally`; the CCB lease was released.

Evidence is retained outside Git/Syncthing at
`C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\responsive\supervised\ccb-source-preview-responsive-20260730T105511898Z-35380`.
The initial run showed the selected-source sticky tray in the 200% CDP
captures and identified a separate visual correction: its long source title
and `Deselect` action could be clipped by the narrow floating header even
though the preview surface and its action wrapper remained contained. No CSS
or production file was changed by that read-only review.

The follow-up correction removes the fixed horizontal offset from `Deselect`
and stacks the title/action at narrow widths. The final five-cell run is
retained outside Git/Syncthing at
`C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\responsive\supervised\ccb-source-preview-responsive-20260730T130658878Z-18508`;
post-capture geometry confirms the tray, title and action remain inside the
native 200% viewport, with complete cleanup and no console/request errors.

The follow-up runtime correction now tracks the actual Moodle scroll container,
portals the holder at full viewport width while floating, restores it through
the original placeholder on upward scrolling, and keeps narrow-screen text
centered. Static JavaScript checks pass. The first live rerun after this change
was not interpretable because the shared workstation was saturated by another
GroupImport Grunt/Chromium workload: login navigation timed out before any CCB
geometry assertion. The fixture and profiles were nevertheless restored by
`finally`; the run is retained outside Git/Syncthing at
`C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\responsive\supervised\ccb-source-preview-responsive-20260730T160708115Z-7520`.

The subsequent vertical review required the tray to attach to the physical top
of the viewport, rather than below the fixed Moodle navigation. The CCB
runtime now observes both window and nested Moodle scroll containers, compares
the tray position with the live navigation edge, and promotes it to its
drawer-aware `position: fixed; top: 0` state at that edge. The final five-cell
matrix passed with the tray at `top: 0` in both genuine 200% captures, including
the narrow mobile action stack. Evidence is retained outside Git/Syncthing at
`C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\responsive\supervised\ccb-source-preview-responsive-20260730T141631876Z-20116`.

The first continuity rerun superseded the earlier saturated-workstation
attempt: the supervised runner selected exactly one Playwright test and passed
all five approved cells on 2026-07-31, including genuine native 200% desktop
and mobile. It proved nested-scroll tracking, full CSS viewport width and
upward restoration, but its geometry-only assertions did not detect that the
body-level portal had lost the inherited native CSS custom properties. Its
tray was therefore transparent in the CDP captures and is not visual-acceptance
evidence. The diagnostic run is retained outside Git/Syncthing at
`C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\responsive\supervised\ccb-source-preview-responsive-20260731T061545005Z-28388`.

### Sources and modal disclosure lot - 2026-07-30

The first implementation lot adds a native `position: sticky` fallback for
the selected-source holder while retaining the drawer-aware JavaScript
positioning path. Source dropdown options now expose a flexible label slot so
long English/French titles wrap without moving the used-source icon. Source
table corners are rounded without clipping menus or popovers. The upload,
image-options, overlay and border accordions share the same bordered summary,
left chevron, `aria-expanded`/`aria-controls` contract and Motion disclosure
animation; reduced motion remains honoured by the existing AMD Motion module.

Static validation passed with `node --check amd/src/admin_manage.js`,
`php -l admin_manage.php`, Sass 1.89.2 compilation and `git diff --check`.
The Moodle Grunt toolchain is not present in this checkout, so the documented
Terser fallback rebuilt only
`amd/build/admin_manage.min.js` and its source map, and both generated files
passed syntax/marker checks. A follow-up single-test attempt selected exactly
one test but stopped before fixture mutation because a live EasyStud/UI Kit
process owned `moodle51-active-fixture-write`. No new browser cells or CDP
captures were produced; rerun after the lease is released.

The completed run then passed the three 100% cells but exposed the same
selected-source floating-header boundary at both native 200% cells: the
runtime used `window.innerWidth` (800/195 CSS px) while the usable document
client width was 792/187 CSS px. The CCB correction now derives the sticky
boundary from `document.documentElement.clientWidth`, preserving the drawer
clamp while removing the scrollbar overrun. AMD was rebuilt with the
documented Terser fallback; the five-cell scenario subsequently passed after
the lease was released, with final evidence recorded above.

The responsive assertion now distinguishes an offscreen normal-flow holder
from a materialised sticky tray. Sticky geometry remains asserted whenever the
holder intersects the viewport after scrolling; native 200% initial layout no
longer fails merely because the holder has not reached its sticky threshold.

### Selected-source viewport portal - 2026-07-30

The selected-source holder now uses a body-level portal only while it is
sticky. This prevents transformed administration containers from constraining
the fixed tray to the source-editor column. A placeholder preserves the source
layout position, and the scroll threshold uses the preserved document anchor
rather than the fixed tray rectangle, so upward scrolling restores the holder
to its original location. At widths up to `48rem`, the selected-source text is
centred. Because the holder leaves `.local-course-banner-builder-admin--native`
while portalled, it must also receive that native token scope; otherwise its
background and border resolve as transparent. The responsive scenario requires
an opaque background and a non-zero border while the holder is floating.

After that scope correction, the supervised five-cell Moodle 5.1 run passed at
100% and genuine native 200% with no console/request errors and complete
fixture/profile cleanup. The two CDP captures now show an opaque tray above the
editor content. Evidence is retained outside Git/Syncthing at
`C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\responsive\supervised\ccb-source-preview-responsive-20260731T071121676Z-31160`.

The subsequent desktop refinement measures Moodle's live right drawer toggle
instead of assuming a theme offset, then reserves its current width plus a
small gap for `Deselect` only in the desktop portal layout. The sticky state
also uses a 2px entry / 12px release band: it cannot repeatedly portal and
restore while the original holder crosses the navigation edge by a few pixels.
The responsive scenario guards both the drawer non-overlap and the retained
sticky state inside that release band. Runtime evidence is pending the shared
Moodle 5.1 fixture lease; do not treat the earlier geometry-only evidence as
validation for this refinement.

The first complete rerun passed four cells but desktop 200% timed out while
Moodle was still loading the source page, before any CCB assertion. The
supervisor therefore supports a strict `-CellId` value from the approved
matrix, so an interrupted cell can be resumed without repeating the other
fixture runs. Native desktop 200% receives a 60-second navigation allowance;
the normal cells retain 20 seconds. The resumed desktop 200% cell passed with
an opaque full-width tray, no `Deselect`/drawer-toggle overlap, retained
release-band stickiness, no console or request failures, and complete fixture
and profile cleanup. Its evidence is retained outside Git/Syncthing at
`C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\responsive\supervised\ccb-source-preview-responsive-20260731T113428645Z-35944`.

### Modal action rail lot - 2026-07-31

### Modal runner output-drain recovery - 2026-08-04

The local supervised runner starts asynchronous reads of both redirected Node
streams immediately after process start, then awaits them after process exit.
This prevents verbose Playwright failures from deadlocking Windows pipes. The
watchdog, lease, safe redaction, artifact files and cleanup remain unchanged.
`Test-CCBAsyncProcessDrain.ps1` proves the mechanism with 1 MiB on each stream
without Moodle or Chromium. A real rail capture remains pending.

The layer-modal body and its absolute action rail now share
`--local-course-banner-builder-modal-action-rail-width`. Previously the body
reserved `18rem` while a later action-contract rule could widen the rail to
`26rem`, allowing it to overlap preview/form content. The shared rail uses
`clamp(15rem, 22vw, 18rem)` on desktop; existing sub-992px rules return the
panel to normal-flow, full-width content. No modal actions, fields, AMD hooks
or slideshow behaviour changed.

The narrow native-zoom refinement keeps the dynamic modal and its form in the
available viewport at `max-width: 575.98px`; below `240px` CSS it uses the
modal viewport rather than spending the editing surface on decorative outer
margins. Modal-rail labels override the dense source-list ellipsis only inside
the modal action list, so long localized action names wrap instead of being
hidden. This does not change public banners, layer geometry, form fields, AMD
data attributes, or the normal 390px-at-100% presentation.

`ccb-layer-modal-action-rail.spec.js` selected exactly one test and passed on
Moodle 5.1 at 1600x900, 1024x768 and 390x844 at 100%, plus genuine native
200% zoom at 1600x900 and 390x844. It verifies rail/form containment, no
document overflow, keyboard focus, readable non-ellipsized labels, compact
modal-body reachability, and no console errors or failed requests. The final
run restored its disposable category and isolated profiles completely. CDP
evidence and the retention manifest are external to Git/Syncthing at
`C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\modal-action-rail\supervised\ccb-layer-modal-action-rail-20260731T141210391Z-20852`.

The final isolated run exercised Image, Border and Overlay forms separately.
For every form and every matrix cell, the visible side-panel button opened and
closed with Enter, exposed the expected `aria-expanded` state, and revealed
the correct form-specific disclosure. The state chevron is visually placed
before the type icon and label; the nested native `summary` remains structural
because the outer side-panel button is the accessible controller. No AMD hook,
native form name or public-banner geometry changed.

Final CDP evidence, `artifact-summary.json`, `cleanup.json` and the retention
manifest are external to Git/Syncthing at
`C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\modal-action-rail\supervised\ccb-layer-modal-action-rail-20260731T151208176Z-26896`.
The cleanup record confirms the disposable category was removed, isolated
profiles were deleted, runtime variables cleared and the Moodle lease released.

### Crop cancellation persistence - 2026-08-01

After the modal action-rail Undo restores the draft image, CCB now writes the
restored crop state to the canonical Moodle inputs and `previewcropstate`
payload. This prevents a previously applied crop from being submitted after
the user has undone it. CCB administration is the only affected surface;
public rendering, banner geometry, the 128px policy and source-preview
geometry are unchanged.

The one-test supervised Moodle 5.1 matrix selected exactly one test and passed
at 1600x900, 1024x768 and 390x844 at 100%, plus genuine native 200% at
1600x900 and 390x844. Its crop regression step uploads a verified PNG, applies
a changed crop, invokes modal Undo, confirms the restored `false, 0, 0, 100,
100` state in the preview, canonical form values, visual draft and submitted
payload, then submits through Moodle and confirms no crop-enabled source layer
persists. It also retained the Image/Border/Overlay modal containment,
keyboard, overflow, console and failed-request checks.

External CDP evidence, `artifact-summary.json`, `cleanup.json` and the
retention manifest are at
`C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\modal-action-rail\supervised\ccb-layer-modal-action-rail-20260801T114147201Z-35544`.
The cleanup record confirms category removal, zero remaining fixture elements,
profile deletion, environment clearing and lease release.

### Layer-modal loading centering - 2026-08-01

The loading state of the CCB Image, Border and Overlay modals is now centred
inside the currently available modal body. While a response is pending, the
body is a flex column, its usual trailing spacing is symmetric, the decorative
bottom spacer is absent, and the loading status grows into the remaining body
surface. The normal action rail, form, modal dimensions and public banner
rendering are unaffected once the response arrives.

The existing one-test Moodle 5.1 supervisor temporarily delayed the real
Overlay edit request and measured the loading spinner at 0px horizontal and
1.59px vertical distance from the modal-body centre. It then completed the
normal Image/Border/Overlay checks at 1600x900, 1024x768 and 390x844 at 100%,
plus 1600x900 and 390x844 at native 200%. There were no console errors,
failed requests, document overflows or cleanup residue.

External CDP evidence, summary, cleanup record and manifest are retained at
`C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\modal-action-rail\supervised\ccb-layer-modal-action-rail-20260801T120821393Z-31188`.

### Expanded side-panel boundaries - 2026-08-01

The Image, Border and Overlay form panels in the CCB modal action rail no
longer join underneath their trigger. Each expanded trigger keeps its normal
rounded edge; its panel has a complete solid border and uses the action-list
gap instead of a negative overlap. This is a CCB presentation change only:
the existing buttons, `aria-expanded`/`aria-controls` contract, form fields,
AMD actions, preview geometry and public rendering are unchanged.

The one-test Moodle 5.1 modal scenario now measures this boundary for all
three panel types through the same 100% and native-200% matrix. The final
desktop evidence reports a 1px solid top border and a 2.875px visible gap for
Image, Border and Overlay. It also rechecks the centred loading state,
keyboard disclosure, containment, console/request errors and complete
fixture/profile cleanup.

External CDP evidence, `artifact-summary.json`, `cleanup.json` and the
manifest are at
`C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\modal-action-rail\supervised\ccb-layer-modal-action-rail-20260801T121945530Z-37688`.

### General source-preview action panel — 2026-07-31

The existing CCB-owned preview layout keeps its canvas and control rail as
separate grid areas. The right rail fills its grid column, while the primary
save/delete action container fills only the remaining canvas row; changing
between Desktop preview and Mobile preview does not resize the
filmstrip/visibility siblings or change the desktop action row. Its controls
use the shared wrapping/icon contract rather than fixed clipping dimensions.

### Source-preview mode labels — EED-CCB-2026-0076

The two segmented-mode captions originate only from
`sourcepreviewmodedesktop` and `sourcepreviewmodemobile` in the English and
French Moodle language packs. `admin_manage.php` renders both through
`get_string()`; the templates and AMD source/build contain no local caption.
The focused contract decodes both language files as strict UTF-8, checks the
four exact translations, rejects the former captions from PHP, Mustache and
AMD assets, and preserves the `desktop` / `mobile` data values. Because the
change is language-only, no Sass or AMD rebuild is required.

The existing one-test supervised responsive scenario passed the complete
five-cell matrix on Moodle 5.1: 1600x900, 1024x768 and 390x844 at 100%, plus
1600x900 and 390x844 at genuine native 200%. It checked panel containment,
action order and non-clipping, Desktop-to-Mobile-to-Desktop state change,
filmstrip/visibility non-overlap, keyboard focus, no console/request failures,
and final course/category/profile restoration. Its external CDP evidence and
manifest are at
`C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\responsive\supervised\ccb-source-preview-responsive-20260731T141702034Z-21184`.

### Layer-list action alignment - 2026-07-31

The action contract now gives source and layer action cells an explicit
leading-icon slot, centered label slot and mirrored trailing slot. Layer
markup now uses the same `.local-course-banner-builder-action-label` wrapper
that Sources already used; no action URL, confirmation, form, data attribute,
drag/drop or keyboard hook changed. The narrowly scoped grid declaration
intentionally overrides the older Kit secondary-action `inline-flex` only in
these CCB list cells.

The isolated `ccb-layer-object-row.spec.js` selected exactly one test and
passed at 1600x900 at 100% and genuine native 200%. For each of the six layer
actions, it proves a left-edge icon, three grid slots, label center alignment,
no clipping, no document/root overflow, native drag and keyboard ordering,
zero console/request errors, and complete fixture/profile cleanup. Evidence:
`C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\layer-object-row\supervised\ccb-layer-object-row-20260731T142449484Z-27220`.

The same one-test scenario now visits the configured Sources list before the
layer editor and validates all visible source actions under the same contract.
At 100% and genuine native 200%, Preview, Edit and both destructive actions
retain left icons, centered non-clipped labels and three grid slots, with no
console/request error. Its CDP evidence is in the same external run root.

### Contextual help contract - 2026-07-31

The audit found that dynamic CCB popovers already use the embedded EasyEdu Kit,
while the single layer override question used a legacy `details` panel with a
separate white surface and padding. It is now a keyboard-focusable button using
the established delegated CCB popover contract. Existing cached `details`
markup receives the same Kit help-icon and popover surface until it disappears.
The targeted layer-object-row validation passed at 100% and genuine native
200%, including keyboard focus, tooltip linkage, no overflow, console/request
checks and disposable-fixture cleanup. Its external artifact root is
`C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\layer-object-row\supervised\ccb-layer-object-row-20260731T143856936Z-19624`.

### Layer-details accordion - 2026-07-31

The source layer table no longer exposes its sizing dropdown inline. Its
`Layer infos & overrides` cell shows the active sizing mode in a closed native
accordion and exposes the read-only mode, contextual help and layer metadata
only after keyboard or pointer disclosure. Full sizing changes remain in the
layer modal. The one-test validation passed at 100% and genuine native 200%,
with no root overflow, console/request failure, or fixture/profile residue.
External CDP evidence and manifest:
`C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\layer-object-row\supervised\ccb-layer-object-row-20260731T144553451Z-13036`.

### Layer-list drag state refinement - 2026-08-01

The native CCB table keeps its existing order, drag and keyboard contracts, but
now projects the shared object-card language through one leading-cell rail.
Reorderable rows expose the shared six-point drag handle, `grab`/`grabbing`
cursors and a light whole-row hover/focus tint. The previous per-cell focus
boxes and visual separators are removed. Locked Border and Overlay rows remain
non-draggable and use separate coloured rails and striped surfaces.

The isolated Moodle 5.1 scenario selected exactly one test and passed at
1600x900 at 100% and genuine native 200%. It verified native drag and keyboard
ordering, five fixture rows (two reorderable and three locked), no document or
root overflow, no console/request failure and complete fixture/profile cleanup.
The external artifact manifest records the eight CDP captures for the run.

### Layer indicator placement - 2026-08-20

The coloured leading rail is again the sole location for each Image, Border or
Overlay type icon. The `Sort order` cell retains only its localised order label,
one copy of each active status badge and the lock badge below that label; it no
longer duplicates the type icon or places the lock beside the order text.
Status badges remain absent from `Banner layer`, preserve their keyboard
popover hooks, and do not change native drag or selection behavior.

### Large preview and empty Sort-order slot - 2026-08-31

The selected-source editor now opens its current renderer in the existing
contained Source Preview modal. The clone is readonly, strips every form and
authoring action, retains Desktop/Mobile simulation locally, and returns focus
to the launcher on every established close path. A focused source contract
guards the no-form boundary and the modal-only mode-state exclusion. Runtime
focus, Escape and responsive review remain part of the managed preview gate.

Ordinary unlocked Image rows no longer render the empty auxiliary status
container below Sort order. The server exports an explicit status-presence
flag, and the Mustache template emits the stack only for Crop, fixed-centre or
locked/status rows. This does not change row order, hidden sort values, drag,
keyboard ordering or any existing status popover.

### Layer-list identity, locked surfaces and selection controls - 2026-08-02

Locked rows now carry one low-contrast striped background on the table row
itself, so the pattern remains continuous across transparent cells. The Overlay
sample receives its own opaque surface rather than exposing stripes behind the
sample. The stale Border leading pseudo-element is suppressed; every row now
uses the same single leading rail width.

The first cell communicates the layer type for locked rows and uses the shared
six-point handle only for images that can actually be reordered. Ordering
constraints and their existing keyboard-accessible popovers now sit beside the
sort position. Table selection and enabled controls use the embedded EasyEdu
selection-checkbox primitive. The CCB mform bridge applies the same state
tokens to native form checkboxes while excluding existing switch controls.

The one selected Moodle 5.1 scenario passed at 1600x900 at 100% and genuine
native 200%, with five rows, two reorderable images and three locked rows. It
checked continuous locked-row paint, the Overlay sample surface, row identity,
drag/keyboard ordering, no console/request failure, no horizontal overflow and
complete category/profile cleanup. Visual review of non-table mform checkboxes
is intentionally deferred to their modal-specific scenario.

### Sources/Layers K.3 visual refinement - 2026-08-03

Locked rows now use a shared vertical stripe rhythm, so the low-contrast
pattern aligns across contiguous rows while each Border/Overlay state retains
its own colour. A light bottom separator preserves row scanning without
restoring cell-by-cell borders. The leading rail is wider and carries the white
type icon; reorderable image rows keep the Image icon at rest and reveal the
six-point handle on hover or keyboard focus.

`Layer infos & overrides` is now a read-only metadata disclosure with aligned
terms/values and a keyboard-accessible help trigger. The image-sizing controls
are absent from the source settings form, configured Sources table and layer
summary. The persisted `fitmode` remains a hidden form value, and existing
source/layer data is unchanged.

The isolated `ccb-layer-object-row.spec.js` selected exactly one test and
passed at 1600x900 at 100% and genuine native 200%. It checked the hidden
source form field, absence of visible sizing controls, continuous row paint,
row separation, Image-to-handle transition, disclosure/help behavior, native
drag and keyboard ordering, no overflow, no console/request failure and full
category/profile cleanup. External CDP evidence, manifest and cleanup reports:
`C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\layer-object-row\supervised\ccb-layer-object-row-20260803T131117902Z-33196`.
### Wave 6 generated Motion contract

The RF5 generated-asset check identifies the minified Motion dependency and
the compiled disclosure-content selector. It does not require the source-level
identifier `Motion.expand` to survive minification, because the official AMD
build is allowed to rename local dependency bindings.
