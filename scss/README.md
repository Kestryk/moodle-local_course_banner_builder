# SCSS structure

Moodle loads `styles.css` for this local plugin. The `scss/` folder is the
organised source structure for future style work, split by component to avoid
the monolithic CSS file growing further.

Build command from the plugin root:

```powershell
powershell -ExecutionPolicy Bypass -File .\scss\build.ps1
```

This requires the Dart Sass CLI (`sass`) to be available on the PATH. The build
script writes to `styles.css`, which is the file Moodle loads.

Current component entry points:

- `components/_base.scss`: base plugin wrapper defaults.
- `components/_slideshow-runtime.scss`: public slideshow overlay rendering,
  navigation, labels, and action styles.
- `components/_native-banner-core.scss`: native banner shells, public banner
  formats, and course-card thumbnail sizing.
- `components/_native-banner.scss`: focused additions for Moodle-generated SVG
  course banners.
- `components/_admin-switcher.scss`: top-level admin view switcher.
- `components/_banner-format-modal.scss`: banner format picker modal and format
  skeleton previews.
- `components/_admin-layout.scss`: main administration layout, source picker,
  dropdowns, preview editor, and modal form layout.
- `components/_preview-opacity.scss`: opacity controls shared by the source
  preview and image layer modals.
- `components/_admin-status.scss`: course and site banner status controls.
- `components/_source-chain.scss`: source hierarchy chain affordances.
- `components/_help-bubble.scss`: compact table help bubble.
- `components/_layer-table.scss`: source layer table and image-detail summaries.
- `components/_modal-preview-actions.scss`: modal preview side action list.
- `components/_admin-controls.scss`: admin action controls, source visual
  editor controls, layer cards, and source table helpers.
- `components/_modal-shell.scss`: responsive preview action layout and layer
  modal shell sizing.
- `components/_border-preview.scss`: border preview sides, corners, holes, and
  rendered border geometry.
- `components/_preview-editor.scss`: preview editor controls, resize handles,
  alignment guides, thumbnails, and selected-source details.
- `components/_slideshow-admin.scss`: slideshow admin panels, preview editor, and
  toolbar controls.
- `components/_tours.scss`: rich-content styles used inside Moodle user tours.
- `components/_responsive.scss`: responsive adjustments for admin tables,
  previews, modals, and slideshow text controls.

When changing a component, keep the SCSS and the corresponding `styles.css`
rules in sync until a build step is added to the plugin packaging workflow.
