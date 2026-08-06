# Layer row UI contract

## Scope

Course Banner Builder layer lists use the EasyEdu `object-row-cells` primitive
on their existing HTML table rows. The integration is visual only: CCB remains
responsible for row markup, ordering, native drag events, keyboard alternatives,
locked-layer rules and persistence.

The synchronized primitive comes from the targeted uncommitted UI Kit diff on
branch `feature/easyedu-visual-parity-2026-07` at HEAD
`1819371b140cfc9cb8a79b70a702bace0baaac69`. Only
`scss/easyedu/components/_tables.scss` is embedded in CCB; the UI Kit worktree
is not copied wholesale.

## CCB mapping

| Existing CCB state | EasyEdu primitive |
| --- | --- |
| `.local-course-banner-builder-layer-row` | `object-row-cells` |
| `[draggable="true"]` | `object-row-cells-draggable` |
| `.local-course-banner-builder-layer-row-dragging` | `object-row-cells-drag-source` |
| `.local-course-banner-builder-layer-row--order-locked` | `object-row-cells-locked` |

CCB does not currently expose a row-level selected or drop-destination state,
so those optional Kit mixins are not attached. They must not be inferred from
checkbox state or transient DOM position.

## Preserved behavior

- No table padding, dimensions, position, transform or `table-layout` changes.
- The native dragged row stays visible; `drag-source-placeholder` is not used.
- Existing controls remain the non-drag ordering alternative.
- CCB border, overlay and other locked subtype colours remain authoritative.
- Existing data attributes, JavaScript selectors and persistence order remain
  unchanged.
- Reduced-motion and forced-colors behavior comes from the Kit primitive.

## Validation contract

Static validation must compile the public SCSS entry, reject whitespace errors
and prove that the adapter emits no forbidden row geometry. Runtime validation
is a separate, lease-protected Moodle 5.1 scenario selecting exactly one test.
It must check row hover, keyboard `focus-within`, visible native drag source,
locked rows, persisted reorder, non-drag reorder, 200% zoom, horizontal
overflow, console/request errors and complete fixture/profile cleanup.

The plugin declares Moodle 4.5 as its compatibility floor. This integration is
compiled and prepared on Moodle 5.1; Moodle 4.5 behavior remains deferred until
it is run on an actual 4.5 runtime.

## Moodle 5.1 validation evidence

The reference supervised run
`ccb-layer-object-row-20260731T132035519Z-20296` passed one selected Playwright
test at 1600x900 and genuine Chromium 200% zoom. It verified the continuous
cell rail, hover, keyboard `focus-within`, a visible native drag source,
dynamic locked-row stripes, drag reorder persistence, keyboard button reorder
persistence, no document or editor-root overflow, and zero console errors or
failed requests.

The disposable category and all of its elements were removed, and both
per-cell Chromium profiles were removed in `finally`. External evidence and
its artifact manifest are retained under
`%LOCALAPPDATA%\EasyEdu\artifacts\ccb\layer-object-row\supervised\ccb-layer-object-row-20260731T132035519Z-20296`.

At 200%, the wide layer table remains inside its existing responsive scroll
shell. This avoids document overflow but leaves the rightmost columns reachable
through local table scrolling; a future responsive-table lot should decide
whether that interaction needs a smaller structural presentation.
