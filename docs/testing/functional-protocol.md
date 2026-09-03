# Functional test protocol

## Purpose

This protocol defines the product-level checks that generic Moodle validation
cannot infer for Course Banner Builder. It separates current automated coverage
from candidate tests that still require deterministic fixtures.

## Supported environments

| Environment | Expected use |
| --- | --- |
| Moodle 4.5 | Minimum supported Moodle version and current platform matrix target |
| Moodle 5.1 | Active development and regression environment |
| Standard Moodle theme | Required fallback validation |
| EasyEdu theme | Required visual integration validation when available |

The plugin currently declares Moodle 4.5 as its minimum version.

## Current capability contract

The builder uses one system capability:

`local/course_banner_builder:manage`

It is defined at `CONTEXT_SYSTEM` and allowed for the manager archetype. The
current expected access matrix is therefore:

| User | Expected builder access |
| --- | --- |
| Site administrator | Allowed |
| System manager with the capability | Allowed |
| Course teacher without the system capability | Denied |
| Enrolled student | Denied |

The rendered course banner can be visible to normal course users, but the
administration pages are not currently course-context management pages. A
future course-context capability must be designed and tested separately rather
than assumed by this protocol.

## Fixture manifest

No reusable Behat data generator currently creates a complete CCB source. The
following fixtures are required before interaction-heavy scenarios become CI
gates.

### Users and courses

- one administrator;
- one system manager with `local/course_banner_builder:manage`;
- one editing teacher without that system capability;
- one enrolled student;
- one empty course using a standard Moodle theme;
- one course with a forum, assignment, quiz, and course overview image.

### Source rules

- parent and child course categories;
- a course in the child category;
- enabled course custom fields with at least two distinct values;
- a category source;
- a custom-field source;
- a two-level inheritance chain;
- cumulative and random composition examples;
- a source with no layers for empty-state checks.

### Files and visual layers

- small JPEG background;
- transparent PNG foreground;
- large valid image;
- unsupported file type;
- file exceeding the configured limit;
- source with two or more image layers;
- cropped image layer;
- border, overlay, and title layers;
- site and course slideshow content.

Fixtures must use distributable test assets with an explicit compatible
license. Do not depend on files from a developer's draft area.

## Test catalogue

Status values:

- `Existing`: automated now.
- `Candidate`: suitable after fixtures exist.
- `Manual`: intentionally manual because the visual result is part of the
  contract.
- `Blocked`: required fixture or stable selector is missing.

