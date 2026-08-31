# Large authoring workspace discovery

Batch: `EED-CCB-2026-0049`

Status: discovery and human-decision gate. This document does not authorize a
product implementation, persistence change, runtime promotion or preview.

## Product boundary

The Wave 6 readonly large-preview clone proved that a large modal can remain
contained, but it is not the requested product. The target is a desktop-first
authoring workspace opened only from the main selected-source preview. It must
reuse the existing CCB document model and interaction contracts instead of
creating a second editor.

The first release must provide a large banner work area, image-layer
selection, drag, resize, Crop, existing action controls, filmstrip, zoom and
direct access to the existing Border and Overlay options. Public output is
always clipped by the stored banner format. Modal size, pan and zoom are view
state only.

Responsive mobile authoring is excluded. The existing main-preview
Desktop/Mobile simulation remains outside this batch and its wording remains
owned by `EED-CCB-2026-0076`.

## Verified source inventory

The audit was performed from source commit
`4bc9fd441b7cc25dd27ee12cf1bf239ddb2d79fc`.

| Concern | Existing source | Reuse decision or gap |
| --- | --- | --- |
| Canonical banner geometry | `classes/banner_geometry.php`, `docs/architecture/banner-geometry.md` | Keep the 1600 x 400 canonical coordinate space and selected-format ratio. Zoom must never alter it. |
| Complete selected-source renderer | `local_course_banner_builder_render_source_visual_editor()` in `admin_manage.php` | Reuse one live editor root. Do not create a second stateful clone with duplicate form ids. |
| Image selection and hit testing | `localCourseBannerBuilderGetTopSourcePreviewLayerAtPoint()`, `localCourseBannerBuilderSelectSourcePreviewLayer()` | Reuse the current single-layer, alpha-aware selection contract. |
| Drag, resize and pointer capture | `localCourseBannerBuilderStartSourcePreviewInteraction()`, `localCourseBannerBuilderHandleSourcePreviewPointerMove()` | Reuse normalized document coordinates. Convert viewport pointer deltas through the current zoom before applying them. |
| Keyboard positioning | `localCourseBannerBuilderNudgeSourcePreviewLayer()` and the delegated Arrow/Shift+Arrow handler | Preserve one-pixel and ten-pixel nudges in canonical coordinates, independent of zoom. |
| Alignment guides | `localCourseBannerBuilderFindPreviewSnap()`, `localCourseBannerBuilderUpdatePreviewGuides()` | Reuse the existing optional guide toggle and peer/centre logic. Boundary-edge expansion requires the snapping decision below. |
| Crop | The `localCourseBannerBuilder*Crop*` functions and `easyedu-kit-docs/ai/COURSE_BANNER_BUILDER_CROP_HANDOFF.md` | Reuse without changing session geometry, release-click suppression or save/reopen behavior. |
| Layer state and history | `localCourseBannerBuilderGetSourcePreviewLayerState()`, `SetSourcePreviewLayerState()`, `BuildSourcePreviewHistorySnapshot()` | Keep document history separate from volatile zoom and pan history. Existing undo depth is 40 snapshots. |
| Save payload | Hidden `previewlayerpayload` form in `admin_manage.php`; `localCourseBannerBuilderSaveSourcePreviewChanges()`; `manager::update_source_visual_editor_layers()` | Reuse the current sesskey-protected AJAX route after the Save/Cancel decision. Offsets already accept `-1000` to `1000` percent. |
| Public clipping | Manager/native overlay wrappers and `course_header_overlay_geometry_adapter.php` | Existing renderers use overflow clipping and normalized offsets. A visible authoring pasteboard must not change public clipping. |
| Border and Overlay editors | Existing `data-edit-layer-url` controls and dynamic Edit Border/Edit Overlay modals | Reuse the same forms, validation, strings and server rules. Inline embedding needs an extraction boundary; copying form logic is forbidden. |
| Filmstrip and lower actions | Existing source-preview filmstrip, visibility row, primary actions and preview controls | Preserve the same hooks and semantics; only the workspace composition changes. |
| Modal and focus lifecycle | Source-preview modal helpers and EasyEdu `context-modal-surface` / `modal-runtime-animation` contracts | Use a dedicated large-workspace modal composition and restore visible focus to the exact launcher after Close, Escape, Cancel and successful Save. |
| Current Wave 6 prototype | `localCourseBannerBuilderCloneReadonlySourcePreview()` and `localCourseBannerBuilderShowLargeSourcePreview()` | Disposable. Do not add editing to the clone. It intentionally strips forms, actions and the filmstrip. |

