# Navigation, drawer and Guide surfaces

Batch: `EED-KIT-2026-0006`

This contract gives EasyStud, Course Banner Builder and future EasyEdu plugins
one visual foundation for primary Navigation, the compact drawer and the Guide
launcher. It does not define product destinations, permissions, Guide content,
target selectors or completion rules.

## Shared composition

Consumers include `navigation-component()` beneath their plugin root and render
the vendorable `easyedu_navigation.mustache` structure. The same normalized
item context feeds desktop and compact presentations.

The compact panel has two fixed regions:

- `.easyedu-navigation__panel-header` reaches the safe-area top and owns the
  shared close control;
- `.easyedu-navigation__panel-scroll` independently scrolls participant,
  Guide and destination content without moving or clipping the header.

The panel itself remains viewport-sized and does not scroll. This prevents a
sticky header from being clipped at the top and prevents scroll chaining into
the Moodle page. The open-state transform remains `none`, so a portalled Guide
dialog is still positioned against the viewport.

The compact trigger's text uses `.easyedu-navigation__trigger-label`. A long
translated label may truncate inside the available trigger width; it must never
expand the fixed trigger beyond the viewport or clip the icon.

## Typography and surfaces

All Navigation controls inherit `--easyedu-font-family-ui`. Destination labels
use `--easyedu-font-weight-medium`; panel and section headings use the shared
typography roles. A consumer must not reintroduce a product-specific family or
heavy menu weight.

The Guide launcher has a visible resting boundary and a neutral surface before
hover or focus. These states use:

- `--easyedu-guide-launcher-resting-surface`;
- `--easyedu-guide-launcher-resting-border`.

The compact projected Guide row continues to use the established
`--easyedu-navigation-guide-*` tokens. The drawer uses:

- `--easyedu-navigation-drawer-surface`;
- `--easyedu-navigation-drawer-border`;
- `--easyedu-navigation-drawer-shadow`.

## Strict EasyStud and CCB parity

EasyStud and CCB consume the same template, SCSS mixins, tokens, focus ring,
close button and Guide launcher states. No `local_groupimport` or
`local_course_banner_builder` selector belongs in this Kit contract.

Consumers keep only their own:

- server-resolved routes, capabilities and active destination;
- product Guide copy, target keys, real-action completion and persistence;
- Moodle anchor selector and translated labels;
- breakpoint choice when their approved layout requires one.

A consumer may override public tokens under its root. It must not fork the
drawer header/body structure, remove the resting Guide boundary, replace the
close/focus contract or create a second list of compact destinations.

## Required validation states

Consumer proof remains separate from this source-only Kit batch. Each adoption
must compare EasyStud and CCB at desktop and compact sizes, including long
labels, drawer open/closed, independently scrolled content, close/Escape/focus
return, Guide launcher resting/hover/focus, RTL, reduced motion and forced
colours. Product Guide content and real-target routing are validated in the
consumer and `EED-NAV-2026-0011` lots.
