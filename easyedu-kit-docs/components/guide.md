# Guide

The EasyEdu guide kit has two layers:

- `guide-shell`: modal, launcher, slides, basic navigation, highlight and basic
  checklist.
- rich optional layers: navigation thumbnails, visual demos and guided panel.

## Base guide

```scss
.my-plugin {
  @include easyedu.guide-shell;
}
```

The guide modal size is standardised by public tokens so every EasyEdu plugin
can share the same proportions while still allowing Moodle themes to override
them:

```scss
.my-plugin {
  --easyedu-guide-modal-width: 72rem;
  --easyedu-guide-modal-height: min(90vh, 46rem);
  --easyedu-guide-modal-max-height: calc(100vh - 2rem);
  --easyedu-guide-modal-min-height: min(42rem, calc(100vh - 2rem));
}
```

## Rich guide navigation

```scss
.my-plugin {
  @include easyedu.guide-rich-navigation;
}
```

Use the rich kit template as the default source for Moodle plugins. It includes
the EasyStud-grade header, subtitle, map flow, navigation cards, progress bar,
slide icons, visual flow cards and footer step count placement. Avoid copying a
reduced/base guide template into plugins unless the product intentionally needs a
minimal guide.

The root template accepts an optional `rootclass` value. Moodle plugins should
pass a plugin-specific class there, for example
`local-course-banner-builder-easyedu-guide`, so SCSS mixins remain scoped while
the generic `.easyedu-guide` hooks stay stable for JavaScript.

Expected classes:

```html
<div class="easyedu-guide-nav-wrap">
  <button class="easyedu-guide-nav-arrow easyedu-guide-nav-arrow--prev">...</button>
  <nav class="easyedu-guide-nav">
    <button class="easyedu-guide-nav-item has-guided-path" data-easyedu-guided-label="Guide">
      <span class="easyedu-guide-nav-item__icon" aria-hidden="true">
        <span class="fa fa-route" aria-hidden="true"></span>
      </span>
      <span class="easyedu-guide-nav-copy">
        <small>Guided</small>
        <span>Create a first structure</span>
      </span>
    </button>
  </nav>
  <button class="easyedu-guide-nav-arrow easyedu-guide-nav-arrow--next">...</button>
</div>
```

Guided-path nav cards should use a short badge label, normally `Guide`, through
`data-easyedu-guided-label`. Longer explanatory wording belongs in the card
subtitle or guided panel, not in the badge, otherwise the badge can overflow the
navigation card.

Expected interaction:

- Left and right arrow keys move to the previous or next guide slide while the
  guide modal is open.
- Home and End move to the first or last guide slide.
- Escape closes the guide modal.
- Vertical mouse-wheel movement over the slide navigation should scroll the
  horizontal navigation rail without showing a native scrollbar.
- The active navigation item should be kept in view when slides change.
- Navigation rail arrows must be disabled when the rail is already at the start
  or end of the scroll range. Recalculate this after opening the modal, after
  nav scrolling, after slide changes and after window resizing.
- Footer progress text should use the visible `Step X of Y` wording from the
  language string/template and sit directly next to the Previous/Next buttons.
- `Show in the interface`, guided-path start buttons, checklist return buttons
  and footer next/previous actions are primary guide actions. Keep them on the
  shared EasyEdu blue surface unless a product has an explicit accessibility
  reason to choose another semantic colour.
- Checklist return actions must use the action label (`guidereturnbutton`, for
  example `Return to guide`) rather than the return-panel title. This lets
  products keep a branded panel title such as `EasyStud guide` without leaking
  that title into the button text.
- Guided checklist rows and messages must never create nested horizontal
  scrolling. Long step labels, completion messages and translated strings should
  wrap inside the fixed guided panel width.

Guided checklist steps may need to open a Moodle modal before their target is
available. In that case, define `open` with either a configured target key or a
selector, and optionally `openDelay` in milliseconds:

```js
{
  id: 'preview',
  title: 'Interactive preview',
  target: 'previewModal',
  open: 'appearanceButton',
  openDelay: 650
}
```

When the step is clicked, the guide triggers the opener, waits for the modal,
scrolls/highlights the target, and marks the step complete. Keep the guided
panel above Moodle modals so the checklist remains usable after the modal opens.

Checklist steps can also open several dependent controls before highlighting
the final field. Use this when a step must switch view, expand an accordion and
then reveal an inline panel:

