# EasyEdu active lot registry

Last updated: 2026-08-29

This is the versioned operational registry for the current EasyEdu UI wave.
It prevents user requests from surviving only in chat history. Every agent
must read this file before starting or resuming one of these lots.

The Platform repository remains the long-term cross-repository source of
truth. Until its currently dirty planning checkout is handed off cleanly, this
registry is the authoritative working copy for the lots below. Synchronising
it to Platform is a documentation handoff, not permission to overwrite that
checkout.

## State vocabulary

- `planned`: scope recorded; source work has not started.
- `auditing`: read-only investigation in an exclusive worktree.
- `implementing`: source changes in an exclusive worktree.
- `source-ready`: committed, pushed and statically validated.
- `preview-ready`: preflight passed; runtime publication still gated.
- `human-review`: visible candidate available for human review.
- `blocked`: named dependency or evidence is missing.
- `closed`: acceptance recorded; do not reopen without a new RF identifier.
- `parked`: intentionally waiting for user specifications or prioritisation.

No item is deleted when its state changes. Update its state, evidence, parent
SHA, owner and next action instead.

## Ordered active programme

| Order | Lot | Owner | State | Depends on | Outcome |
| --- | --- | --- | --- | --- | --- |
| 0 | `EED-OPS-2026-0016` | Platform | blocked | clean handoff of the dirty Platform planning checkout | Mirror this registry into Platform and make it discoverable from every repository AGENTS contract |
| 1 | `EED-KIT-2026-0001` | EasyEdu UI Kit | planned | none | Canonical modal, action, preview-surface and pagination primitives |
| 2A | `EED-CCB-2026-0042` | CCB | planned | Kit audit; consume published primitives when reusable | CCB modal and source-hierarchy visual parity |
| 2B | `EED-UI-2026-0030` | EasyStud | planned | Kit audit; consume published primitives when reusable | Global EasyStud controls, block layout and pagination parity |
| 2C | `EED-CCB-2026-0043` | CCB | implementing | stable CCB current base | Image-modal Crop/Recrop geometry stability; QA1 evidence harness is source-ready after validation |
| 3 | `EED-CCB-2026-0044` | CCB | planned | 0042 and 0043 | Motion and draggable-layer visual parity |
| 4A | `EED-UI-2026-0031` | EasyStud | planned | 0030 and canonical Skeleton Kit | Skeleton coverage for every eligible EasyStud view |
| 4B | `EED-CCB-2026-0045` | CCB | planned | 0042, 0044 and 0031 findings | Skeleton reconciliation for every eligible CCB view |
| 5 | `EED-CCB-2026-0036` | CCB | blocked | clean integrated CCB HEAD after the active visual/behaviour wave | Saved-baseline and unsaved-changes indicator |
| 6 | `EED-CCB-2026-0009-D-H` | CCB | planned | stable CCB integration base | Course-thumbnail end-to-end programme |
| 7 | `EED-CCB-2026-0002-D` | CCB | planned | current-base source audit | Public Slideshow labels and geometry recurrence |
| 8 | `EED-CCB-2026-0046` | CCB | parked | user specification | Enlarged-preview behaviour, deliberately undefined for now |

Orders `2A`, `2B` and the read-only phase of `2C` may run in parallel in
separate worktrees. Product integration into a shared runtime remains
sequential. A shared Kit change is published before either consumer copies it.

## EED-OPS-2026-0016 - Platform registry synchronisation

### Current evidence

- The working registry is versioned in this CCB branch and is mandatory for
  CCB agents through the repository `AGENTS.md` contract.
- The Platform planning checkout is dirty in `AGENTS.md`, development plans,
  CCB/EasyStud roadmaps, orchestration documentation and tools.

### Remaining work

- Obtain a clean Platform ownership handoff without reset, stash, clean, merge
  or overwrite of the current changes.
- Merge this registry semantically into the Platform development plan and the
  CCB, EasyStud and UI Kit roadmaps.
- Add or update the repository agent pointers so a fresh agent in any of the
  three repositories discovers the same canonical registry automatically.
- Replace duplicated temporary registries with links to the canonical Platform
  document once that document is published.

### Acceptance

- One canonical registry is reachable from every relevant `AGENTS.md`.
- The Platform worktree is clean, pushed and records the registry commit.
- No product source, runtime, cache, lease or browser is touched.

## EED-KIT-2026-0001 - shared visual foundations

### Scope

- Make the modal close control strictly identical to the accepted EasyStud
  control: dimensions, border, icon centring, hover, focus and forced-colors.
