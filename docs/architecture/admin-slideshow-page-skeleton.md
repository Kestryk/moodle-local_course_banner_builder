# CCB administration Slideshow page skeleton

## Scope

`EED-CCB-2026-0005` adds a page-level loading shell around the existing
Slideshow administration content. It owns only the semantic shell in
`admin_slideshow.php`, the pre-RequireJS fail-open bootstrap, the dedicated AMD
readiness signal, the local page geometry and the explicitly allocated Loading
primitives. The initial Loading surface came from immutable UI Kit commit
`003a3f2f1a2b5dd55c6778fd4711ef292b5778cc`; the corrective audit synchronized
the published Loading tokens and bottom-end busy indicator from UI Kit commit
`f5aa5f72df80d8ae2a2b00c9628fcffadc5e7f56`.

The shell deliberately does not change Slideshow settings, modal previews,
format-picker controls, `slideshow_admin`, Navigation, Guide, Sources/Layers,
or public banner rendering.

## 2026-08-10 page identity parity (`EED-CCB-2026-0006`)

The Slideshow shell now mirrors the EasyStud Mass Group Import header order:
the EasyStud product mark, page title and short description appear before the
shared Navigation. The Skeleton uses the same order and the Mass Import header
heights (`0.72rem`, `1.55rem`, `0.82rem`) so it reserves the final header's
space before the live page is revealed.

The page identity remains CCB-owned. Its typography composes the existing UI
Kit eyebrow, page-title and body roles. CCB consumes the Navigation Skeleton
frame and cue primitives unchanged from immutable UI Kit snapshot
`c9277a82fb471018f4cc07b24dd336d2adfa310d`; Navigation destinations, Guide
content, lifecycle timing and geometry remain outside that snapshot boundary.

## 2026-08-25 Kit 41 section parity (`EED-CCB-2026-0006`)

CCB now consumes the visual Skeleton section primitives from immutable UI Kit
snapshot `41e86979dc8138dd026438039143f2ba94c0531e`. The two Slideshow
administration-card placeholders use `skeleton-section-frame` with the public
`--easyedu-primary` accent: their frame and logical top border are static,
while only the title, preview text, rows and action cues retain the shimmer.
The primitive supplies forced-colors `CanvasText`; reduced motion therefore
never animates either principal frame or its internal cues.

The live Course and Site icons retain the shared Kit header alignment; CCB no
longer overrides it. The localised plugin name (`pluginname`) replaces the
former EasyStud eyebrow, and the identity owns a token-based gap before the
unchanged shared Navigation. At narrow widths the CCB-owned cards still stack
in one column and apply the Kit compact Skeleton density. Logical properties
preserve the same border placement and cue rhythm in RTL. The Skeleton remains
decorative (`aria-hidden`) and contains no focusable element; its no-script and
normal ready-state handoff are unchanged.

## Lifecycle

The PHP response starts in `loading`, before interactive JavaScript: the
Skeleton is already present, `aria-busy` is true, the historical bottom-end
`Loading in progress` indicator is active and the live Slideshow controls are
not painted. A `noscript` override reveals the real page when JavaScript is
unavailable.

The classic `js/admin_slideshow_skeleton_bootstrap.js` runs before RequireJS
and owns the bounded handoff. The dedicated AMD module is registered after
`slideshow_admin` and signals that the interactive initializers have been
scheduled. The bootstrap then waits for 240 ms of visual quiet, font readiness
and a 1.2 second minimum visible interval. A 1.5 second deadline starts a
degraded fail-open handoff if that signal never arrives.

The handoff is ordered rather than simultaneous: the Skeleton fades out for
180 ms, is removed from layout, then the live page fades in for 180 ms after
two animation frames. A missing wrapper, unsupported observer, font failure or
unexpected bootstrap exception always reveals the live page instead of
trapping it behind the Skeleton.

On release, `aria-busy` becomes `false`, `aria-hidden` and `inert` are
removed from the live wrapper, and the loading status is hidden. No skeleton
placeholder contains a focusable control.

## Styling boundary

`scss/components/_slideshow-page-skeleton.scss` owns only the Slideshow page
layout: navigation-sized shell, heading, two administration cards, previews,
rows and actions. It uses the vendored Loading mixins and official public
tokens for the surface, shimmer, bounded stack spacing and opacity handoff.
The Skeleton stays in normal document flow during loading, so underlying live
controls cannot leak through or briefly affect page geometry.

### Visual parity audit

The visual reference is the Student Management Skeleton served by EasyStud
commit `986b23e229cec2cf325656e506b59a761bc75d46`, not a new CCB variation.
The accepted CCB layout remains product-owned, but every placeholder now uses
the same shared treatment:

- stable `#dce7f0` surface and `#cfdee9` border;
- 108-degree overlay sweep rather than a moving full-surface gradient;
- `rgba(255, 255, 255, 0.16)` soft edges and `0.5` centre highlight;
- two-second linear travel from `-110%` to `110%`, reversed in RTL;
- static surfaces for reduced motion and forced colours.

EasyStud applies the moving sweep to content cues, not to every large panel.
CCB follows the same composition rule: the Navigation frame, cards and preview
frames remain pale static containers, while their marks, labels, rows and
preview text/action cues use the animated Loading primitive. The outer preview
dimensions are unchanged. This prevents expensive full-panel repaints and
makes the two-second loop retain the same perceived cadence on both products.

The audit found that UI Kit commit
`f5aa5f72df80d8ae2a2b00c9628fcffadc5e7f56` documents the overlay as the
default integration example, but its shared keyframe travels in the opposite
direction and its centre token is `0.52` rather than EasyStud's `0.5`. The CCB
embedded Loading primitive is aligned to the proven product reference pending
a separately owned UI Kit canonical correction. Future consumers must import
that corrected primitive instead of recreating shimmer values locally.

The audit compared the active EasyStud runtime commit
`986b23e229cec2cf325656e506b59a761bc75d46` with the published UI Kit. The Kit
already documents the required server-first state, inert live controls,
bounded fail-open, complete page-region geometry, reduced-motion and
forced-colors behavior. The original CCB consumer had not followed that
contract: it rendered `ready` first and switched to `loading` from AMD, causing
the observed content flash. No EasyStud or UI Kit file was changed by this
correction.

The official `scss/build.ps1` rebuilds the one CCB `styles.css` asset from the
whole existing source entry point. At this baseline, that controlled rebuild
also synchronizes pre-existing EasyEdu Guide declarations which were already
imported by `scss/styles.scss` but absent from its previous generated output.
This batch does not modify Guide source, initializers or behavior; the complete
generated asset remains the reproducible output of the approved source build.

## Validation and deferred review

`tools/playwright/ccb-admin-slideshow-page-skeleton.spec.js` is the focused,
future lease-gated browser scenario. It uses only process-local credentials and
must be run with the normal isolated profile, external artifact manifest and
fixture/runtime lease. It records the state timeline and proves that `loading`
is the first observed state, remains visible for the bounded minimum, then
releases a ready, non-busy and non-inert live page.

This batch performs static validation only. Runtime, cache promotion,
Playwright execution, visual review, Moodle 4.5 execution and Moodle 5.2 CI
remain separate approvals. The plugin declares Moodle 4.5 as its compatibility
floor; its established AMD pattern is retained for that floor.