```js
{
  id: 'add-item',
  title: 'Add an item',
  target: 'inlinePanel',
  highlightTarget: 'inlineTextarea',
  open: [
    {target: 'layoutStructure', delay: 320},
    {target: 'firstSectionToggle', delay: 360},
    {target: 'firstSectionAddButton', delay: 260}
  ]
}
```

Each `open` item can be either a target key, a selector, or an object with
`target`, `selector`, `open` and `delay`. The kit skips controls that are
already open through `aria-expanded="true"` or `aria-pressed="true"` so a
checklist step does not accidentally close a panel that is already visible.

Configured targets may be arrays of selectors. The resolver picks the first
visible match, then falls back to the first available match. Prefer this pattern
for repeated cards, filters or creation panels that can appear in several
layouts:

```js
targets: {
  firstEditableGroup: [
    '[data-view="participants"] [data-group-card]:not([hidden])',
    '[data-view="structure"] [data-group-card]:not([hidden])'
  ]
}
```

Guide scrolling should keep the target close to the top of the safe viewport
without hiding it under sticky Moodle navigation. Avoid centring large targets:
it can send the user past the controls they need, especially for drag/drop or
context-menu practice steps. If the target lives inside a scrollable plugin
panel, the kit scrolls that internal panel first, then aligns the page viewport
after the inner motion has started settling. This avoids competing native
smooth-scroll animations, which otherwise make checklist focus transitions feel
jumpy. The guide owns this motion with a requestAnimationFrame-based animation
and falls back to immediate scrolling when reduced motion is active. Plugins
should therefore put checklist targets on the field, card or control the user
actually needs, not only on an outer column.

## Visual demos

```scss
.my-plugin {
  @include easyedu.guide-visuals;
}
```

Visual demos are learning scenes, not decorative card collections. The shared
guide uses a soft canvas with subtle background variation, sparse outlines and
motion to explain a workflow. Avoid wrapping every label in a bordered card or
badge. A border should communicate selection, focus, a drop target, an important
functional grouping or a warning.

Useful classes:

- `.easyedu-guide-visual`
- `.easyedu-guide-mini-card`
- `.easyedu-guide-mini-card--participant`
- `.easyedu-guide-mini-card--group`
- `.easyedu-guide-mini-card--grouping`
- `.easyedu-guide-flow-arrow`
- `.easyedu-guide-demo-cursor`
- `.easyedu-guide-demo-drop`

Reusable scene families currently covered by the kit:

- `visualcards`: compare object types such as participant, group, grouping,
  source, layer and banner.
- `visualcarddetail`: explain one object and its metadata/details.
- `visualassignment`: show two participants or groups being organised into a
  shared destination, useful for participant -> group -> grouping and
  source/layer inheritance examples.
- `visualfiltersdemo`: show search, filters, result selection and reset-like
  actions.
- `visualpaste`: show text pasted into a field and converted into recognised
  chips.
- `visualcontextmenu`: explain right click / long press menus with a pointer
  and menu reveal animation.
- `visualactionflow`: show multiple selection, an action button and a
  destination modal.
- `visualdragdrop`: show a dragged object, compatible target and optional
  selection/menu alternatives.
- `visualformula`: explain bulk creation formulas such as `Group #*4`.
- `visualsteps`: show progressive creation, success, warnings, empty-course
  guidance or workflow summaries through a shared step layout.
- `visualkeys`: show keyboard shortcuts without depending on animation.

The scene motion is part of the teaching contract. Context-menu scenes should
show the pointer movement, click ring and menu reveal; drag/drop scenes should
show the card moving toward the compatible drop target; action-flow scenes
should nudge the relevant action. Consumers should not disable these animations
except through the shared reduced-motion media query.

The kit template also supports data-driven visual blocks so plugins do not need
to hard-code new slide HTML for common learning slides:

```json
{
  "visualtype": "workflow",
  "visualcards": {
    "items": [
      {"type": "source", "icon": "fa-sitemap", "title": "Source", "meta": "Rule"},
      {"type": "layer", "icon": "fa-layer-group", "title": "Layers", "meta": "Visual stack"}
    ]
  },
  "visualsteps": {
    "items": [
      {"state": "source", "icon": "fa-check", "label": "Choose source"},
      {"state": "layer", "icon": "fa-image", "label": "Add layer"}
    ]
  },
  "visualkeys": {
    "items": [
      {"key": "Tab", "label": "Move between controls"},
      {"key": "Space", "label": "Activate focused item"}
    ]
  }
}
```