| ID | Flow | Expected result | Owner | Status |
| --- | --- | --- | --- | --- |
| AUTH-01 | Administrator opens course banner management | Page and CCB root load | Behat | Existing |
| AUTH-02 | System manager opens builder | Access allowed | Behat | Blocked |
| AUTH-03 | Teacher without system capability opens builder | Access denied | Behat | Blocked |
| AUTH-04 | Student opens builder | Access denied | Behat | Blocked |
| LOAD-01 | Course banner admin loads with no configured source | CCB root remains available | Behat | Existing smoke |
| LOAD-02 | Site, slideshow, and transfer views load | Correct CCB root and shared Navigation rail | Behat | Candidate |
| LOAD-03 | Slideshow identity and Skeleton handoff | The localised Course Banner Builder name, title and description precede the unchanged Navigation in loading and ready states. Navigation is one compact static line with its Guide circle; Course/Site structural frames have logical top accents and only cues animate. Header icons align, cards stack at 390 px, RTL mirrors cue travel, reduced-motion stops it, forced-colors keeps frames visible and no Skeleton descendant is focusable. | Playwright/manual | Candidate |
| NAV-01 | Open every CCB administration destination | Rail exposes Course, Site, Slideshow, Transfer, Course format and Site format; desktop icons remain plain, while compact icons use the EasyStud neutral tile and primary active state | Playwright/manual | Candidate |
| NAV-02 | Open and close the compact rail | The closed left-centred half-pill is 44px by 44px and its hover/focus state shows the complete localized label; backdrop, Escape and focus return work without horizontal overflow or collision with Moodle's native drawer control | Playwright/manual | Candidate |
| SRC-01 | Create and select category source | Source becomes active | Behat | Blocked |
| SRC-02 | Create custom-field source | Value-specific source is available | Behat | Blocked |
| SRC-03 | Configure inheritance chain | Final layer order follows inheritance rules | PHPUnit/Behat | Blocked |
| SRC-04 | Change a configured source parent | Parent source is informative in the table; compact pencil triggers in both the Parent column and Selected source open one keyboard-operable modal portalled directly under `document.body`, with no `aria-hidden` ancestor and initial focus on its dropdown. It offers root plus configured non-descendants only. The searched option list opens below its field in normal flow and stays within the opaque, internally scrollable modal surface. Its centred Close control and Save action consume the existing shared EasyEdu modal-close and Save primitives after the portal move; Save retains the shared light hover, focus and pressed states. Escape, backdrop click and Cancel close without leaving a backdrop, restore the modal to its server-rendered parent and return focus to the pencil; Cancel makes no request. Confirm recalculates the affected source chain, refreshes the complete table and the Selected source fragment when applicable, restores the modal and focus, and leaves external review captures. A forged descendant is rejected by the server while the modal stays open. The same action geometry remains contained at 390 px. | One leased Moodle 5.1 Playwright scenario (`ccb-parent-source-modal.spec.js`) | Candidate — requires a disposable three-source chain fixture, an external manifested artifact root and explicit runtime authorisation |
| IMG-01 | Upload a valid image through filepicker | Draft image appears once | Playwright | Blocked |
| IMG-02 | Drop a valid image | Same state as filepicker upload | Playwright | Blocked |
| IMG-03 | Upload invalid type or oversized file | Localised error, no layer created | Behat/Playwright | Blocked |
| IMG-04 | Add and select one image layer | Handles and image tools target that layer | Playwright | Blocked |
| IMG-05 | Replace or delete an image | Preview and persisted state update | Playwright | Blocked |
| IMG-06 | Add one image, resize, then use Fit to preview | A newly uploaded draft starts once with the same proportional geometry as Fit to preview (`cover`, centred, 100% width/height and Keep proportions enabled). Later filemanager refreshes reuse that draft's stored state and do not overwrite user edits. Fit, Fill banner, active-draft selection, drag and resize pass through one modal transaction: form fields, active draft JSON, then current/visual DOM mirrors. The transaction resolves the modal-scoped `fitmodeoverride` by id and then canonical name, so id-less Moodle hidden inputs are usable. The active draft's technical visual twin is excluded from snap/guide peers so dragging remains direct. Aspect-locked vertical edges and corners follow the moved pointer axis. A live pointer frame renders at most once and stages JSON until release; filemanager mutations share one form-local refresh queue. Edit-image and general-preview transforms are outside this flow. The state persists after save/reopen; Cancel leaves the saved layer unchanged. Crop/recrop and modal undo/redo are checked as non-regressions only. `tools/test-image-modal-transform-contract.ps1` protects these contracts. `tools/playwright/Invoke-CCBImageModalTransformValidation.ps1` then selects exactly one `ccb-image-modal-transform.spec.js` test before credentials or fixture setup, holds the shared Moodle fixture lease, stores geometry/timing/captures/trace outside Git and verifies cleanup. | One local-supervised leased Moodle 5.1 Playwright scenario plus manual review | Candidate |
| IMG-07 | Select draft image A, then B | Every filemanager thumbnail has a localised native `Select image N` control, exposes its selected state, and supports Enter/Space. Its activation and the existing preview click use the same A-to-B selection path, which commits active crop state before changing drafts. | Static A/B contract now; one leased Moodle 5.1 scenario and manual keyboard review next | Candidate |
| IMG-08 | Select an image that extends beyond an editable preview frame | The selected image keeps its normal transform and clipping. Each overflowing outline edge clamps independently and magnetically to the corresponding preview edge with one uniform inset stroke; unaffected edges still follow the image. The same behavior is expected in the general/source preview, Add image modal and Edit image modal; clearing selection or hiding the image removes the indicator. | Static selection-frame contract plus manual/browser review | Candidate RF2 |
| CROP-01 | Normalise crop percentages | Values clamp to valid geometry | PHPUnit | Existing |
| CROP-02 | Compute effective crop dimensions | Visible dimensions match crop | PHPUnit | Existing |
| CROP-03 | Raster crop | Output dimensions match crop | PHPUnit | Existing |
| CROP-04 | Open crop editor | Original image and crop selection are shown | Playwright | Blocked |
| CROP-05 | Move and resize crop, then validate | Crop persists after reload | Playwright | Blocked |
| CROP-06 | Cancel crop | Previous persisted geometry is restored | Playwright | Blocked |
| CROP-07 | Crop two existing draft images independently | Switching selection preserves both crops and their outer placement | Playwright | Candidate |
| CROP-08 | Chronological multi-image transformation history | A disposable CCB source exposes exactly two existing images through its normal edit-image modal. Crop A, switch to B, Fit/Fill/placement/size/opacity/options, then Undo/Redo restores the selected image and every non-destructive state in action order. `Fit to preview` retains Crop. Filemanager Add/Delete is explicitly excluded because current Moodle draft deletion is irreversible; that lifecycle is `EED-CCB-2026-0056`. | One local-supervised leased Moodle 5.1 scenario (`Invoke-CCBCropHistoryValidation.ps1` / `ccb-crop-recrop-history.spec.js`) plus desktop/narrow manual review | Candidate |
| LYR-01 | Reorder layers by buttons | Preview z-order changes and persists | Behat/Playwright | Blocked |
| LYR-02 | Reorder layers by drag/drop | Same persisted order as button flow | Playwright | Blocked |
| LYR-03 | Push image above overlay/border | Conflicting order controls are disabled | Playwright | Blocked |
| LYR-04 | Inspect layer identity, Sort order status and Layer infos disclosure | Image, Border and Overlay types stay in the coloured leading rail. Sort order shows only one copy of each status badge and its lock badge below the localised `Locked` label; neither status nor lock appears in Banner layer. An image without a custom size displays its native `Original size` dimensions in `Layer infos & overrides`; a dynamic or otherwise populated layer retains its disclosure, keyboard help and existing content. When no layer information can be read, the disclosure uses the EasyEdu Kit empty state and does not render a redundant `?` help. Every rendered popover indicator remains keyboard-focusable. Native drag and selection remain available. | One leased Moodle 5.1 Playwright scenario (`ccb-layer-object-row.spec.js`) | Candidate |
| DEL-01 | Cancel then confirm selected and all layer deletion | Cancellation makes no POST; confirmed actions use the local selected-source refresh, loading feedback, success toast and deterministic focus return | One leased Moodle 5.1 Playwright scenario (`ccb-async-editor-actions.spec.js`) | Candidate |
| SAVE-01 | Save and reload source | All visual settings persist | Behat/Playwright | Blocked |
| PREV-01 | Resize admin preview | Layout remains usable at desktop/tablet/mobile | Playwright/manual | Manual |
| PREV-02 | Render final course banner | Position, crop, title, border, and overlay agree with preview | Visual/manual | Manual |
| PREV-03 | Open the large authoring workspace | At >=1024 px the exact live source editor moves into one contained modal, existing selection/actions remain interactive, Close/Escape/backdrop restore the editor to its original place and focus returns to the launcher. Below 1024 px a localised explanation appears and the normal editor remains usable. No duplicate editor, automatic request or console error occurs. | Static contract plus managed-preview manual review | Slice 1 candidate |
| PREV-04 | Navigate the large authoring viewport | The large workspace opens in Fit; its range and +/- controls stay within 25–400%, the 100% control restores actual size, and overflow exposes native scrollbars. Holding Space and dragging pans those scrollbars without moving/resizing/cropping a layer. Closing removes the transient view state; reopening starts in Fit. The standard editor and classic general preview never receive this zoom or pan. | Slice 2 static contract plus managed-preview manual review | Slice 2 candidate |
| SRC-05 | Save a Parent change from the portalled modal | Save and Cancel keep compact shared geometry. Save shows the standard bottom-end busy feedback and refreshes the configured table plus the selected source general preview without a page reload; error keeps the modal actionable. | Wave 15 static contract plus the existing leased Parent scenario | Source candidate |
| IMG-09 | Open a dynamically loaded Image editor | The selected Image frame is already aligned on the first visible modal paint, remains stable through Crop and does not require a canvas click. Image, Border and Overlay content reveals once after their accepted loader, with no flicker or layout shift; reduced motion bypasses the reveal. | Wave 15 static contract plus managed-preview manual review | Source candidate |
| PREV-05 | Exercise the corrected large-workspace controls | Compact +/- controls remain visible; numeric zoom clamps to 25-400%; endpoint disabled states are truthful; manual zoom survives resize; Fit follows resize; only Space-drag pans; first Fit aligns selection and close visibly returns focus. | Wave 15 static contract plus managed-preview manual review | Source candidate |
| PREV-06 | Exercise the large grid plane and published frame | At >=1024 px the workspace shows a checkerboard beyond a clearly labelled published frame. Fit centres that frame; 25-400% zoom changes the plane and banner only, never the toolbar or source controls. Existing out-of-frame layers remain reachable. Dragging empty grid and Space-drag pan, while layer/Crop gestures retain ownership. Close and reopen restore the exact editor, draft and Fit start. The classic general preview is unchanged. | Slice 3A source/generated contract plus managed-preview manual review | Source candidate |
| PREV-07 | Exercise the large-scene and right-dock composition | The checkerboard viewport owns most modal width and flexible height. The right source-control dock keeps a bounded width, fills the usable height and scrolls independently; its buttons never stretch. Visibility controls, filmstrip and Save/Delete remain reachable below the scene, filmstrip arrows follow real overflow, and Fit/zoom/Crop/layer gestures retain their established behavior. | Slice 3B source/generated contract plus managed-preview manual review | Source candidate |
| THEME-01 | Use standard Moodle theme | Builder and banner retain a complete fallback UI | Behat/Playwright | Blocked |
| GUIDE-01 | Open guide and navigate slides | Slide state and keyboard navigation remain stable | Playwright | Blocked |
| GUIDE-02 | Show target in interface | Highlight stays aligned and return panel appears | Playwright | Blocked |
| GUIDE-03 | Complete guided path | Checklist, prerequisite, reload, and return state work | Playwright | Blocked |
| GUIDE-04 | Open Guide from the CCB rail | Desktop label reveal does not shift destinations, clip its shadow or create a second tooltip; compact launcher closes the panel, opens Guide above it and returns focus after close | Playwright/manual | Candidate |
| GUIDE-05 | Show a Guide target that requires disclosures | The compact or desktop target is selected for the active viewport; `showopen` runs before `showafteropen`, then the target is highlighted and the return panel remains usable | One leased Moodle 5.1 Playwright scenario (`ccb-guide-05.spec.js`) plus manual | Candidate |
| ERR-01 | Open builder without sources | Actionable empty state, no console error | Behat | Candidate |
| ERR-02 | Server-side validation fails | Localised message and submitted data remain understandable | Behat | Blocked |
| A11Y-01 | Scan admin root | No serious or critical plugin-region axe violations | Behat/Playwright | Existing smoke |
| A11Y-02 | Keyboard-only core flow | Focus visible, modal focus restored, non-drag controls work | Manual/Behat | Manual |
| A11Y-03 | Public banner title replacement | One Moodle-owned primary heading; decorative duplicate hidden; distinct context is secondary; no hidden focusable control | Playwright/manual | Existing opt-in / Manual screen reader |
| SET-TRF-01 | Open General settings and Transfer without changing data | Compact Settings panel, readable focusable Delete action, two Moodle-style Transfer checkbox columns, aligned destructive option and spaced filepicker button | Playwright | Existing opt-in |
| TRF-01 | Export settings and files | Package contains selected supported data | PHPUnit/Behat | Blocked |
| TRF-02 | Import package into clean site | Data maps safely and missing dependencies are reported | PHPUnit/Behat | Blocked |

