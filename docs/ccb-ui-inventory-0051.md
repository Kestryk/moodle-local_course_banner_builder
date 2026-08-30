# EED-CCB-2026-0051 — UI inventory and corrective map

Date: 2026-08-30  
Scope: source-only inventory. This document records the current implementation
before further corrective adoption; it does not change PHP, AMD, CSS, markup,
runtime data, or behaviour.

## Screen map

| Screen / entry point | Source of UI | Main controls and actions | Modal / overlay surfaces | Help / guide hooks |
| --- | --- | --- | --- | --- |
| Site banner administration | `admin_manage.php` with `context=site`; title settings renderer | title enable/replace, title style, preview toolbar, save/reset | title-settings modal; layer modals; title colour dialog opened by `data-action=local-course-banner-builder-open-title-colour-dialog` | shared Guide/navigation hooks loaded by page bootstrap |
| Course banner administration | `admin_manage.php` with course/category source context | same title controls plus configured-source selection and source actions | same title/layer modal family | same shared hooks, context-specific labels |
| Activity/banner administration | `admin_manage.php` activity context | inherited/custom title controls, preview mode and title actions | title-settings modal and layer modals | same shared hooks |
| Configured sources / source visual editor | `admin_manage.php`, `local_course_banner_builder_render_source_visual_editor()` | Preview, Edit, Delete layers/source, composition mode, parent source, Collapse/expand, layer action rail, Undo/Redo, Crop/Fit/Fill and placement controls | add image layer, edit image/border/overlay, parent source, title settings, preview modal | contextual help controls in preview mode and layer-info/overrides areas |
| Settings / transfer | `classes/form/*`, `admin_manage.php` settings/transfer routes and `tours/course_banner_builder_transfer.json` | import/export/transfer settings and destructive confirmations | Moodle form/dialog surfaces plus plugin modal shells where applicable | transfer tour; shared Guide adapter |
| Slideshow/title editing | `local_course_banner_builder_render_title_settings_modal()` and `amd/src/slideshow_admin.js` | style mode, toggles, toolbar, font, colour, position, reset, Save changes | title editor and dedicated colour dialog | preview-mode help and shared guide integration |

## Existing primitive usage

- Buttons generally start from Moodle `.btn` variants and plugin classes such as
  `local-course-banner-builder-source-preview-button` and
  `local-course-banner-builder-source-preview-visibility-toggle`.
- Modal shells are Bootstrap/Moodle `.modal`, `.modal-dialog`, `.modal-content`,
  `.modal-header`, `.modal-body`, and `.modal-footer`, with plugin modifiers in
  `_modals.scss` and the generated `styles.css`.
- Shared EasyEdu Kit sources are present under `easyedu-kit-docs/` and the
  plugin's `scss/easyedu/components/` includes buttons, modals, forms, menus,
  guide, motion, focus and typography adapters. The plugin still has local
  legacy selectors, so a selector's visual similarity does not prove that it
  uses a shared primitive.
- Preview checkerboard and preview/action rails are plugin-owned classes; they
  must remain visually compatible with the Kit checkerboard and modal/action
  contracts.
- Guide/navigation AMD entry points are `easyedu_guide.js`,
  `easyedu_guide_adapter.js`, `easyedu_navigation.js` and
  `easyedu_navigation_guide.js`; CCB page behaviour is additionally wired by
  `admin_manage.js` and `slideshow_admin.js`.

## High-risk inconsistencies to address in owned corrective lots

1. **Modal identity** — every CCB modal must use the same bordered, centred
   close button and the same header/footer spacing as the EasyStud/common Kit
   modal. Audit title, colour, parent, layer and source-preview modals together.
2. **Action typography and states** — local action classes can override Kit
   font weight, underline-on-hover, icon alignment, height and focus treatment.
   Verify normal/hover/active/focus and narrow widths, including menu items.
3. **Dropdown anchoring** — parent-source and colour choices need a containing
   block that keeps the list below its trigger and inside the modal viewport;
   avoid portal/z-index changes that reintroduce clipping or veil overlap.
4. **Preview and layer frames** — inherited Border/Overlay rows, editable rows,
   checkerboards, action rails and loading states need one frame contract;
   distinguish inherited/locked/read-only states without changing data logic.
5. **Responsive controls** — source preview mode, title editor toolbars,
   pagination-like action rows and long labels need overflow tests at desktop,
   intermediate and mobile widths. Preserve keyboard focus and touch targets.
6. **Motion** — transitions for modal open/close, source children
   collapse/expand, preview mode changes and layer info disclosures should be
   added only through the shared motion contract and honour reduced motion.
7. **Colour picker** — the dedicated CCB Kit-style colour dialog must remain
   separate from the browser/OS picker and preserve temporary Apply/Cancel/
   Escape semantics and focus return.
8. **Crop history** — transformation history must retain crop geometry rather
   than restoring the pre-crop outer dimensions; this is a behaviour-sensitive
   lot and remains separate from Filemanager lifecycle work.
9. **Guides** — tour selectors and explanatory text must be checked against all
   four contexts and responsive DOM states; stale show-in-interface/guided-path
   selectors should be removed or updated only in a dedicated guide lot.

## Ownership / next lots

- CCB-0051 owns this inventory only.
- Modal identity and close-button parity: extend the existing CCB-0042 follow-up
  owner, with a new narrowly scoped source lot if required.
- Crop geometry/history: CCB-0043 RF7 follow-up; do not mix with Filemanager
  lifecycle (CCB-0056).
- Cross-plugin Kit primitive parity: UI Kit owner first, then CCB/EasyStud
  consumer lots.
- Guide inventory and responsive tour repair: dedicated Guide lot after this
  inventory; no selector changes are included here.

## Validation boundary

Static validation for this lot is document-only: verify the file is present,
the working tree contains no product changes, and `git diff --check` passes.
Preview, browser, cache, fixture, lease and runtime actions are explicitly out
of scope.