Use these sections for reusable explanatory slides such as workflow, keyboard
shortcuts, source chains, modal flow, drag/drop and checklist previews. If a new
visual pattern is useful in more than one plugin, add it here before adding a
plugin-local visual.

Implementation guidance:

- Provide labels, icons, semantic type names and step data only; do not copy the
  visual HTML into a plugin.
- Prefer semantic `type` values (`participant`, `group`, `grouping`, `source`,
  `layer`, `banner`) so the kit can apply consistent colour and identity rails.
- Keep scene copy short. Longer explanations belong in the slide text above the
  scene.
- Use one main scene per slide. If the slide needs both a visual scene and a
  guided checklist launcher, keep the launcher compact and connected to the
  explanation rather than adding another large card.
- The scene must remain understandable when `prefers-reduced-motion: reduce`
  disables animation.
- Guide buttons (`Show in the interface`, `Start guided path`, footer
  Previous/Next, `Return to guide`) must keep the kit button classes and allow
  text wrapping. Primary guide actions are intentionally forced back to the
  EasyEdu blue surface because Moodle/Bootstrap button variables can otherwise
  turn checklist or guided-path buttons transparent in plugin contexts. Do not
  force `white-space: nowrap` in plugin overrides; long Moodle language strings
  should wrap inside the button instead of overflowing.
- `Show in the interface`, `Start guided path` and footer Previous/Next share
  the compact guide action contract: `--easyedu-guide-action-radius`,
  `--easyedu-guide-action-padding` and `--easyedu-guide-action-min-height`.
  Plugins must not replace these controls with square, generously padded Moodle
  buttons; override the variables at the guide root only when a product needs a
  documented density variant.
- When the launcher is nested in an administration navigation rail, apply the
  navigation-action mixin to direct child buttons only. A descendant `.btn`
  selector also reaches buttons inside the guide modal and replaces their
  rounded action contract with the square navigation-tab treatment.
- Guided checklist panels should not create nested scrollbars for normal
  three-to-five-step paths. Keep step content concise and let the panel size
  naturally; only add plugin-local scrolling for exceptionally long custom
  paths after visual validation.

## Guided path slide cards

Slides with `guidedpath` should render a connected card, not a detached button.
Use optional `guidedpathtitle`, `guidedpathcontent` and `guidedpathsteps` to
explain what the path will do:

```json
{
  "guidedpath": "create-source",
  "guidedpathtitle": "Create and select a source",
  "guidedpathcontent": "The checklist opens the source controls and keeps the next action visible.",
  "guidedpathsteps": {
    "items": ["Choose a source", "Select it", "Review the preview"]
  }
}
```

The card uses `.easyedu-guide-guided-card`, keeps the start action attached to
the explanation and should be preferred over plugin-owned guided path previews.

The green guided-path frame is available as a reusable surface mixin:

```scss
.my-guided-card {
  @include easyedu.guide-guided-path-card-surface;
}
```

Use this only for guided-path launch surfaces or unlock-path panels. Keep the
centred gradient surface and soft green inset line intact so users recognise the
same "guided action is available" affordance across EasyEdu plugins. The surface
does not use an extra left rail; the stronger green background carries the
affordance without visually competing with the slide content. Guided-path cards
share the same default width as explanatory visual blocks so slide content does
not visually shrink when a path launcher appears under an explanation.

## Guided panel

```scss
.my-plugin {
  @include easyedu.guided-panel;
}
```

The guided panel supports left/right docking, minimised state and complete state:

```html
<aside class="easyedu-guided-panel is-docked-right">
  <header class="easyedu-guided-panel__header">...</header>
  <div class="easyedu-guided-panel__steps">
    <button class="easyedu-guided-panel__step is-complete">
      <span class="easyedu-guided-panel__index">1</span>
      <span>
        <strong>Create a group</strong>
        <small>Use the creation field, then validate.</small>
      </span>
    </button>
  </div>
  <div class="easyedu-guided-panel__message is-complete">
    <span class="fa fa-check-circle" aria-hidden="true"></span>
    <span>All steps are complete.</span>
  </div>
  <footer class="easyedu-guided-panel__footer">
    <button class="btn btn-outline-primary btn-sm easyedu-guided-panel__return">
      Return to guide
    </button>
  </footer>
</aside>
```

