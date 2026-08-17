# Tooltips

EasyEdu tooltips are custom hover/focus help bubbles. They should be used when a
plugin needs a consistent visual style and must avoid duplicated native browser
`title` tooltips.

## Mixins

```scss
.my-control {
  @include easyedu.hover-help-host;
}

.my-help-icon {
  @include easyedu.help-icon;
}

.my-custom-popover {
  @include easyedu.popover-surface;
}

.my-custom-popover.is-warning {
  @include easyedu.popover-warning-variant;
}

@include easyedu.positioned-popover-arrows(".my-custom-popover");
```

## Expected HTML

```html
<button data-easyedu-hover-help="Create a new layer">
  <span class="fa fa-plus" aria-hidden="true"></span>
</button>
```

## Notes

- Keep the help text short for controls.
- Use long tooltip variants only for explanatory text.
- Remove native `title` attributes when they duplicate the custom bubble.
- Use `popover-surface` plus `positioned-popover-arrows()` when a plugin
  creates Moodle-like hover popovers in JavaScript. This keeps custom popovers
  visually aligned with EasyStud while preserving plugin-specific positioning
  logic.
- For a labelled status control, attach the explanatory popover to the complete
  control wrapper instead of adding a separate `?` button. The wrapper must not
  cancel the nested control action, and disabled controls must remain covered
  by the wrapper hover area.
- Use `popover-warning-variant` for destructive or irreversible consequences.
  It keeps the standard EasyEdu gradient, spacing, arrow and typography while
  changing only the semantic colour and shadow tokens.
- When JavaScript clamps a popover against a viewport edge, expose the measured
  trigger anchor through `--local-course-banner-builder-popover-arrow-left` (or
  an equivalent plugin variable). The shared arrow mixin must consume that
  value instead of resetting the arrow to 50% of the popover surface.

## Import Contract

Tooltips are an enhancement layer, not the primary label. A consuming plugin
must keep visible button/menu labels or accessible names, then add
`data-easyedu-hover-help` only when the user benefits from extra context.

Required behaviour:

- Show on hover and keyboard focus.
- Do not show on click.
- Do not duplicate the browser native `title` bubble.
- Keep short control text compact; use the long variant only for explanatory
  help that cannot fit in the surrounding UI.
- Remove tooltip attributes from actions once they move into a visible menu,
  because menu item labels already explain the action.

Common mistakes to avoid:

- Adding both `title` and `data-easyedu-hover-help`.
- Leaving hover bubbles on compact overflow menu items.
- Using a tooltip as the only accessible name for an icon-only control.
- Hard-coding tooltip colours in a plugin instead of using the kit mixins.
- Restyling plugin popovers locally instead of using the EasyEdu popover
  primitive.
- Appending a question-mark trigger beside every labelled status control when
  the control itself can expose the same contextual help.
