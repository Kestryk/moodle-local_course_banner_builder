# EasyEdu UI Harmonisation Audit

Course Banner Builder is visually moving toward the EasyStud / EasyEdu UI
language, but the plugin has interaction-heavy preview editors. The migration
must stay incremental and preserve all plugin-owned DOM hooks.

## Current baseline

- EasyStud reference surface: simplified student management, compact panels,
  rounded action controls, custom help bubbles, guide modal and controlled
  density.
- Course Banner Builder high-risk surfaces: image crop/resize, draggable
  previews, title editor, slideshow editor, layer reorder tables, undo/redo and
  sticky preview modals.
- The first pass adds the embedded EasyEdu SCSS kit and a scoped
  `_easyedu-harmonisation.scss` adapter instead of rewriting existing CCB
  component files.

## Applied mapping

| CCB surface | EasyEdu primitive |
| --- | --- |
| Admin cards and slideshow cards | `admin-panel` |
| Layer/source tables | `layer-table` |
| Preview toolbar icons | `banner-preview-toolbar` |
| Preview side action buttons | `banner-preview-side-actions` |
| Wide modal shells | `modal-with-preview` |
| Linked range and numeric inputs | `linked-range-number` |
| Colour fields | `color-picker-field` |
| Help icons and bubbles | `help-tooltip`, `tooltip-surface` |

## Safety rules

- Do not rename `data-*`, ids or classes consumed by `amd/src/admin_manage.js`
  and `amd/src/slideshow_admin.js`.
- Do not alter transform, z-index or inset calculations during a visual-only
  pass.
- Validate crop, resize, drag, border sliders, title dragging and slideshow
  modal interactions after each sizeable visual change.
- Keep Moodle 4.5 and 5.1 compatibility in mind when adding CSS or JS APIs.

## Next passes

1. Compare CCB admin pages and EasyStud with Playwright screenshots.
2. Refine slideshow admin spacing, toggles and action hierarchy.
3. Harmonise modal headers/footers and sticky preview layouts once screenshots
   confirm the first pass has not disturbed editor interactions.
4. Backport reusable primitives to EasyStud only after CCB-specific patterns are
   stable in the kit.
