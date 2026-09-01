# Banner geometry contract

## Status

`local_course_banner_builder\banner_geometry` is a pure foundation service.
The public course-header HTML image-overlay path now consumes it through
`course_header_overlay_geometry_adapter`. The adapter normalises existing
stored source-element values in memory, selects `public_banner`, and returns
the existing inline-style representation. The manager passes the existing
layer z-index through the adapter, so stacking order remains unchanged. It
performs no database or settings writes.

Batch 2C aligns the administration source-chain preview frame with the
selected banner format by applying its existing `--format-*` modifier outside
the native-administration wrapper as well. Its layer, crop, title, border and
slideshow renderers remain on their existing paths. Generated public
backgrounds, site banners, GD composites, cards, thumbnails, slideshows,
imports and exports also remain unchanged. This batch therefore does not claim
complete visual preview/public parity.

## Canonical source space

The authoring space is fixed at **1600 x 400 canonical pixels** (4:1). Source
layer and title coordinates use a top-left origin in that space. Values may be
outside the canvas because existing positioned-layer behaviour allows offsets
beyond a visible edge; projections preserve them rather than clamp them.

The contract takes only explicit arrays. It does not read Moodle configuration,
request data, files, the database, or browser measurements. It returns final
projected values rounded to six decimal places; intermediate calculations retain
floating-point precision.

## Formats

| Identifier | Display ratio |
| --- | ---: |
| `standard` | 4:1 |
| `contentwide` | 5:1 |
| `fullwidthtop` | 5:1 |
| `fullwidthtopcompact` | 8:1 |
| `fullwidthtopinset` | 6.1:1 |

The selected format supplies the intended display ratio. An explicit target
container supplies the actual projection width and height; no DOM measurement
belongs in the PHP contract.

## Source and calculated fields

Canonical source fields are layer `x`, `y`, `width`, `height`, `scale`,
transform origin, crop rectangle, fit policy, title settings, border percent,
and layer order. Calculated fields include target scales, projected layer box,
normalized coordinates, effective media crop/fit box, title safe width and
frame bounds, and projected border thickness.

Rotation is reserved in the returned contract but currently only accepts zero:
CCB has no supported persisted rotation control. Crop uses the existing stored
percentage convention: `x`/`y` are clamped inside a 1-100% width/height crop.
Supported image fit names are `contain`, `cover`, `fill`, and `original`.

Title geometry intentionally reports available frame/content width, a minimum
frame height derived from line count, font-size, line-height and padding rather
than estimating browser font glyph widths.

## Rendering policies

- `preview`: canonical projection for administration previews and editing
  modals.
- `public_banner`: the same normalized geometry as `preview`; later migration
  must preserve this equivalence for a given target container.
- `thumbnail`: an explicitly non-identical adaptation for course cards and
  overview images. When a caller marks a layer as thumbnail-adaptable, its
  scale is multiplied by the named existing rule `1.16`. Border percentages use
  the named existing rule `min(11, authored_percent * 0.55)`.

Thumbnail adaptation is opt-in through explicit input. It must never be used to
claim pixel-identical banner fidelity.

## Ownership and migration order

`banner_geometry` owns only deterministic projection mathematics. The course
header adapter owns legacy-field translation for public HTML image overlays:
fit mode, anchor/offset fields, crop percentages, custom dimensions and
fixed-centre placement. Existing renderer helpers retain ownership of storage,
Moodle APIs, GD and browser behaviour until migrated deliberately.

Planned order:

1. measure the migrated public HTML image overlays against deterministic admin
   preview fixtures across formats and viewports;
2. correct generated public backgrounds to use strict banner geometry;
3. migrate cards using the explicit thumbnail policy;
4. retire duplicate helpers only after visual and regression evidence.

## Administration preview frame ownership

`[data-source-preview-frame="1"]` is a
`.local-course-banner-builder-border-preview-frame` emitted with the selected
`--format-*` modifier by the existing source-preview helper. The shared base
rule is 4:1 for `standard`; the existing modifiers set `contentwide` and
`fullwidthtop` to 5:1, `fullwidthtopcompact` to 8:1, and
`fullwidthtopinset` to 6.1:1. Batch 2C makes those modifier selectors global
to the preview frame instead of restricting them to
`.local-course-banner-builder-admin--native`, because source-chain modal
previews are outside that wrapper.

