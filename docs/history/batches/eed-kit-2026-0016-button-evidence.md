# EED-KIT-2026-0016 — Button evidence and decision registry

## Status

`In progress — three English-only Penpot catalogues created; owner decision
review is pending.`

This is an evidence and decision batch. It changes no CCB, EasyStud or UI Kit
product behaviour. A later, separately approved consumer batch will implement
only the variants accepted from this catalogue.

## Goal

Create an English-only Penpot catalogue of every distinct button construction
used by Course Banner Builder (CCB) and EasyStud. The catalogue lets the
product owner classify each construction as one of:

- `standard` — use the Kit family unchanged;
- `approved variant` — retain it as a named branch of that family;
- `normalise` — migrate it to an accepted Kit branch;
- `exception` — preserve it with a recorded product reason.

"Every" means every source occurrence is indexed. Penpot shows one specimen
per distinct visual/behavioural signature, rather than repeating identical
buttons that differ only by translated label or data id.

## Scope

### Included

| Family | CCB evidence | EasyStud evidence |
| --- | --- | --- |
| Primary commit | preview Save, layer Save, parent source Save, banner/slideshow Save | import Run, Add emails, move confirmation |
| Secondary/outline | Cancel, Preview, Select all, Collapse all, source dropdown | inline Save/Cancel, filters, Clear selection, Add groups |
| Destructive | Delete selected/all layers, category/image deletion | destructive confirmation and member removal paths |
| Compact/icon actions | table help, inline edit/save/cancel, editor and slideshow toolbar | Rename, mail, remove, unlink, tags, icon action buttons |
| Disclosure/selection | source-chain, layer and editor option controls | members/groups/groupings and advanced-filter controls |
| Shared shell actions | modal close, Navigation and Guide controls | modal close, Navigation and Guide controls |

### Explicitly deferred

- font-family choice and typography migration;
- checkbox, radio, toggle, dropdown, file-picker, toast and modal-family
  audits;
- changing source markup, Sass, AMD, generated assets, runtime, cache,
  fixture or preview branch;
- duplicate rendering of shared Navigation/Guide controls where the source
  signature is already identical in both consumers.

## Active related lots

| Lot | Relationship | Status in this batch |
| --- | --- | --- |
| `EED-KIT-2026-0016` | Shared decision registry and future Kit contract | Active |
| `EED-CCB-2026-0063/0064/0075-RF1` | Compact preview actions, modal loading and layer Delete | Evidence source only; no reopening of accepted behaviour |
| `EED-CCB-2026-0049` | Large authoring workspace actions | Evidence source only; no workspace change |
| EasyStud Wave 16 | Current EasyStud consumer baseline | Evidence source only; no runtime change |

## Evidence method

1. Index source buttons from Mustache/PHP constructors, including Moodle
   `add_action_buttons()` paths that require later browser proof.
2. Cluster the index by role, classes, size, icon slot, state treatment and
   action behaviour.
3. Capture computed browser values only for representatives where the source
   construction cannot establish the visual contract.
4. Materialise each distinct signature in Penpot with an English role label,
   product tag, source path, selector/constructor and decision field. Three
   catalogues were created on `99 — Audit mapping` on 2026-09-04:
   `Primary & destructive`, `Secondary & outline`, and
   `Icon, disclosure & navigation`.
5. Export the completed Penpot board for a visual sanity check. Browser-rendered
   controls remain the authority if an editable Penpot reproduction diverges.

All Penpot labels, family names, state names and notes must be English. Product
strings are translated only when they are needed to identify the exact source
control; the visible specimen uses an English equivalent.

## Preview comparison references

Every current Penpot specimen now carries a short `Preview:` reference that
points to the visible product zone to compare, rather than leaving the owner to
decode a PHP, Mustache or AMD file name. Examples include:

- `Source visual editor -> footer actions` for CCB preview Save and layer
  Delete controls;
- `Group / Grouping card -> inline name edit` for EasyStud rename Save and
  Cancel;
- `Mass Import -> parsed rows table` for EasyStud Select all rows.

The three catalogues contain 44 such references. Two legacy CCB entries
(`Select all` and `Collapse all`) are marked `Reference pending` because source
evidence does not yet establish a live, literal control in the current preview.
They are excluded from visual approval until a focused browser/source proof
either locates them or retires the duplicate specimen.

The owner should compare the product badge, specimen name and `Preview:` zone
against the active product preview. Penpot remains a decision surface; the
browser-rendered control remains the visual authority.

## Quota discipline

- Luna handles deterministic inventory and source-zone mapping.
- Terra handles normalised comparison and duplicate clustering.
- Sol is reserved for ambiguous behaviour, cross-module decisions and final
  review gates.