### Architectural consequence

The existing live editor is already the document controller. The least
duplicative candidate is to portal or mount that one root into a dedicated
workspace shell and return or replace it after the workspace closes. This
keeps current listeners, data hooks and one form identity. It is a candidate
integration seam, not an approved Save/persistence architecture.

A separately cloned interactive tree is rejected because `cloneNode()` does
not copy listeners, would duplicate ids and would require synchronizing Crop,
history, layer modals and the hidden payload in two directions.

## Interaction research

The proposed interaction model follows established browser and authoring-tool
behavior:

- Pointer capture keeps a drag routed to its active element after the pointer
  leaves its bounds. CCB already uses this mechanism and should retain it:
  [MDN `setPointerCapture()`](https://developer.mozilla.org/en-US/docs/Web/API/Element/setPointerCapture).
- Wheel events expose `ctrlKey` for zoom gestures. The workspace should call
  `preventDefault()` only for a cancelable Ctrl+wheel inside its viewport and
  must leave ordinary wheel/page scrolling untouched:
  [MDN wheel event](https://developer.mozilla.org/en-US/docs/Web/API/Element/wheel_event).
- CSS transforms change the view coordinate space without changing document
  flow. A dedicated viewport transform can therefore scale the visual canvas
  while the CCB model remains in canonical percentages:
  [MDN CSS transforms](https://developer.mozilla.org/en-US/docs/Web/CSS/Guides/Transforms/Using) and
  [transform origin](https://developer.mozilla.org/en-US/docs/Web/CSS/Reference/Properties/transform-origin).
- Pointer-relative zoom keeps the content under the pointer stable and is a
  standard canvas-editor pattern:
  [Konva pointer-relative zoom](https://konvajs.org/docs/sandbox/Zooming_Relative_To_Pointer.html).
- A temporary Hand tool activated by Space and a persistent Fit action are
  established editor conventions:
  [Adobe Hand tool](https://helpx.adobe.com/photoshop/using/tool-techniques/hand-tool.html) and
  [Adobe image viewing](https://helpx.adobe.com/photoshop/using/viewing-images.html).
- A modal must contain its Tab sequence, close with Escape and normally return
  focus to its invoker:
  [WAI-ARIA modal dialog pattern](https://www.w3.org/WAI/ARIA/apg/patterns/dialog-modal/).
- The workspace must remain keyboard operable and use native controls for its
  toolbar and properties:
  [MDN keyboard-navigable widgets](https://developer.mozilla.org/en-US/docs/Web/Accessibility/Guides/Keyboard-navigable_JavaScript_widgets).

No canvas library is selected by this research. The current DOM renderer
already provides accessible controls, server-rendered geometry and validated
Crop behavior. Replacing it with Canvas, SVG or a third-party editor would add
a second rendering and hit-testing engine without a proven need.

## Kit-aligned wireframe

The diagram is a structural wireframe, not final visual styling.

```text
+----------------------------------------------------------------------------+
| [CCB identity] Large banner editor                         [?] [Close]       |
+----------------------------------------------------------------------------+
| [Select] [Undo] [Redo] | [Fit] [-] [ 100% ] [+] | [Snap] [Centre]         |
+------------------------------------------------------+---------------------+
|                                                      | Properties / actions|
|   scrollable neutral pasteboard                      |                     |
|                                                      | selected Image:     |
|      +----------------------------------------+      | placement, opacity, |
|      | stored banner boundary                |      | order, visibility   |
|      |                                        |      |                     |
|      | selected layers / Crop / guides       |      | Border or Overlay:  |
|      |                                        |      | existing options    |
|      +----------------------------------------+      | and validation      |
|                                                      |                     |
|   off-boundary layer parts remain manipulable        | compact Kit panels  |
+------------------------------------------------------+---------------------+
| Existing lower icon action rail                                            |
+----------------------------------------------------------------------------+
| Existing layer filmstrip / visibility / Add                                |
+----------------------------------------------------------------------------+
| Status and keyboard hint                         [Cancel] [Save changes]     |
+----------------------------------------------------------------------------+
```

Composition rules:

- The banner boundary is centred inside a larger scrollable pasteboard and
  uses the configured banner-format ratio.
- The top viewport toolbar contains only view and history actions. Business
  properties stay in the right panel.
- The right panel uses Kit modal, field, action and preview-side disclosure
  primitives. CCB owns only workspace geometry and business hooks.
- The lower action rail and filmstrip retain their existing classes and
  behavior; controls do not stretch with modal height.
- Zoom and pan are excluded from `previewlayerpayload` and document undo/redo.
- The canvas/pasteboard is focusable as one composite editing surface. Native
  buttons and form controls remain ordinary Tab stops; layer selection and
  nudging retain their current keyboard route.
- When the dialog opens, initial focus goes to its visible heading or the
  canvas entry control so the top of this large structure is not scrolled away.

## Seven human decisions

The recommendations below make the first version bounded. They are not final
until the human accepts or changes them.

| # | Decision | Options | Recommended V1 |
| --- | --- | --- | --- |
| 1 | Tablet | Full edit; view-only; unavailable below a breakpoint | Full editing at `>= 1024px` CSS viewport width with the panel docked; below 1024px show an explanation and keep the normal editor available. Mobile authoring remains excluded. |
| 2 | Outside-boundary coordinates | Persist them; temporary only; clamp on Save | Persist existing normalized offsets within the current `-1000..1000` contract. Public renderers continue to clip at the banner boundary. |
| 3 | Pan | Dedicated tool; Space+drag; scrollbars; combination | Visible scrollbars plus Space+drag while the canvas, not a form control, owns focus. Ordinary Space activation and page scrolling remain unchanged. |
| 4 | Zoom | Define min/max/step/default/reset/Fit | `25%..400%`; buttons move by 25 percentage points; Ctrl+wheel uses bounded 10% multiplicative steps; open at Fit; expose Fit and 100% reset; show a stable rounded percentage. |
| 5 | Snapping | None; boundary/centre; optional guides/grid | Preserve the existing enabled Snap toggle and peer/centre guides; add banner centre and boundary edges; no grid in V1. |
| 6 | Selection | Single; multi-select/alignment | Single image layer only in V1. Border/Overlay are edited through their property entry points. Multi-selection is a separate later lot. |
| 7 | Save lifecycle | Immediate writes; Apply to main draft; modal Save/Cancel transaction | One explicit Save/Cancel transaction. Keep a session snapshot, make no server write until Save, restore the opening document state on Cancel/Escape, and reuse the existing sesskey-protected AJAX save on Save. |

Human approval can be concise: `CCB 0049 decisions: accept recommendations`,
or list only the changed decision numbers.

## Implementation slices after approval

1. Replace the readonly-clone contract with a dedicated Kit modal shell and
   one live-editor mount point. Keep the opener and public renderer unchanged.
2. Add a viewport controller whose only state is zoom/pan/Fit. Prove ordinary
   scrolling, pointer-relative Ctrl+wheel and canonical pointer-delta mapping.
3. Compose the existing image selection, transform, Crop, history, lower rail
   and filmstrip inside the workspace without changing their payload schema.
4. Reuse or extract the existing Border/Overlay forms so the workspace property
   panel calls the same validation and strings without copying stateful code.
5. Implement the approved Save/Cancel lifecycle, dirty warning, focus return,
   loading and toast behavior.
6. Validate desktop/tablet threshold, reduced motion, keyboard, focus trap,
   Crop, Keep proportions, off-boundary save/reopen, public clipping,
   Border/Overlay parity and no console/RequireJS regression.

Each slice requires its own exact file allowlist and source contract. Runtime,
managed preview, cache and browser work remain separately authorized.

## Explicit non-goals

- No interactive code or CSS is added by this discovery batch.
- No database field, payload field or public geometry rule is added.
- No third-party canvas/editor dependency is introduced.
- No mobile authoring UI, rotation, grid, multi-selection or alignment command
  is included in V1.
- No modification is made to the shared Kit repository or Platform planning
  worktree by this CCB source lot.
