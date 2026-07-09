# Course Banner Builder Integration Notes

This example describes how Course Banner Builder should consume EasyEdu UI Kit
`v0.4.0`.

## Sync

```powershell
.\sync-easyedu-kit.ps1 -IncludeGuide -IncludeDocs -TargetPluginRoots @(
  "C:\dev\Moodle 51\MoodleWindowsInstaller-latest-501\server\moodle\local\coursebannerbuilder"
)
```

## SCSS entry point

```scss
@use "easyedu" as easyedu;

@include easyedu.motion-keyframes;

.local-coursebannerbuilder {
  @include easyedu.token-defaults;
  @include easyedu.guide-shell;
  @include easyedu.guide-rich-navigation;
  @include easyedu.guide-visuals;
  @include easyedu.guided-panel;
}
```

## Suggested component mapping

| Course Banner Builder concept | EasyEdu component |
| --- | --- |
| Main editor/card containers | `panel-shell`, `panel-header`, `split-layout` |
| Layers | `object-card`, `identity-rail`, `selectable-card`, `drag-handle` |
| Image upload | `filepicker`, `file-drop-overlay`, `image-preview` |
| Layer settings modal | `settings-modal-dialog`, `settings-modal-field`, `metadata-list` |
| Source picker dropdowns | `menu-surface`, `menu-item`, `dropdown-menu` |
| Inline help | `hover-help-host`, `help-icon` |
| Guide | `guide-shell`, `guide-rich-navigation`, `guide-visuals`, `guided-panel` |

## Guide target naming

Use semantic target attributes in Course Banner Builder templates:

```html
<button data-easyedu-guide-target="create-layer">Add layer</button>
<section data-easyedu-guide-target="layer-list">...</section>
<button data-easyedu-guide-target="save-banner">Save</button>
```

Then configure the guide:

```js
init('[data-easyedu-guide-root]', {
  storageKey: 'local_coursebannerbuilder.easyedu_guide.seen',
  firstVisit: true,
  targets: {
    createLayer: '[data-easyedu-guide-target="create-layer"]',
    layerList: '[data-easyedu-guide-target="layer-list"]',
    sourcePickers: '[data-easyedu-guide-target="source-pickers"]',
    sourcePicker: '[data-source-dropdown="category"]',
    sourceParentPicker: '[data-source-dropdown="summary-sourceparent"]',
    saveBanner: '[data-easyedu-guide-target="save-banner"]'
  },
  paths: {
    basics: [
      {
        id: 'create-layer',
        title: 'Create a first layer',
        target: 'createLayer',
        completeOn: 'coursebannerbuilder:layer-created'
      },
      {
        id: 'organise-layers',
        title: 'Organise layers',
        target: 'layerList',
        completeOn: 'coursebannerbuilder:layer-moved'
      }
    ]
  }
});
```

For source-related guide steps, avoid targeting the Moodle page navigation,
generic section headers or the configured-source table. The table can be empty
when the user has not configured anything yet, so it is a poor onboarding
target. The stable targets are the actual source UI elements:

- `sourcePickers`: a plugin-owned wrapper such as
  `data-easyedu-guide-target="source-pickers"` around the category and custom
  field dropdowns;
- `sourcePicker`: `[data-source-dropdown="category"]`, the primary "Choose a
  source" dropdown;
- `sourceParentPicker`: `[data-source-dropdown="summary-sourceparent"]`, the
  configured source parent dropdown in the selected-source summary.

If a guided path needs to wait for a real submit button but visually show the
two dropdowns, keep `target` on the real action and use `highlightTarget` for
the dropdown wrapper:

```js
{
  id: 'select-source',
  title: 'Select source',
  target: 'selectSourceButton',
  highlightTarget: 'sourcePickers',
  completeOnClick: true
}
```

Do not point the step at a nav item or an empty table only because it has the
same text label.

After a real action succeeds:

```js
document.dispatchEvent(new CustomEvent('easyedu:guide-step-complete', {
  detail: {
    path: 'basics',
    step: 'create-layer'
  }
}));
```

## Crop editor contract

Course Banner Builder has a fragile image crop system that spans:

- the general/source preview;
- the add-image-layer modal;
- the edit-image-layer modal;
- the persisted layer state and draft layer state.

When this system changes, treat it as a contract-driven interaction system, not
as a local visual tweak.

### Canonical rules

1. Persist only the crop rectangle in the durable layer state:
   - `imagecropenabled`
   - `imagecropleftpercent`
   - `imagecroptoppercent`
   - `imagecropwidthpercent`
   - `imagecropheightpercent`
2. Treat crop source geometry used during editing as session-only data.
3. Do not keep crop source geometry as a second durable truth in form data,
   mirrored preview layers or saved draft settings.
4. When reopening an existing crop, rebuild the expanded source rectangle from
   the visible layer placement and persisted crop rectangle, not from a visually
   cropped DOM node.
5. If a crop session is reopened and applied without changing the crop box, the
   final visible placement must remain byte-for-byte equivalent from the user
   point of view. No incremental shrink, offset drift or recentering is
   acceptable.

### Modal preview safety rules

- Draft visual layers used as background/context must not inherit the temporary
  crop-edit layout of the active layer.
- Apply/cancel crop buttons must stay outside the crop box even when the crop
  is large or reaches preview edges.
- Releasing a crop drag over another image must not change the selected draft
  image.
- Switching from one draft image to another while a crop session is active must
  cleanly commit or cancel the current crop without polluting the next image.

### Validation checklist for crop work

When changing crop behaviour in Course Banner Builder, always retest these
scenarios:

1. First crop in the general preview.
2. First crop in add-image modal.
3. First crop in edit-image modal.
4. Second crop of the same image without moving the selection.
5. Second crop of the same image with the selection expanded again.
6. Two images in the add-image modal, crop image A, switch to B, then return to
   A.
7. Release a crop drag while another image is underneath.
8. Verify crop action buttons stay outside the crop box.

If browser automation is available, prefer a short targeted headless probe over
manual guesswork for regressions in items 4 to 7.