- A screenshot is collected only for a real-rendering, interaction or
  breakpoint fact that source evidence cannot establish; one state per
  meaningful breakpoint is sufficient.
- Penpot reads and exports are scoped to named specimens only. The canonical
  registry stores links and normalised facts instead of duplicate captures.

## Component matrix

`08 — Components` now contains `EasyEdu — Button system / structure v1`.
It is deliberately separate from the source-evidence catalogues on
`99 — Audit mapping`:

- four semantic families: `Primary action`, `Secondary action`, `Danger
  action`, and `Icon & disclosure`;
- three sizes: `S / 32 px`, `M / 40 px`, and `L / 48 px`;
- four content compositions: icon only, label only, leading icon and trailing
  icon;
- four visible states: default, hover, focus and disabled.

The board is a component structure for owner comparison, not approval of a
final visual theme. Its icon slots are intentionally neutral composition
markers; actual product icons remain sourced from the audited control. The
next decision gate maps each catalogued specimen to an approved matrix cell,
or records it as a justified branch or exception.

## Compact and table-action correction

The first catalogue incorrectly represented several `btn-link p-0` and
icon-only controls as elongated generic action buttons. That representation has
been corrected in Penpot; it did not affect product code.

- modal closes are square icon controls, not text buttons;
- CCB table help, inline Save, editor toolbar and EasyStud Rename, mail and
  group-menu controls retain icon-only evidence;
- CCB and EasyStud responsive navigation triggers are separate
  product-specific specimens, not a presumed shared component;
- a new English-only board, `EasyEdu — compact action evidence v1`, records
  source and layer table actions, EasyStud action bars, card actions,
  selection, pagination and modal/navigation controls at their compact source
  footprint.

The Component page also has a compact section for contextual table actions,
selection/pagination, card quick actions and product-specific navigation.
It distinguishes the lighter contextual primary/danger actions from the
stronger Primary and Danger families. CCB navigation styling remains explicitly
pending a focused browser comparison; the supplied EasyStud responsive render
is the evidence for its pill-shaped specimen.

### Recorded product follow-up

The responsive navigation trigger remains a consumer-parity follow-up, outside
this Penpot-only evidence batch: compare the CCB and EasyStud browser renders,
measure the CCB right-side radius and text baseline, then correct the reported
CCB text clipping before any shared-component migration. The matrix must not
be used to silently make CCB look like EasyStud.

## Initial source findings

- CCB has a consumer-local `save-action-button` mixin in its embedded Kit copy
  while the canonical UI Kit currently exposes only the generic
  `action-button` primitive. This is a source-of-truth divergence, not an
  instruction to copy CCB code into the Kit unchanged.
- EasyStud's five literal `Save` controls are compact, outline-secondary
  rename confirmations. They belong to the `save / secondary / inline` branch,
  not to the primary persistence branch.
- CCB's `Save preview changes` is primary and asynchronous; it belongs to
  `save / primary / preview`. Its Font Awesome save glyph uses the exact
  Moodle-rendered Regular variant in Penpot. The three button catalogues also
  use Font Awesome Free 6.7.2 SVG vectors for the other represented icon
  controls; literal `?` and `+0` remain text because those are the actual
  source control contents.
- Button classes alone are insufficient for a decision: native Moodle form
  buttons, PHP-generated editor controls and dynamic controls must be checked
  against a browser representative before they are called conforming.

## Decision output

The completed Penpot catalogue will use these fields for every specimen:

`Family · Intent · Size · Content · State · Product · Source · Decision`

The resulting registry becomes the implementation input for a future Kit-first
refactor: define the primitive in `easyedu-ui-kit`, then migrate one consumer
at a time without changing Moodle action hooks or semantics.

## Validation and safety

- Source inventory: read-only.
- Penpot write: design-only and reversible through Penpot history.
- Penpot structural check: all three catalogues have no descendant containment
  violation; their text has no accented French content. The server timed out
  while exporting PNG evidence, so visual export remains a non-blocking
  follow-up rather than a claimed browser comparison.
- No authenticated Moodle test is authorised by this record.
- Visual exports and browser captures stay outside Git and are registered under
  the approved EasyEdu artifact policy.
- No product preview can be claimed until a later consumer migration is
  committed, promoted and reviewed independently.

## Deletion-icon evidence correction

The Penpot catalogue must not use an `x` for a destructive action. In the
current source, `x` is reserved for Close or Cancel only.

- CCB Delete content, Delete selected layers and Delete all layers use
  `fa-trash-can`.
- EasyStud Delete group(s) and Delete grouping(s) use `fa-trash`.
- EasyStud Remove member(s) uses `fa-user-minus`; Remove from groupings uses
  `fa-unlink`.
