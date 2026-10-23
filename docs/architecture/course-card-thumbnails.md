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

## Core cache invalidation

Moodle's own course cards do not read the generated file directly: they go
through `\core_course\external\course_summary_exporter::get_course_image()`,
which reads core's `course_image` application cache. That cache has **no
`invalidationevents`** wired in core (`course/db/caches.php`) and explicitly
caches a negative/empty result so a card viewed before a banner was configured
stays blank forever, independently of the plugin's own files and of
`cache_helper::purge_by_event('changesincourse')`. `sync_course_overview_image()`
therefore also calls `\cache::make('core', 'course_image')->delete($courseid)`
on every path that changes (or confirms the absence of) the managed overview
file. See `docs/testing/course-card-banner-title.md` (K23-32) for the full
diagnosis.

## Card title

When `bannertitle_course_enabled` is on, `coursecards.js` echoes the course
banner title on cards it has identified as CCB-managed, reusing the banner
title's own typography tokens (colour, font family, weight, caps, overlay
scrim) at a fixed, clamped card scale (`-webkit-line-clamp: 2` + ellipsis).
The full banner title's absolute position, custom point size, frame and
shadow settings are not reused — they target the full-size banner canvas and
do not translate to a card. The title text itself is read from the card's own
already-rendered title node, not fetched again per course.

## Validation boundary

`tools/test-ccb-course-card-thumbnail-contract.ps1` guards the PHP hook and
route, manager priority/cleanup, AMD source/build and SCSS/generated CSS. This
is source-level evidence. Final acceptance still requires representative real
Dashboard, My courses and legacy coursebox cards in the managed Moodle matrix.