## Stable automation order

1. Keep the existing crop helper PHPUnit tests mandatory.
2. Run the plugin-root accessibility smoke on an empty admin page.
3. Add a Behat data generator for categories, custom fields, sources, and
   layers.
4. Add stable Behat access, empty-state, save/reload, and validation scenarios.
5. Add Playwright for filepicker, drop, crop, resize, guide, and responsive
   flows.
6. Promote a test to the EasyEdu platform matrix only after it is deterministic
   on a clean disposable Moodle site.

The opt-in `SET-TRF-01` implementation and its non-destructive execution
contract are documented in [CCB Settings and Transfer parity](ccb-settings-transfer-parity.md).

## Detailed manual scenarios

### Image and crop regression

1. Add at least two images in one add-layer modal.
2. Select image A, move and resize it, crop it, and validate the crop.
3. Select image B and repeat with different geometry.
4. Re-select image A without dragging it.
5. Confirm the crop remains visible immediately and the image does not flash
   back to its full contents.
6. Re-open crop on image A, select another source area, and validate.
7. Confirm image B remains unchanged.
8. Save, reload, and compare modal, general preview, and final banner.

### Layer order regression

1. Prepare three image layers plus an overlay and border.
2. Reorder images using buttons, then drag and drop.
3. Confirm the table order, preview z-order, and saved order agree.
4. Enable above-overlay or above-border states.
5. Confirm incompatible order controls and drag/drop are disabled.
6. Remove the special state and confirm normal ordering becomes available.