The generic AMD module now docks the checklist away from the highlighted target
when a user clicks a checklist step or "show in the interface". It also refreshes
the highlight during scroll and resize events so the outline follows moving UI.
It updates the active step, shows step feedback, switches the minimise icon
between minus and expand, and turns the message block into the green success
state when every step is complete.

When a slide uses "show in the interface", the guide modal should close while
preserving a fixed viewport highlight on the target. A sticky return panel lets
the user reopen the guide without losing their place:

```html
<aside class="easyedu-guide-interface-return" data-easyedu-guide-interface-return hidden>
  <div class="easyedu-guide-interface-return__text">
    <strong>Return to guide</strong>
    <span>The highlighted interface area remains selected while the guide is hidden.</span>
  </div>
  <div class="easyedu-guide-interface-return__actions">
    <button class="btn btn-outline-primary btn-sm easyedu-guide-interface-return__button"
            data-easyedu-guide-interface-return-button="1">
      <span class="fa fa-question-circle" aria-hidden="true"></span>
      <span>Return to guide</span>
    </button>
    <button class="btn btn-outline-secondary btn-sm easyedu-guide-interface-return__dismiss"
            data-easyedu-guide-interface-return-dismiss="1"
            aria-label="Dismiss return to guide">
      <span class="fa fa-times" aria-hidden="true"></span>
    </button>
  </div>
</aside>
```

The highlight is intentionally `position: fixed`; target calculations should use
`getBoundingClientRect()` coordinates directly so the selector remains anchored
correctly while the page scrolls.

The return panel and its highlight are temporary. They auto-hide after a short
delay, matching EasyStud, and the highlight is cleared at the same time. If the
user dismisses the return panel manually, the highlight must be cleared
immediately. Opening the guide normally while the temporary return panel is
active must also clear the old highlight. Guided checklists keep the checklist
panel visible, but the highlighted outline is temporary: after a short delay the
selector disappears while the guided checklist remains available.

The interface return panel uses a two-column layout on desktop: explanatory
text first, actions second. Keep the action column `max-content` so translated
button labels do not overlap the explanatory text. On narrow screens it stacks
into one column.

The highlight layer must stay below Moodle fixed navigation. Use the kit layer
contract instead of raising plugin-local `z-index` values: highlight below
Moodle nav, return/checklist panels above ordinary content, guide modal above
the page while open.

## Show In Interface Button

Slides that point to real UI elements should use the shared show button:

```html
<button class="btn btn-outline-primary btn-sm easyedu-guide-slide__show">
  <span class="fa fa-location-arrow" aria-hidden="true"></span>
  <span>Show in the interface</span>
</button>
```

## JavaScript contract

The reusable AMD foundation handles base modal and checklist behaviour. Rich
plugins should complete real actions using events:

```js
document.dispatchEvent(new CustomEvent('easyedu:guide-step-complete', {
  detail: {
    path: 'basics',
    step: 'create-layer'
  }
}));
```

When a target moves after an accordion, pagination, filter or Ajax update,
plugins should ask the guide to recalculate the outline:

```js
document.dispatchEvent(new CustomEvent('easyedu:guide-refresh-highlight', {
  detail: {
    target: 'createLayer',
    dock: true
  }
}));
```

Use `target` to point to either a configured target key or a CSS selector. Omit
`target` to refresh the currently highlighted element. Use `dock: false` only
when the plugin intentionally wants to keep the checklist on its current side.

Checklist steps can separate the real action target from the visual highlight.
Use `target` for the element that completes the action, and `highlightTarget`
for the area the user should look at:

```js
{
  id: 'select-source',
  title: 'Select a source',
  target: 'selectSourceButton',
  highlightTarget: 'sourcePickers',
  completeOnClick: true
}
```

This is useful when a step should complete after a submit button click but the
helpful visual area is a wrapper containing dropdowns, filters or creation
controls. Do not target an empty results table, page navigation item or generic
heading when a stable control wrapper exists.

Starting a guided path must immediately open and highlight its first available
step. The user must not need to click the first checklist item again. The
highlight layer remains above Moodle modal content but below the guided
checklist so checklist controls are never masked.

