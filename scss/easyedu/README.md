# EasyEdu UI kit

EasyEdu is the shared visual language used by EasyStud and intended for other
independent Moodle plugins such as Course Banner Builder.

The kit is designed to be **embedded in each plugin**, not installed as a Moodle
dependency. Users can install every plugin independently, while plugin authors
can still keep the visual system consistent by synchronising this folder from a
single source.

## Design goals

- **Standalone plugin distribution**: every plugin ships its own copy of the kit.
- **Theme override friendly**: Moodle themes override public `--easyedu-*`
  variables instead of editing plugin files.
- **Human readable SCSS**: files are split by UI responsibility and documented.
- **No runtime coupling**: Sass mixins emit CSS only when a plugin includes them.
- **Safe local customisation**: plugin-specific styles live outside this folder.

## Folder map

```text
scss/easyedu/
  _index.scss                  Public Sass entry point.
  _tokens.scss                 Public CSS variables and compatibility aliases.
  _components.scss             Backward-compatible mixin aggregator.
  _theme-overrides.example.scss Example Moodle theme overrides.
  components/
    _panels.scss               Main panel and filter containers.
    _cards.scss                Object cards and identity rails.
    _buttons.scss              Icon and action button primitives.
    _forms.scss                Dropdown/menu surfaces.
    _modals.scss               Modal shell surfaces.
    _feedback.scss             Tokens, count badges, empty states.
    _overlays.scss             Drag/drop overlays and hover help surfaces.
```

## Minimal usage in a plugin

Copy `scss/easyedu/` into the target plugin, then import it from the plugin Sass
entry point:

```scss
@use "easyedu" as easyedu;

.local-coursebannerbuilder {
  @include easyedu.token-defaults;

  color: var(--easyedu-text);
}

.local-coursebannerbuilder-layer-card {
  @include easyedu.object-card(var(--easyedu-primary));
}
```

## Theme overrides

Moodle themes should target the plugin root and override only the public CSS
variables they need:

```scss
.local-groupimport-easystud,
.local-coursebannerbuilder {
  --easyedu-primary: #005f73;
  --easyedu-accent: #2a9d8f;
  --easyedu-radius: 0.9rem;
  --easyedu-card-radius: 1rem;
}
```

Prefer generic `--easyedu-*` variables. Plugin-specific aliases such as
`--local-groupimport-easystud-*` are compatibility bridges for existing code.

## Public token families

- **Global**: `--easyedu-text`, `--easyedu-text-muted`, `--easyedu-surface`,
  `--easyedu-border`, `--easyedu-shadow`.
- **Brand/action**: `--easyedu-primary`, `--easyedu-primary-soft`,
  `--easyedu-primary-strong`, `--easyedu-accent`, `--easyedu-danger`.
- **Components**: `--easyedu-card-*`, `--easyedu-control-*`,
  `--easyedu-count-*`, `--easyedu-dropdown-*`, `--easyedu-modal-*`,
  `--easyedu-tooltip-*`.
- **Semantic objects**: `--easyedu-participant-*`, `--easyedu-group-*`,
  `--easyedu-grouping-*`.
- **Identity rails**: `--easyedu-identity-border-width`,
  `--easyedu-identity-size`, `--easyedu-icon-*`, `--easyedu-drag-handle`.

## Available mixins

- `panel-shell`: large plugin panels.
- `filter-shell`: filter/search containers.
- `object-card`: draggable/selectable object cards.
- `identity-rail`: coloured left rail with a masked icon.
- `icon-button`: compact square icon buttons.
- `action-button`: toolbar/action buttons.
- `dropdown-menu`: custom dropdown or overflow menus.
- `modal-surface`: modal dialog shell.
- `token-pill`: semantic labels.
- `count-badge`: small quantity badges.
- `empty-state`: empty or no-result placeholders.
- `drop-target-overlay`: drag/drop compatible target feedback.
- `tooltip-surface`: hover-only help bubbles.

## Maintenance rule

Treat `scss/easyedu/` as a vendored kit:

1. Update the source kit first.
2. Synchronise the folder into each plugin.
3. Keep plugin-specific overrides in that plugin's own `scss/components/`,
   `scss/views/` or `scss/abstracts/` folders.
4. Recompile each plugin after synchronisation.

This keeps the kit easy to copy while still allowing each plugin to evolve.

## Porting checklist for a new plugin

1. Copy or synchronise `scss/easyedu/` into the plugin.
2. Import the kit with `@use "easyedu" as easyedu;`.
3. Include `@include easyedu.token-defaults;` on the plugin root selector.
4. Define only plugin-specific semantic aliases outside `scss/easyedu/`.
5. Use mixins for the first visible components: panels, cards, buttons, modals,
   dropdowns and empty states.
6. Recompile the plugin SCSS and verify the plugin still works without any
   other EasyEdu plugin installed.
7. Add a short theme override example for that plugin root if it introduces
   domain-specific colours.

## What should not live in the kit

- Plugin strings, Moodle capabilities or business rules.
- Selectors named after a specific plugin, except temporary compatibility
  aliases documented in `_tokens.scss`.
- One-off spacing fixes for a single screen.
- Compiled CSS.