### Responsive rendering

Check at minimum:

- 1600 x 900;
- 1024 x 768;
- 768 x 1024;
- 390 x 844;
- browser zoom at 200%.

Verify the real banner and every applicable preview preserve crop, relative
position, title frame proportions, border containment, overlay order, and
slideshow controls.

### Batch 2C DOM-geometry cells

The opt-in CCB geometry specification lives in the shared Playwright harness
at `local/groupimport/tools/playwright/ccb-banner-geometry.spec.js`. Run each
format and viewport cell in a separate process; it restores the selected
format in its `finally` block and asserts the restored value. The following example validates one Moodle 5.1
desktop cell from that harness directory (credentials remain environment-only):

```powershell
$env:EASYEDU_CCB_FIXTURE_COURSE_ID = '2'
$env:EASYEDU_CCB_FORMATS = 'fullwidthtop'
$env:EASYEDU_CCB_VIEWPORT = 'desktop'
$env:EASYEDU_CCB_THEME = 'boost'
$env:EASYEDU_CCB_CAPTURE_SCREENSHOTS = '1'
$env:EASYEDU_CCB_ARTIFACT_ROOT = '.\artifacts\ccb-batch2c'
npx --yes node@20 .\node_modules\@playwright\test\cli.js test .\ccb-banner-geometry.spec.js --grep "required format" --reporter=line --workers=1
```