For source-selection interfaces, prefer a stable wrapper such as:

```html
<div data-easyedu-guide-target="source-pickers">
  ...
</div>
```

Then expose both action and visual targets:

```js
targets: {
  sourcePicker: '[data-easyedu-guide-target="source-pickers"]',
  selectSourceButton: '[data-action="plugin-submit-source"]'
}
```

Use `target: 'selectSourceButton'` when the real action is the submit button,
and `highlightTarget: 'sourcePicker'` when the useful visual area is the source
dropdown wrapper. Avoid using configured-source tables as guide targets when
they can be empty before setup.

For slide-level "Show in the interface" buttons, plugins may also provide
`targetselector`. This is the concrete CSS selector written into
`data-easyedu-guide-show-target` while keeping `target` as the semantic key used
by prerequisites, checklists and plugin configuration. Prefer `targetselector`
when the target key is known server-side and the button should remain robust if
the JavaScript config is rebound or refreshed.

```json
{
  "target": "adminNav",
  "targetselector": "[data-easyedu-guide-target=\"adminNav\"]"
}
```

## Conditional slides and guided paths

Some guide slides are only useful after the user has created or selected an
object in the current view. For example, a "general preview" slide should not be
available in a banner editor before a source exists.

Slides can declare a prerequisite with `requires`. The value can be either a
configured target key or a CSS selector, using the same resolution rules as
`target`.

```json
{
  "title": "General preview",
  "target": "visualEditor",
  "requires": "visualEditor",
  "requiresbadge": "Locked",
  "requireslabel": "Source",
  "requirestitle": "Create or select a source first",
  "requirescontent": "The preview slide needs a source to exist before it can point to the real interface.",
  "unlockpath": "source-setup",
  "unlocklabel": "Create a source"
}
```

When the prerequisite cannot be found:

- the navigation card receives `is-locked`;
- next/previous and keyboard navigation skip the card;
- clicking the card opens the slide in a temporary locked state;
- `requiresbadge` is shown as a compact one-word badge in the navigation card;
- the locked slide content explains why it is blocked and can start an unlock
  checklist through `unlockpath`;
- any guided checklist step using `requires` is disabled and displays its
  `requiresLabel` text.

Use a very short badge label, such as "Locked". Long explanations belong in
`requirestitle` and `requirescontent`; do not put them in the navigation card,
otherwise translated labels can overflow the thumbnail. Put a creation/setup
slide before locked slides so the user can immediately understand how to unlock
the next path.

The reusable template expects these optional fields on each slide:

| Field | Purpose |
| --- | --- |
| `requires` | Target key or selector that must exist before the slide is available. |
| `requiresbadge` | Short badge shown on the locked navigation card. Defaults to `guiderequiresbadge`. |
| `requirestitle` | Title of the locked-state panel inside the slide. |
| `requirescontent` | Longer explanation shown inside the locked-state panel. |
| `unlockpath` | Guided path to start from the locked-state panel. |
| `unlocklabel` | Button label for the unlock path. |

Required template hooks:

```html
<section data-easyedu-guide-slide="2" data-easyedu-guide-requires="visualEditor">
  <div class="easyedu-guide-slide__locked">
    <h4>{{requirestitle}}</h4>
    <p>{{requirescontent}}</p>
    <button class="easyedu-guide-slide__unlock" data-easyedu-guide-start-path="{{unlockpath}}">
      {{unlocklabel}}
    </button>
  </div>
</section>
```

Unlock paths can be visually distinguished with `unlockPaths` in the guide
configuration:

```js
init('[data-easyedu-guide-root]', {
  unlockPaths: ['source-setup']
});
```

Keep unlock paths separate from normal practice paths. For example, a
`configured-sources` path can teach the source list with the standard checklist
style, while `unlock-source-preview` can use the amber unlock style and explain
which blocked slide it unlocks.

Use `pathLabels` when an unlock path should name the blocked slide:

```js
init('[data-easyedu-guide-root]', {
  pathLabels: {
    'unlock-source-preview': 'Unlock: General preview'
  }
});
```

Checklist steps can depend on earlier checklist steps with `requiresStep`. The
dependent step stays disabled until the required step is complete and can show a
short requirement label:

```json
{
  "id": "select-source",
  "title": "Select source",
  "target": "selectSourceButton",
  "requiresStep": "choose-source"
}
```

