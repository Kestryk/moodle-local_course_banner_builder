# Course Banner Builder Navigation adapter

This directory records the Navigation/Guide contract imported into Course
Banner Builder for `EED-NAV-2026-0006`. Its immutable reference source is UI
Kit snapshot `f032a18aefc8f0816a2f36c52d6e6867cd9664b8`; it is not a runtime
dependency or a second Guide implementation.

The CCB adaptation provides:

- one normalized server-prepared context;
- separate desktop and compact Mustache wrappers;
- one shared item partial;
- a compact-panel controller with focus, Escape and backdrop handling; its
  left-edge trigger is viewport-centred rather than calculated from another
  page control;
- an optional Navigation/Guide bridge that projects one launcher per
  presentation and portals the complete Guide root outside transformed panels;
- examples for nested and empty navigation.

Consumers copy the package into their own Moodle component namespace. They must
not require a separately installed UI Kit plugin.

## Context ownership

PHP owns permissions, visibility, URLs, labels, icons and the complete
navigation hierarchy. Hidden or unauthorized items are omitted before
rendering. Desktop and compact variants receive the same `sections` array.

The responsive renderer must not parse, clone or infer items from rendered
desktop HTML.

See [the local CCB Navigation contract](../docs/navigation.md) for the CCB
routes, init order, Guide portal boundary and validation requirements.

## Local implementation

The CCB copies use the `local_course_banner_builder` Mustache and AMD
namespaces. Keep the public `data-easyedu-navigation-*` attributes stable;
they are the contract between server context, compact-panel controller and
Guide bridge.

When the optional `guidehtml` slot is used, initialize the contracts in this
order:

```js
NavigationGuide.init('[data-easyedu-navigation]');
Guide.init('[data-easyedu-guide-root]', guideConfig);
```

Before replacing the rendered region, run the consumer Guide teardown when its
lifecycle exposes one, then call `bridge.destroy()`. The bridge moves the
complete Guide root under `document.body`, preserves resolved `--easyedu-*`
tokens, keeps desktop label reveal, closes the compact panel before opening
Guide, and restores the original DOM on teardown.

The CCB consumer alignment keeps the desktop Guide label as an out-of-flow
capsule, so the label never reflows the centred rail. The compact projection is
one full-width gradient row with its icon and label in the same target. These
are consumer adaptations pending their next immutable UI Kit snapshot; do not
copy product routes, format actions or Guide content into another consumer.

## Required visual and help contract

Every future Navigation consumer must preserve these shared details:

- destination icons are plain glyphs in a fixed one-rem alignment slot on both
  desktop and compact layouts; they must not gain an icon tile, border, rounded
  box or independent active background;
- the compact left-edge half-pill uses the public
  `--easyedu-touch-target-min: 2.75rem` token for its resting block size,
  inline size and minimum dimensions, remains fixed at the viewport centre and
  expands only its text label on hover or keyboard focus;
- a Guide launcher that already exposes the animated desktop label or the
  permanent compact label must not also opt into a browser, Moodle or
  Navigation popover. Keep its accessible name in `aria-label`, but omit
  `title`, `data-easyedu-navigation-popover` and equivalent tooltip attributes.

These constraints are part of the vendorable Navigation contract, not CCB
decoration. The canonical UI Kit owner must reproduce them in the next reviewed
snapshot before another plugin imports Navigation.

The shared source contract does not reconnect EasyStud and CCB: their markup,
module namespaces, normalized contexts, routes and product actions remain
local and must be changed in their own documented batches.