No JavaScript size calculation, inline height, wrapper constraint, or public
adapter participates in the correction. The selected format is rendered when
the management page refreshes after the existing format form is saved; this
batch neither changes that form flow nor introduces a second frontend ratio
map. Modal previews that share the source-preview frame receive the same
modifier; independent square layer thumbnails retain their explicit 1:1 rule.

### Transient selection frame for clipped editor images

The editable source preview, Add image modal and Edit image modal use one
transient `local-course-banner-builder-preview-selection-outline` element
managed by `localCourseBannerBuilderSyncPreviewSelectionOutline`. It normally
tracks the rendered image Crop selection. If that selection extends beyond any
edge of the preview frame, only that indicator edge clamps magnetically to the
matching frame edge. The remaining edges continue to track the selected image;
a uniform inset keeps the complete stroke inside the clipped preview. This is
selection chrome only: it does not clamp or rewrite image geometry, Crop,
pointer hit-testing, Keep proportions, persistence or public rendering.
The indicator is hidden when there is no visible selected layer and remains
above preview guides through its existing z-index contract.

The English `bannerformat:fullwidthtop_help` text still describes 4:1. It is a
known localisation/documentation discrepancy and remains deliberately out of
scope for Batch 2C.

## Batch 2E-A.1 public native-banner sizing authority

The public native-banner container has two deliberately matching CSS sources:

1. `scss/components/_native-banner-core.scss`, compiled into the plugin's
   `styles.css` fallback contract;
2. `hook_callbacks::get_course_banner_runtime_css()`, emitted as an inline
   stylesheet after Moodle's compiled theme/plugin stylesheet and therefore
   authoritative for a mounted public course or site banner.

Both sources use one grouped non-standard format selector and the same policy:

```text
displayHeight = clamp(128px, containerWidth / selectedFormatRatio, existingFormatMaxHeight)
```

| Format | Ratio | Minimum | Maximum |
| --- | ---: | ---: | ---: |
| `contentwide` | 5:1 | 128px | 280px |
| `fullwidthtop` | 5:1 | 128px | 360px |
| `fullwidthtopcompact` | 8:1 | 128px | 210px |
| `fullwidthtopinset` | 6.1:1 | 128px | 300px |
| `standard` | 4:1 | unchanged base rule | unchanged base rule |

`standard` is deliberately absent from the grouped selector. The runtime
stylesheet is injected for both native course and site banners; eligible
course-related activity pages reuse the course public-banner context. This
does not migrate a separate activity renderer, cards, thumbnails, GD output,
administration previews, editing modals or slideshow controls.

The approved public 128px floor is a responsive container adaptation selected from the
previously measured policy matrix, not a change to
the 1600x400 canonical authoring space or to normalized overlay geometry.
Mobile administration/modal preview parity remains the next functional batch.
The visual title remains beneath an `aria-hidden` public ancestor and title
replacement may hide Moodle's visible `h1`; accessibility remediation is a
separate follow-up and is not claimed by this sizing change.

## Batch 2E-B administration mobile simulation

The selected source visual editor now has a local, segmented preview control:
**Desktop preview** (the default) and **Mobile preview**. The
control belongs only to `[data-source-visual-editor="1"]`; it does not write a
Moodle setting, alter source/layer data, touch crop state, or change public
banner output. JavaScript retains the selected mode only in an in-memory map
for the current source/root lifecycle. A normal page reload starts in desktop
mode.

The captions are Moodle language strings rather than mode identifiers. English
uses **Desktop preview** and **Mobile preview**; French uses **Aperçu
ordinateur** and **Aperçu mobile**. Translating these captions does not change
the established `desktop` / `mobile` data values or introduce persisted state.

### EED-CCB-2026-0049 contained large authoring preview

The selected-source authoring canvas exposes an explicit large-preview action
beside its Desktop/Mobile control. The action clones the current rendered
source panel into the existing opaque preview modal, then removes forms,
save/delete controls, the filmstrip, editor rails and the nested launcher. The
clone is marked readonly before it is initialised, so layer selection, drag,
resize, Crop and payload writes remain unavailable.

The clone preserves the source panel's current Desktop/Mobile mode at open.
Further mode changes are modal-local because modal previews are excluded from
the in-memory authoring-mode map. Closing with the header control, Escape or
the backdrop uses the existing modal lifecycle and restores focus to the
launcher. No new database field, form value, local storage key, public CSS or
banner renderer is introduced.