Checklist steps may separate the real action target from the visual highlight
target:

```json
{
  "id": "select-source",
  "title": "Select source",
  "target": "selectSourceButton",
  "highlightTarget": "sourcePickers",
  "requiresStep": "choose-source",
  "completeOnClick": true
}
```

Use `target` for the element that should complete the action, such as a submit
button. Use `highlightTarget` for the clearer visual area, such as a wrapper
around related dropdowns. This prevents guides from highlighting empty result
tables or unrelated navigation simply because their text labels are similar.
`showTarget` is accepted as an alias for `highlightTarget` when plugin code
already uses that wording.

Locked checklist steps receive `is-locked`, `aria-disabled="true"` and
`data-easyedu-guide-lock-message`. The kit displays that lock message as a
small overlay on the blocked step. Locked checklist steps use the same subtle
striped language as locked navigation cards so the user recognises them as
temporarily unavailable rather than broken. For `requiresStep` dependencies, the
overlay should show the title of the required step so the user can immediately
understand what must be completed first.

When a real interface action reloads the page, use `completeOnClick` on the
step that targets the real button or link. The guide stores the checklist path,
active step, completed steps and active slide before the browser follows the
click. After reload, the same checklist is restored and can show the completed
state.

```json
{
  "id": "select-source",
  "title": "Select source",
  "target": "selectSourceButton",
  "completeOnClick": true
}
```

Do not mark `completeOnClick` steps as complete from the checklist click alone.
The kit intentionally waits for the real click on the target element so the
guided path represents actual user progress.

The target may match several equivalent controls, for example every selectable
source option in a dropdown. In that case the click detector must accept the
actual clicked element with `closest(selector)` rather than resolving only the
first matching node with `querySelector()`.

Checklist progress is transient. Closing a checklist, returning to the main
guide, or reopening the guide from the launcher must clear the active checklist,
highlight and return panel. Persist checklist state only when a real
`completeOnClick` action is expected to reload the page.

## Highlight and show-in-interface behaviour

The kit highlight used by checklist steps and "show in the interface" is
viewport anchored. It uses a fixed overlay that is recalculated after scrolling,
resizing, modal opening/closing, CSS transitions, animations and
plugin-dispatched refresh events. This avoids the fragile absolute-position
behaviour where the highlighted outline drifts away from the selected DOM
element while the page scrolls.

The overlay node is appended to `document.body`, not inside the plugin guide
launcher root. This avoids transformed, clipped or very small plugin containers
becoming the containing block for the fixed highlight. The CSS for
`.easyedu-guide-highlight` is therefore emitted globally by the guide mixin,
while the visual variant is applied through a class on the overlay node.

The implementation must stay deterministic:

- keep a single active highlighted target;
- refresh through a single `requestAnimationFrame` scheduler;
- clear the active target, timer and visual highlight through the same cleanup
  path;
- do not use competing hard timers or persistent suppressed states;
- do not observe the whole document with an unbounded `MutationObserver`.

When a target is shown, the reusable AMD also applies
`is-easyedu-guide-highlight-target` to the selected DOM element and removes it
from the previous target. The fixed overlay remains the main visual highlight;
the class exists so consuming plugins can attach non-destructive local behaviour
or test the currently selected guide target.

The recommended pattern is:

- resolve the target from a configured target key or a selector;
- close the guide modal while preserving the highlight;
- scroll the target into view;
- dock the checklist away from the highlighted target;
- show the sticky return-to-guide card;
- refresh the highlight after plugin UI transitions with
  `easyedu:guide-refresh-highlight`.

The generic AMD listens to `scroll`, `resize`, `transitionend`,
`animationend`, `shown.bs.modal` and `hidden.bs.modal`. Plugins should still
dispatch `easyedu:guide-refresh-highlight` after intentional Ajax or view-state
changes so the refresh happens at the semantic moment, not only after the
browser reports movement.

After `scrollIntoView`, the module performs a short refresh burst. This keeps the
fixed highlight aligned while smooth scrolling and layout settling are still in
progress, without running a permanent polling loop.

Checklist clicks should not trigger Moodle toast notifications by default. Use
the guided panel message area for step feedback, and reserve toasts for real
plugin actions such as save, create, delete or failed permission checks.
Checklist highlights are temporary too, but only the highlight disappears: the
checklist panel remains visible. Show-in-interface highlights disappear with the
sticky return panel. Opening the main guide, returning to the guide, dismissing
the return panel or closing a checklist must clear the current highlight.