- Define one Save/action-button contract: neighbour-compatible height, icon
  slot, floppy-disk icon, label alignment and disabled/focus states.
- Define one edit-pencil contract with stable spacing and no underline in any
  hover, active or visited state.
- Publish one neutral checkerboard preview surface identical to existing
  accepted preview surfaces, with no consumer-specific blue tint.
- Define compact pagination arrows and button-content centring primitives.
- Document desktop/mobile parity and `prefers-reduced-motion` behaviour.

### Exclusions

- No CCB or EasyStud lifecycle, markup or business action.
- No consumer geometry, persisted values or route changes.

### Acceptance

- Kit contracts, Sass build, forced-colors and reduced-motion checks.
- Separate consumer adoption; Kit proof is not consumer preview proof.

## EED-CCB-2026-0042 - modal and source-hierarchy visual parity

### Page identity and edit entry points

- Use the established blue brand colour for the Slideshow title
  `Course Banner Builder`.
- Provide the standard pencil beside Parent source and Source composition
  mode, in the configured-source list and in the Selected source description.
- Replace the broken Selected source dropdown with direct opening of the
  correct edit modal.
- Preserve readable spacing and baseline alignment between pencil and text.
- Never underline the pencil or its menu actions on hover.

### Parent modal

- Keep the search field and render the parent list below it, never above it.
- Use the exact shared EasyStud close control.
- Add the floppy-disk icon to Save changes.
- Give Save and Cancel matching compatible heights and vertical alignment.
- Preserve opacity, backdrop, focus trap, Escape and focus return.

### Preview and source-chain rows

- Remove the competing preview frame and use the exact neutral shared
  checkerboard surface.
- Restyle `Existing border in the chain` and `Existing overlay in the chain`
  with the complete current layer-row language: inherited identity, locked
  state, background, type rail, Layer Overrides information and alignment.
- Harmonise `Edit the source of this layer` with the shared action contract.
- Harmonise `Collapse all` with comparable Kit actions such as `Select all`.
- Replace the overly bold `Layer & Overrides` title typography with the sober
  shared heading typography.
- Standardise every CCB `Save changes` button, including the button below the
  source-layer list, without changing its submit target.
- Apply the accepted close control to every CCB modal, not only Parent source.

### Acceptance

- Static PHP, AMD, Sass, accessibility and exact-allowlist checks.
- Targeted modal/source scenarios at desktop, tablet, mobile and native 200%.
- Human review of Parent modal, Selected source, inherited Border/Overlay
  rows, layer modal preview and every changed close/Save control.

## EED-UI-2026-0030 - EasyStud global control and pagination parity

### Scope

- Centre text and icons in every upper action button, including disabled
  controls, on every EasyStud view rather than only Groups/Groupings.
- Redesign `Groups without grouping` in Complete view to match the surrounding
  sections without changing its disclosure behaviour.
- Keep each lower pagination row at the bottom of its content block even when
  the block contains few cards; do not make it viewport-fixed.
- Adopt compact Kit pagination arrows on all EasyStud views.
- Preserve desktop/mobile parity, keyboard focus, labels and action semantics.

### Acceptance

- Inventory every view before implementation to avoid another local-only fix.
- Human review on representative short and long lists at desktop and mobile.

## EED-CCB-2026-0043 - image Crop/Recrop stability

### Scope

- Reproduce the reported automatic image shrink during Crop and Recrop in the
  add/edit image-layer modal.
- Identify whether canvas sizing, crop zoom, modal reflow or rehydration owns
  the change before editing product code.
- Keep image and preview geometry stable during edit, Apply, Undo, reopen and
  a second crop.

### Safety boundary

- This lot is behaviour-sensitive and remains separate from 0042 styling.
- Do not modify public preview geometry, other modal types or persisted crop
  semantics without evidence.

### Acceptance

- One focused scenario with before/after geometry and persisted payload proof.
- Mandatory human visual review of initial crop, recrop and restored preview.

### QA1 evidence harness

- `EED-CCB-2026-0043-QA1` has one source-owned Crop/Recrop scenario and one
  supervised runner. It is independent from the cumulative wave spec and does
  not authorise a browser run.
- The later Platform allowlist must contain only the seven paths listed in
  `docs/history/batches/eed-ccb-2026-0043-qa1-crop-recrop.md`; it must not
  permit product, AMD or Kit paths from this QA-only branch.

## EED-CCB-2026-0044 - motion and draggable-layer parity