`EASYEDU_CCB_FORMATS` accepts a comma-separated subset of `standard`,
`contentwide`, `fullwidthtop`, `fullwidthtopcompact`, and
`fullwidthtopinset`. `EASYEDU_CCB_VIEWPORT` (or the plural
`EASYEDU_CCB_VIEWPORTS`) accepts `desktop`, `tablet`, `portrait`, and
`mobile`. `EASYEDU_CCB_THEME`, when set, is an assertion of the active Moodle
theme rather than a theme switcher; prepare the target theme before starting a
cell. Set `EASYEDU_CCB_CAPTURE_SCREENSHOTS` to `1` only for evidence cells;
the JSON and trace are always retained. Set `EASYEDU_CCB_ARTIFACT_ROOT` to a
stable, ignored evidence directory when executing separate processes, because
Playwright clears its normal `test-results` directory before each process.

The specification records `visualViewport.scale` and `devicePixelRatio` as
diagnostic metadata, but this is not a substitute for browser zoom. A genuine
200% zoom check must be performed in a headed browser using the browser zoom
control; do not emulate it with `deviceScaleFactor`.

At narrow effective viewports, including genuine 200% zoom on the 390x844
cell, the source-preview visibility actions must wrap inside their preview
surface. The responsive evidence must therefore check both root scroll width
and descendant containment; a row that widens the visual-editor root is a
failed cell even when the document itself has no horizontal scrollbar.
The source-preview surface may use a centered viewport-width treatment below
576 CSS pixels when the Moodle admin column is narrower than the viewport. This
is an administration-only containment aid: the frame keeps the server-provided
format ratio and must not alter public banner geometry.
CDP captures must scroll the actual source-preview banner frame into the
viewport before capture and retain the recorded scroll state; a page-top or
mode-panel-only capture is not visual evidence for the source-preview
rendering.