Use `highlightStyle: 'pulse-blue'` when a plugin wants the animated blue Course
Banner Builder highlight style. Keep the behaviour shared; only the visual style
should vary by plugin.

## Minimized guided checklist

When the guided checklist is minimized, the header remains visible and must show
the current pending step, not only the generic checklist title. The reusable AMD
updates `[data-easyedu-guide-checklist-title]` with
`Guided path: {active step title}` and
`[data-easyedu-guide-checklist-subtitle]` with the visited counter. Plugins can
localise these two parts through `labels.guidedPath` and `labels.visited`.
The close action is hidden in this minimized state: users can expand the panel
again before closing, which prevents a tiny persistent close icon from floating
beside the compact checklist.

Recommended plugin-owned hooks:

- Add a stable `data-easyedu-guided-target` selector to slides or guided steps
  that can point to a real interface element.
- Dispatch `easyedu:guide-step-complete` after the user performs the real
  action, not only after they click the checklist item.
- Dispatch `easyedu:guide-refresh-highlight` after transitions that move the
  highlighted element, such as expanding a card, changing page or injecting Ajax
  results.
- Keep demo-only slides independent from real Moodle data so the guide still
  teaches something when a course is empty.
- If a highlighted target can move because of expansion, pagination or Ajax,
  refresh the highlight after that UI transition completes.

Guide scrolling follows the shared motion policy. Place the guide inside a
root carrying `data-easyedu-motion-policy`, or add `easyedu-motion-disabled` to
the Moodle page body. Navigation, target scrolling and return-to-interface
movement then switch to immediate scrolling when the administrator disables
animations or the visitor requests reduced motion. Pedagogical scenes must also
provide a meaningful static state through their reduced-motion CSS.

For plugins with many moving parts, see `docs/components/orchestration.md` for a
complete hook map covering view toggles, filters, pagination, Ajax updates,
responsive trays and nested accordions.

## Checklist completion ownership

Every checklist step must state who owns its completion:

- `completionMode: 'informational'` allows the checklist item itself to complete
  the step after the target has been shown.
- `completionMode: 'action'` waits for a successful interface action.
- `completionMode: 'event'` waits for `easyedu:guide-step-complete` or the
  event declared in `completeOn`.
- `completionMode: 'reload'` is reserved for a real action that reloads the
  page and therefore needs controlled checklist persistence.
- `waitForCompletion: true` is supported as a compatibility alias for action
  or event-driven steps.

Action, event and reload steps must never complete when their checklist row is
clicked. The owning plugin dispatches the completion only after the underlying
operation succeeds:

```js
root.dispatchEvent(new CustomEvent('easyedu:guide-step-complete', {
    bubbles: true,
    detail: {
        path: 'first-structure',
        step: 'create-structure',
    },
}));
```

Use stable string step identifiers. Numeric indexes are accepted for legacy
bridges but should not be introduced in new guide definitions.

## Rich learning scene catalogue

The kit template owns the reusable animated scenes used by EasyStud and Course
Banner Builder. Plugins supply only labels, icons and example data:

- `visualcards`: compact object overview.
- `visualcarddetail`: one object card with related metadata.
- `visualassignment`: participant/source to container to final-context flow.
- `visualfiltersdemo`: search, actions and active filter badges.
- `visualpaste`: pasted identifiers transformed into recognised chips.
- `visualcontextmenu`: pointer, right-click feedback and action menu.
- `visualactionflow`: selected objects, command and destination modal.
- `visualdragdrop`: source card, compatible drop target, selection and actions.
- `visualformula`: a creation recipe expanded into generated results.
- `visualsteps` with `layout`: workflow, status or warning-grid summaries.
- `visualkeys`: keyboard controls.
- `guidedpath`: integrated guided-path launch card.

These scenes must remain data-driven and use EasyEdu class names. Do not copy
plugin-prefixed tutorial markup into a second plugin. New reusable scenes belong
in the kit first and must include responsive and reduced-motion behaviour.

## Extraction status

The generic guide now owns the shell, navigation, locked states, highlights,
checklists, return panel, guided-path cards and the rich learning scenes listed
above. Plugins own their Moodle strings, selectors, business events and example
content only.
