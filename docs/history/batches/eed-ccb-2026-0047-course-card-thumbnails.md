# EED-CCB-2026-0047 - Course-card thumbnail audit

## Scope

This batch restores the legacy 0009-D to 0009-H acceptance direction: wide
Dashboard/My courses cards, square courseboxes, teacher-image priority, Moodle
fallback, lazy loading, responsive density and generated-file cleanup. Banner
generation, administration previews, Crop and public banner geometry are
excluded.

## Static audit result

The cumulative Wave 10 RF1 base already implements every requested source
contract:

- `coursecards.js` discovers native Moodle card surfaces, selects wide or square
  URLs from measured geometry, defers off-screen work and replaces only CCB
  managed overview images after the new thumbnail has loaded;
- `manager.php` publishes separate dense wide and square composites, preserves
  teacher-managed overview images, removes obsolete generated card files and
  exposes revisioned URLs;
- `card.php` keeps a not-found fallback instead of overwriting native Moodle
  content; and
- the source and generated CSS preserve contained wide images and bounded 1:1
  courseboxes.

No product-source or generated-asset correction was justified by the audit.
The batch therefore adds a focused static contract and architecture record only.

## Validation and remaining gate

The focused contract must pass with PHP/JavaScript syntax and `git diff
--check`. No AMD or Sass rebuild is required because their source did not
change. No runtime, cache, fixture or browser activity belongs to this source
audit.

Actual Dashboard, My courses and coursebox rendering remains a separate human
and managed-browser gate. Since this batch changes no product runtime, it does
not justify a new CCB preview on its own.
