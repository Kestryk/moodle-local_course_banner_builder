# Responsive

Responsive mixins provide common behaviour for Moodle plugins on tablets and
phones without forcing a single layout.

## Stack split views

```scss
.my-two-column-layout {
  @include easyedu.responsive-stack(56rem);
}
```

## Mobile action tray

```scss
.my-mobile-actions {
  @include easyedu.mobile-action-tray;
}

.my-mobile-actions__summary {
  @include easyedu.mobile-action-tray-summary;
}

.my-mobile-actions__buttons {
  @include easyedu.mobile-action-tray-buttons;

  .btn {
    @include easyedu.mobile-action-tray-button;
  }
}

@media (max-width: 35rem) {
  .my-mobile-actions {
    @include easyedu.mobile-action-tray-stacked;
  }
}
```

Use the action tray only for contextual actions that are currently available.
Avoid showing disabled actions on small screens.

The tray surface is constrained to the viewport width. In stacked mode, action
buttons wrap to a second row instead of creating horizontal scrolling, which is
safer for translated labels and dense Moodle action sets.

Desktop selection feedback should use `sticky-selection-panel` from
`components/panels.md`. On touch screens, prefer this mobile tray and hide
duplicate desktop recovery actions so the interface has only one obvious action
area.

## Mobile cards and guide panel

```scss
.my-card {
  @include easyedu.mobile-card-density;
}

.my-guided-panel {
  @include easyedu.mobile-guided-panel;
}
```

On touch screens, selection and action trays should be treated as the primary
workflow. Drag/drop can remain available but should not be required.

Long-press context menus are intentionally plugin-owned JavaScript because each
plugin needs different conflict rules with drag/drop. Recommended behaviour:

- Do not start drag feedback before the long-press threshold has resolved.
- Keep the menu open after the finger is released so actions are selectable.
- Hide unavailable actions instead of presenting many disabled options.
- Keep the tray and guided checklist from overlapping each other.

When responsive changes move highlighted targets, dispatch the public guide
refresh event after the tray or menu finishes opening. See
`docs/components/orchestration.md` for the recommended hook map.

## Expected action tray structure

```html
<div class="my-mobile-actions" hidden>
  <div class="my-mobile-actions__summary">2 selected</div>
  <div class="my-mobile-actions__buttons">
    <button class="btn btn-sm btn-primary">Move</button>
    <button class="btn btn-sm btn-outline-danger">Delete</button>
  </div>
</div>
```

If another sticky surface is visible, such as a guided checklist, the plugin
should adjust vertical offsets or stack both surfaces rather than letting them
overlap.