### Scope

- Add restrained transitions for Desktop/Mobile public simulation changes,
  disclosure open/close and other state changes that benefit from continuity.
- Respect `prefers-reduced-motion`; animation must not carry essential meaning.
- Give movable source-layer rows the accepted EasyStud card-drag language:
  opaque lifted item, visible origin placeholder, preserved layout space and
  clear drop state.
- Preserve locked rows, keyboard alternatives, ordering and saved data.

### Acceptance

- Motion and drag tests remain separate from modal/Crop assertions.
- Human review of mode switching, Layer & Overrides and a complete drag cycle.

## EED-UI-2026-0031 - complete EasyStud Skeleton coverage

- Inventory every EasyStud view with real navigation or asynchronous loading.
- Apply the canonical static frames, compact Navigation Skeleton and Guide cue.
- Keep shimmer on decorative cues only.
- Preserve lifecycle, `aria-busy`, no-script, fail-open, RTL, reduced-motion,
  forced-colors and zoom containment.
- Record every view in the Kit-to-consumer coverage matrix.

## EED-CCB-2026-0045 - complete CCB Skeleton reconciliation

- Start only after the EasyStud coverage rules are stable.
- Inventory every eligible CCB view and consume the same Kit primitives without
  introducing a competing local Skeleton system.
- Preserve CCB-specific DOM, lifecycle, Filemanager and modal behaviour.
- Validate Slideshow plus all newly covered CCB views independently.

## Preserved later lots

### EED-CCB-2026-0036 - unsaved-changes indicator

- Compare the current canonical preview state with the server-confirmed saved
  baseline independently of the bounded Undo/Redo stack.
- Show a discreet localised indicator beside Save only while state differs.
- Preserve the established 0020 POST, sesskey, lock, toast, focus,
  rehydration and error contracts.

### EED-CCB-2026-0009-D-H - course thumbnails

- Integrate the backend on the current base.
- Provide the approved UI choice and teacher-image priority.
- Support wide Dashboard/My courses thumbnails and square courseboxes with
  Moodle-native fallback.
- Cover events, transfer, backup/restore and runtime validation.

### EED-CCB-2026-0002-D - public Slideshow recurrence

- Re-audit current public labels and geometry before reusing any old patch.
- Preserve persisted coordinates and established public rendering contracts.

### EED-CCB-2026-0046 - enlarged preview

- Reserved identifier only. Do not audit or implement until the user supplies
  the expected behaviour and affected surfaces.

## Closed reference lots

- `SKELETON-B-K3.1`: EasyStud Student Management and Mass Import matrix passed
  at 320/390, LTR/RTL and native 100/200% zoom.
- `EED-CCB-2026-0006`: CCB Slideshow Skeleton accepted.
- `EED-CCB-2026-0041`: image A/B selection and accessibility work absorbed in
  the current CCB integration base.
- `EED-UI-2026-0028-B`: expanded Group menu stacking scenario passed. Later
  typography or global-control work belongs to 0029, not a reopened 0028-B.

## Chronology and handoff rules

1. Register a new request here before source work starts.
2. Assign exactly one owner, exclusive worktree and immutable base.
3. Audit source-only first when behaviour or ownership is uncertain.
4. Publish reusable primitives in Kit before consumer integration.
5. Commit, push and report static evidence before preview consideration.
6. Integrate compatible candidates cumulatively, one runtime owner at a time.
7. Run discovery before the single authorised focused scenario.
8. Stop on the first real product failure; do not retry unchanged.
9. Request human visual validation for every visible wave, with exact screens
   and captures to inspect.
10. Update this registry with state, SHA, evidence and next action after every
    milestone or blocker. Closed items remain in the history.

## Required agent handoff fields

Every lot handoff must report:

- lot identifier and title;
- repository, worktree, branch, base SHA and candidate SHA;
- owner and state from the vocabulary above;
- exact allowlist and exclusions;
- static checks, preview status, browser scenario and human-review status;
- blocker category: product, QA, runtime, tooling or external dependency;
- rollback commit or recovery procedure;
- one concrete next action and escalation condition.

## Platform synchronisation gate

The current `C:\dev\easyedu-platform` checkout contains uncommitted planning,
orchestration and roadmap work. Do not reset, clean, stash, merge or overwrite
it. Once its owner provides a clean handoff, copy this registry semantically
into the Platform development plan and the CCB, EasyStud and UI Kit roadmaps,
then replace this temporary authority note with the final Platform location.