### Batch 2F-B.1 genuine 200% continuation

The CCB-owned scenario
`local/course_banner_builder/tools/playwright/ccb-banner-public-title-accessibility-2fa.spec.js`
is run through `tools/playwright/run-ccb-2fb1.ps1`. The runner uses the active
Moodle 5.1 checkout, an external artifact root, an isolated Chrome profile,
and a named CCB fixture lease. It runs Playwright discovery first and refuses
to mutate the fixture unless exactly one B2F.1 test is selected.

The stable fixture is course 11 / CMID 12. The source category is disposable
and dynamically created; it must be removed after the run. The supervised
runner imports the saved DPAPI credential into the child process; credentials
must never be configured globally. A passing run must prove native 200% zoom,
exactly one accessible H1, exactly one contextual H2, correct accessibility
tree levels, no duplicate heading, no horizontal overflow, the 128px public
minimum, no unexpected runtime errors, and complete cleanup.

The approved passing evidence is run
`ccb-2fb1-supervised-20260727T143916775Z-9640` under
`%LOCALAPPDATA%\EasyEdu\artifacts\ccb\public-title-accessibility\supervised`.
Do not use foreground screen capture for this gate; CDP captures stay external
to avoid the workstation black-screen issue.

### Batch 2E-A.1 public sizing cells

`local/groupimport/tools/playwright/ccb-banner-public-floor-2ea.spec.js`
measures production public CSS in an isolated browser process. It confirms that
the post-theme inline runtime stylesheet and the compiled CSS fallback both
contain the shared 128px non-standard selector, then records the computed
winner, public dimensions, title/frame containment, overlay geometry, crop
style, opacity, z-index and cleanup state. It never opens or changes an
administration/modal preview.

Run one process for each format/viewport pair (Boost): `standard`,
`contentwide`, `fullwidthtop`, `fullwidthtopcompact`, `fullwidthtopinset` at
1600x900, 1024x768, 768x1024 and 390x844. Add genuine headed-browser 200%
cells for one desktop non-standard format and one restrictive mobile
non-standard format, plus a repeat mobile cell. Each cell uses course 2,
temporarily moves it from category 3 to category 8 through Moodle UI, and
asserts restoration of category, title, title settings, format and 100% zoom.

The required non-standard result is within one rendered pixel of
`clamp(128, width / ratio, max-height)`; `standard` remains on its existing
4:1 base rule. Keep full Playwright artifacts only for representative passing
cells and all failures. Site-banner ownership is covered statically unless a
reversible existing site fixture is available; do not configure a global site
banner merely to run this protocol.

`tests/hook_callbacks_runtime_css_test.php` is the matching PHPUnit coverage
for the generated runtime stylesheet. It reflects the protected CSS factory
only; it does not invoke a public hook or alter settings. When the Moodle
PHPUnit dependency is installed, run the plugin test through Moodle's PHPUnit
runner. If it is unavailable locally, run PHP lint and the documented
reflection-based runtime smoke instead, and retain the browser evidence.

#### 2026-07-22 Boost evidence

Batch 2E-A.1 passed the five-format, four-viewport matrix at 100%, genuine
200% desktop `fullwidthtop`, genuine 200% mobile
`fullwidthtopcompact`, and a repeated 100% mobile inset cell. The inline
runtime stylesheet was the observed computed winner; the compiled plugin
stylesheet contained the matching grouped fallback. All successful cells
restored category 3, title, title settings, format and browser zoom. The two
early environment-only attempts are retained as failure artifacts: an
unavailable Mustache cache write caused them to stop before fixture mutation;
space was recovered by moving only CCB-owned temporary Chrome profiles to a
recoverable D: quarantine.

