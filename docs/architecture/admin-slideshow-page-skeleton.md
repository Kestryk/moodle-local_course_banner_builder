# CCB administration Slideshow page skeleton

## Scope

`EED-CCB-2026-0005` adds a page-level loading shell around the existing
Slideshow administration content. It owns only the semantic shell in
`admin_slideshow.php`, the dedicated AMD lifecycle, the local page geometry
and the explicitly allocated Loading primitives copied from the immutable UI
Kit commit `003a3f2f1a2b5dd55c6778fd4711ef292b5778cc`.

The shell deliberately does not change Slideshow settings, modal previews,
format-picker controls, `slideshow_admin`, Navigation, Guide, Sources/Layers,
or public banner rendering.

## Lifecycle

The PHP page is fail-open: without JavaScript the live Slideshow content is
immediately available and the skeleton remains hidden. Once the dedicated AMD
module runs, it marks the shell busy, makes the status text available to
assistive technology, and makes the existing live wrapper inert and hidden
from the accessibility tree while the visual skeleton is shown.

The lifecycle releases after 180 ms of DOM quiet followed by two animation
frames. It also has a 1.5 second fail-open deadline. A missing wrapper,
unsupported observer, font failure or unexpected JavaScript exception always
releases the live page instead of trapping it behind the skeleton.

On release, `aria-busy` becomes `false`, `aria-hidden` and `inert` are
removed from the live wrapper, and the loading status is hidden. No skeleton
placeholder contains a focusable control.

## Styling boundary

`scss/components/_slideshow-page-skeleton.scss` owns only the Slideshow page
layout: navigation-sized shell, heading, two administration cards, previews,
rows and actions. It uses the vendored Loading mixins for the surface, shimmer,
stack spacing and opacity handoff. The CCB copy supplies CSS-variable fallbacks
because this snapshot does not otherwise allocate Loading tokens; themes can
still override the public `--easyedu-loading-*` variables.

The shared Loading source, `easyedu-skeleton-shimmer` keyframe and component
forward are copied unchanged from the approved UI Kit snapshot. Reduced-motion
and forced-colors behavior is supplied by those primitives; the local shell
also uses Canvas in forced-colors mode.

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
fixture/runtime lease. It proves that the final page is ready, non-busy, not
inert, and no longer exposes its placeholder or live status.

This batch performs static validation only. Runtime, cache promotion,
Playwright execution, visual review, Moodle 4.5 execution and Moodle 5.2 CI
remain separate approvals. The plugin declares Moodle 4.5 as its compatibility
floor; its established AMD pattern is retained for that floor.