Mobile simulation uses a logical width of 390px. Its logical height is
derived server-side from `banner_geometry::get_format_aspect_ratios()` and the
already-published public sizing policy:

```text
standard:                 390 / 4       = 97.5px
contentwide:              clamp(128, 390 / 5,   280) = 128px
fullwidthtop:             clamp(128, 390 / 5,   360) = 128px
fullwidthtopcompact:      clamp(128, 390 / 8,   210) = 128px
fullwidthtopinset:        clamp(128, 390 / 6.1, 300) = 128px
```

The displayed frame is scaled down proportionally when the administration
column is narrower than 390px. This is preview-only CSS in
`_admin-layout.scss`; Batch 2E-B does not alter
`hook_callbacks::get_course_banner_runtime_css()`, `_native-banner-core.scss`,
public adapters, the geometry contract, or storage.

### Controlled inline geometry exception

The source-preview frame receives three computed CSS custom properties from
`admin_manage.php`: the logical width, its CSS width, and the calculated
logical height. This is a deliberately narrow exception to the EasyEdu rule
against inline CSS for reusable UI. The values are derived from
`banner_geometry::get_format_aspect_ratios()` and the published 128px
floor/format-cap policy; they are not arbitrary presentation declarations.

The exception is valid only when all of these conditions hold:

- the inline declarations are limited to
  `--local-course-banner-builder-source-preview-*` geometry properties;
- matching `data-source-preview-*` attributes remain available for inspection
  and scripted state contracts;
- `_admin-layout.scss` supplies a safe fallback for missing custom properties;
- no public banner, storage value, form payload or Moodle setting is changed;
- generated `styles.css` is rebuilt from the SCSS source when that source
  changes.

CI and review tooling should flag other new inline style declarations while
allowing this explicitly named geometry namespace.

### Modal and thumbnail classification

| Surface | Batch 2E-B status | Reason |
| --- | --- | --- |
| Selected source visual editor | updated | It is the canonical authoring surface and owns the selected source frame. |
| Read-only source-chain modal | updated | It reuses the selected-source frame and exposes the same transient simulation selector; source/layer editing controls remain stripped. |
| Title, image, overlay, border, slideshow modals | deferred | Each owns a distinct preview frame and interaction lifecycle; sharing this transient source-only control would be misleading. |
| Independent square layer thumbnails / course overview adaptations | not applicable | They have explicit thumbnail policies and must not claim banner-fidelity. |

Normalised placement compares child rectangles against the frame content box,
not its border box. Persistent layer state (crop, fit/anchor, custom size and
offsets, opacity, z-index) must remain byte-for-byte unchanged while changing
preview mode. An `original`-fit image may intentionally extend outside the
frame and be clipped, so its raw visual rectangle is recorded but is not a
cross-aspect ratio failure criterion.

At 390px the configured-source table has a pre-existing 19px document
overflow from its separately scrollable table shell. The Batch 2E-B test
records desktop and mobile values for the same viewport and rejects any
increase caused by the preview simulation; it does not hide or modify that
unrelated table behaviour.

## Batch 2A browser measurement protocol

Use a disposable course fixture with a landmarked background, one transparent
PNG with an asymmetric crop, a partial border, an overlay, a framed multiline
title and one inherited parent layer. For each format, record the bounding box
of `.local-course-banner-builder-fixed-overlay` and its child image in the
administration preview and the public course banner. Compare normalised left,
top, width and height; allow one CSS pixel of rounding only.

| Format | Viewport | Zoom | Preview box | Public box | Result |
| --- | --- | ---: | --- | --- | --- |
| `standard` | 1600x900 | 100% | pending fixture | pending fixture | pending |
| `contentwide` | 1024x768 | 100% | pending fixture | pending fixture | pending |
| `fullwidthtop` | 768x1024 | 100% | pending fixture | pending fixture | pending |
| `fullwidthtopcompact` | 390x844 | 100% | pending fixture | pending fixture | pending |
| `fullwidthtopinset` | 1600x900 | 200% | pending fixture | pending fixture | pending |
| `fullwidthtopinset` | 390x844 | 200% | pending fixture | pending fixture | pending |

Do not promote a full-page screenshot to a CI gate. Preserve the DOM
measurements, fixture revision, theme, Moodle version and browser version with
the release evidence.