## Batch 2E-B selected-source mobile preview

The opt-in specification
`local/groupimport/tools/playwright/ccb-banner-admin-mobile-preview-2eb.spec.js`
checks the selected source editor and its read-only source-chain modal without
creating, deleting, or editing a source/layer. It changes only the selected
banner format through the existing administration form and restores that
format in `finally`. Preview mode is asserted to be transient and does not
form part of the restore set.

Use a unique scenario id and a D: artifact root. The specification creates an
owned Chrome profile under that run directory, deletes it on completion, and
never falls back to C:. Keep credentials exclusively in the wrapper child
process; do not put them in a command history, artifact, source file, or report.

```powershell
$env:EASYEDU_CCB_2EB_ARTIFACT_ROOT = 'D:\EasyEdu\artifacts\ccb\admin-mobile-preview\2026-07-22'
$env:EASYEDU_CCB_2EB_SCENARIO_ID = 'ccb-2eb-matrix'
$env:EASYEDU_CCB_2EB_FORMATS = 'standard,contentwide,fullwidthtop,fullwidthtopcompact,fullwidthtopinset'
$env:EASYEDU_CCB_2EB_VIEWPORTS = 'desktop,laptop,narrow,mobile'
$env:EASYEDU_CCB_2EB_ZOOM = '100'
.\Invoke-CCBPlaywrightWithSavedCredentials.ps1 `
    -Spec ccb-banner-admin-mobile-preview-2eb.spec.js `
    -PlaywrightArgument @('--reporter=line', '--workers=1')
```

Run from `local/groupimport/tools/playwright`; `-c .` and the bare test name
are required because the shared Playwright configuration otherwise resolves a
different test directory. Run the genuine 200% browser-zoom cell separately
with `EASYEDU_CCB_2EB_ZOOM = '200'` in a headed desktop session. Retain the
JSON evidence and failure screenshots, but retain passing screenshots only
for representative cells.

The full 100% matrix covers all five formats at 1600x900, 1024x768, 768x1024
and 390x844. For every cell it records desktop/mobile frame dimensions,
logical public-policy height, scale, normalised image/title/overlay/border
rectangles, crop/fit/anchor/offset/opacity/z-index state, source form values
(with `sesskey` redacted), control semantics, and document overflow baseline.
It statically verifies that both public sizing authorities still contain the
published floor/cap policy. The live fixture currently has an inherited
original-fit image but no title, overlay, or border; absent layer kinds are
recorded rather than fabricated.

The geometry matrix opens the administration source-preview modal in a fresh
Playwright page for each measurement. This preserves the real modal/fetch
interaction while avoiding unrelated Moodle session requests left open by the
public-surface page. The page uses the active matrix viewport and is closed in
`finally` after the frame and layer measurements are captured.

Success requires the original format, browser zoom, and owned profile to be
restored/removed. `ownership.json`, `original-state.json`, `cleanup.json`, and
`artifact-summary.json` carry the run marker, repository branch/head and
cleanup result. A failure saves `failure.json` and a screenshot before cleanup.

## Reset and cleanup

- Use a disposable Moodle site or restore a database snapshot before a full
  protocol run.
- Delete CCB settings through the plugin UI only on disposable data.
- Purge Moodle caches after changing compiled SCSS or AMD build artifacts.
- Remove test draft files and generated course overview images after the run.
- Record the source ids, course ids, theme, Moodle version, browser version,
  and fixture revision in release evidence.

## Future EasyEdu platform matrix

Candidate jobs:

| Job | Moodle | Gate content |
| --- | --- | --- |
| `course-banner-builder-45` | 4.5 | Existing lint, package, PHPUnit; later stable Behat |
| `course-banner-builder-51` | 5.1 | Same checks plus current JS-heavy regression suite |
| `course-banner-builder-a11y` | 4.5 and 5.1 | Plugin-region Behat axe smoke |
| `course-banner-builder-browser` | 5.1 | Opt-in Playwright crop, guide, and responsive flows |

The browser job must remain manual until fixtures and browser timing are stable.