- CCB Delete images is an intentional current-source exception: it uses
  `fa-layer-group`, not a trash glyph. It stays visible as an exception for an
  owner decision rather than being falsely normalised.

The compact evidence board now renders actual trash glyphs for the three
trash-source specimens above. Its CCB `Delete images` specimen deliberately
uses `fa-layer-group` in every state; trash remains reserved there for the
separate content/layer deletion specimens. Other icon evidence remains
source-led and will be replaced only after a matching browser capture or
explicit owner decision.

## Screenshot-led calibration gate

The original component matrix is a structural inventory, not a scale or
spacing approval. Before code migration, full viewport screenshots will be
catalogued by route, breakpoint and state; each visible control will be linked
to its source selector and Penpot specimen. The owner then chooses one
reference specimen per family. Only those approved decisions may change Kit or
consumer CSS.

## REM measurement baseline

All new evidence records use `rem`, never a Penpot-only pixel approximation.
The embedded shared button source declares the following baseline:

| Role | Height / minimum height | Type | Padding |
| --- | --- | --- | --- |
| Compact action | `1.9rem` | `0.78rem` | `0.22rem 0.5rem` |
| Regular action | `2.35rem` | `0.88rem` | `0.36rem 0.75rem` |
| Large action | `2.85rem` | `0.98rem` | `0.48rem 0.95rem` |

The generic radius token is `0.72rem`, but it is not a permission to overwrite
a source-specific button. CCB Save preview, table actions, modal close
controls, editor-toolbar icons and navigation triggers each retain their own
computed evidence until an owner approves a common branch.

## Screenshot evidence register

The owner supplied complete-page CCB captures. The numbered references below
are identifiers only; image files remain outside Git.

| Ref | Surface | Button/control families visible |
| --- | --- | --- |
| CCB-A1 | Course banner sources guide | Guide tabs, step arrows, Close, Previous, Next, Show in interface |
| CCB-A2 | Add one or more layers modal | Enable toggle, Image/Border/Overlay tabs, side actions, icon toolbar, Save layer(s), Close |
| CCB-A3 | Edit course title modal | segmented title style, toggles, editor toolbar, accordion controls, footer Save changes, Close |
| CCB-A4 | Course format modal | selectable format cards, footer Save banner format, Close |
| CCB-A5 | Transfer | admin navigation rail, upload, checkboxes, Export configuration, Import configuration |
| CCB-A6 | Slideshow settings | toggles, checkboxes, compact help, primary/secondary section actions, footer Save slideshow settings |
| CCB-A7 | Site banner editor | source Delete/Delete layers only, visual-editor actions, table Edit/Delete, preview Save/Delete, bulk Delete/Save |
| CCB-A8 | Course banner editor | navigation rail, source select/deselect, source Delete/Delete layers only, visual-editor actions, table and configured-source actions |

The visual evidence confirms that table Edit/Delete entries are compact
contextual actions; editor toolbar actions are icon-only; and footer Save
controls form a separate primary family. These are not interchangeable
footprints.

## CCB local action metrics found in source

CCB has a documented local layer over the generic Kit values. The following
must be reproduced as a CCB branch in Penpot, not silently rewritten as the
generic matrix:

| CCB branch | Source rule | Measurement |
| --- | --- | --- |
| Preview side action | `components/_action-contract.scss:13-20` | `2.45rem` action height, `0.5rem` icon/label gap, `1rem` icon slot |
| Preview side rail control | `components/_admin-controls.scss:140-148` | fixed `2.45rem` height/min/max in the vertical action rail |
| Table action cell | `components/_action-contract.scss:277-309,379-413` | `2.25rem` minimum height; `1rem` leading and trailing grid slots with `0.5rem` gaps; the leading glyph and visible label are both vertically centred, and the label is centred across the complete action through the mirrored final slot |
| Source-chain preview action | `components/_admin-controls.scss:714-724` | `9rem` minimum and `14rem` maximum width; centred label branch |
| Button motion | `components/_admin-controls.scss:171-196` | hover `translateY(-1px)` plus icon scale `1.06`; active `scale(0.94)` |
| Responsive navigation, expanded hover | `templates/easyedu_navigation.mustache:48-59`, `classes/output/navigation.php:107-110`, `components/_navigation.scss:384-459` | exact `fa-bars`, English label `Open Course Banner Builder menu`, `2.75rem` / 44px touch target and expansion up to `22rem`; `0 999px 999px 0` radius preserves the flat viewport edge |

These metrics explain why a generic `2.35rem` Kit button is visually close but
not exact for CCB side and source-chain actions.
