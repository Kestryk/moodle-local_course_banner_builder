# Observed native banner alignment

## Contract

All native site/course banner shells use one horizontal alignment controller in
`hook_callbacks::get_banner_content_alignment_script()`. Page classification still
controls whether a banner exists, and formats still control vertical placement,
aspect ratio and presentation. Neither gates horizontal alignment. No Moodle core,
theme setting, template or theme stylesheet changes are required.

The content root is Moodle's `#region-main`, with structural `main`/`role=main`
and local `.main-inner` fallbacks. The controller walks visible in-flow structural
wrappers and stops at a framed box or sibling content row. It excludes the banner,
non-content tags, hidden nodes and floating surfaces. Padding belongs to the
wrapper, not the reference frame. There is no list of page types or format names.

A ResizeObserver follows the measured path, frame and ancestors; this detects
both changing content width and a fixed-width column shifted by a drawer.
Width changes synchronize before paint. The shell width stays relative to its
parent (100% plus the measured gutter difference), so a shared parent resize
does not wait for a new pixel-width measurement. Deferring that callback through another
requestAnimationFrame produced a measured one-frame lag during drawer animation.
DOM replacements reselect the content path, layout-class changes and transition
completion cover positional changes, and window resize remains a fallback.
Repeated mounts reuse a WeakMap controller, unchanged styles are not rewritten,
and observers/listeners detach on a real page exit (back-forward cache is retained).
The shell is aligned before its mounted class makes it paintable. Its no-script
fallback and existing vertical positioning remain intact.

## Earlier failure

The earlier scripts duplicated content measurement for course/site, called it
only inside fullwidthtop placement branches, and relied on finite startup timers
plus window resize. Standard/contentwide therefore fell back to header-maxwidth,
including Moodle's padded main wrapper: the deployed standard banner was 15px
wider on each side in all 20 sampled community/staging cases. A drawer changing
content geometry without window resize could also leave full-width formats stale.
The former fullwidthtopinset horizontal inset is superseded by the requirement
that every shell share the content edges; its vertical/image presentation remains.

## Tests and reproducible gates

| Script | Classification | Purpose | Prerequisites |
| --- | --- | --- | --- |
| `tools/test-banner-content-alignment.cjs` | Current source/producer contract | Parse both real PHP-generated mount scripts and verify shared observed alignment registration. | PHP CLI via `CCB_TEST_PHP`. |
| `tools/playwright/ccb-content-alignment-fixture.cjs` | Isolated browser candidate | Both mount producers, five formats, 1600/768px, open/closed drawer and replaced content plus hidden pre-mount/first-paint checks: 100 cases. | `CCB_TEST_PHP`, `CCB_TEST_PLAYWRIGHT`, `CCB_TEST_CHROME`, external `CCB_TEST_RESULTS`. |
| `tools/playwright/ccb-content-alignment-final.spec.js` | Supervised native candidate | Five real Moodle contexts, five formats, two widths; sample actual drawer animation and final state against independent page content landmarks. | Authenticated wrapper, exclusive runtime/config fixture lease and external artifact root. |
| `tools/playwright/ccb-dashboard-drawer-final.spec.js` | Supervised native candidate | Explicit native block-drawer commands after awaiting theme_boost/drawers; assert that the actual target both opens and closes, then sample both edges. | Same exclusive fixture lease; supplements the main matrix. |
| `tools/validate-banner-content-alignment-results.cjs` | Native evidence gate | Require both native matrices, all 50 cells, 40 actual drawer cycles and every sampled edge within 0.5px; reject clicks that never open a drawer. | External artifact root as CLI argument. |
| `tools/playwright/ccb-production-alignment-readonly.spec.js` | Supervised read-only candidate | Same five contexts on community/staging; diagnosis before deployment and later acceptance. | Authenticated wrapper, external artifact root; never changes course data or configuration. |

The native fixture temporarily changes only course/site banner format config and
purges caches on the exclusively leased copy. Back up and restore that config and
the deployed plugin files. Wait for the native drawer AMD module before clicking its commands. A visible
button can precede its listener registration on a busy dashboard; a click with no
state change is not a drawer acceptance result. The native page's own End tour / Close drawer controls
must be used when onboarding overlays obscure the outside toggle: never force a
click through an overlay or hide it to obtain a pass. Catalogue without a drawer
has a static cell and an explicit unavailable marker, not a fabricated drawer test.

Both generated scripts remain inline PHP output, not AMD modules: no AMD source
or compiled bundle changes. PHP lint, syntax compilation of generated JavaScript,
producer/browser tests and source diff review are the build gates for this change.
Keep CRLF in the working tree and evaluate MoodleCS on an LF-normalized disposable
copy as in Linux CI, separately from the raw Windows line-ending diagnostics.

## Deployment gate

Push a dedicated branch, rebase against the current integration branch, and wait
for the orchestrator's deployment approval. After deployment/reset of PHP opcache,
replay the same community/staging measurements plus dynamic drawer cycles before
closing K23-54. A local PASS is not a production acceptance result.

The earlier all-format commits `9b35a8e` and `d08ab52` were present only on
their dedicated branch and were not ancestors of integration HEAD `65ac2f5`.
The new change is standalone on that integration base; deploying an old snapshot
or omitting the first commit must not be mistaken for a recurring runtime fix.

The main geometry run and explicit dashboard companion are both mandatory. Run
`node tools/validate-banner-content-alignment-results.cjs ARTIFACT_ROOT` after
them; `final-combined.json` is the acceptance matrix. Neither an exit code alone
nor a click without an observed open/closed state certifies dynamic alignment.
