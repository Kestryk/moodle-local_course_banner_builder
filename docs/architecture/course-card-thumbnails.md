# Course-card thumbnail contract

## Ownership

Course Banner Builder progressively enhances Moodle's existing Dashboard, My
courses and course-list images. It does not replace core renderers or theme
templates. The enhancer may replace only an overview image whose URL carries
CCB's managed `course_banner_builder_auto_` marker.

## Variants and responsive density

- Wide card targets use the revisioned 1200 x 540 composite.
- Targets measured close to square, including legacy `.coursebox` surfaces,
  request the separate 960 x 960 variant.
- Both variants preserve their contents with `contain`; square courseboxes add
  an explicit 1:1 boundary while native card geometry remains theme-owned.
- An `IntersectionObserver` defers off-screen work with a 700px prefetch margin.
  Generated images also request lazy loading and asynchronous decoding.

## Priority and fallback

A valid teacher-managed Moodle overview image has priority when the setting is
enabled. Synchronisation removes obsolete CCB card artifacts but does not
replace that teacher image. If no CCB thumbnail can be generated or loaded, the
enhancer leaves the existing Moodle image or pattern untouched; `card.php`
returns not-found rather than inventing a substitute.

## Generated-file lifecycle

Synchronisation deletes the plugin's course-card file area before publishing
the current revisioned wide and square files. A read can trigger one guarded
resynchronisation when the expected revision is absent. Source changes,
disabled layers and deleted sources therefore retract stale generated cards
through the existing synchronisation path.

## Validation boundary

`tools/test-ccb-course-card-thumbnail-contract.ps1` guards the PHP hook and
route, manager priority/cleanup, AMD source/build and SCSS/generated CSS. This
is source-level evidence. Final acceptance still requires representative real
Dashboard, My courses and legacy coursebox cards in the managed Moodle matrix.
