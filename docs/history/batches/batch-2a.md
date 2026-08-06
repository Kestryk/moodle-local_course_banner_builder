# Batch 2A — Preview/public geometry measurement

## Status

Partially validated.

## Summary

Batch 2A defines the cross-surface geometry comparison between administration
preview and public banner output for the supported formats and viewports.

## Problem or motivation

Crop, placement, title frame, border, overlay order, and inherited layers must
remain visually coherent when preview and public rendering are compared.

## Scope

Disposable landmarked fixture, five banner formats, desktop/tablet/mobile
viewports, and genuine 200% cells, with normalised DOM measurements.

## Implementation

The protocol is documented; the deterministic geometry service and public
adapter provide preparatory architecture but do not constitute the full
cross-surface gate.

## Files and components

- [geometry protocol](../../testing/functional-protocol.md)
- [banner geometry architecture](../../architecture/banner-geometry.md)
- `classes/banner_geometry.php`
- `classes/course_header_overlay_geometry_adapter.php`

## Decisions

Compare normalised child rectangles against the frame content box and allow
only documented one-pixel rounding.

## Validation

The first controlled cell, Batch 2A.1 (`standard`, desktop 1600x900), passed in
the Moodle 5.1 fixture harness on 2026-07-28. Batch 2A.2 (`contentwide`, tablet
1024x768) initially exposed an acceptance blocker: the admin preview frame
measured about 4:1 while the public frame measured about 5:1. Evidence for
that diagnostic is external to Git at
`C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\batch-2a\supervised\ccb-2a1-supervised-20260728T065535175Z-39680`.
The 2A.2 diagnostic artifact is
`C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\batch-2a\supervised\ccb-2a2-contentwide-tablet-20260728T082757376Z-26328`.

The specificity and minimum-height correction was then validated by a single
isolated 2A.2 rerun. The admin ratio measured `5.000382:1`, the public ratio
`5.0001:1`, the maximum first-cell delta was `0.003548`, console errors were
empty, and only `net::ERR_ABORTED` requests were observed. The course format
and course 11 were restored, no temporary category remained, and the profile
was removed. The accepted evidence is external to Git at
`C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\batch-2a\supervised\ccb-2a2-supervised-20260728T093741260Z-32748`.

The remaining format/viewport cells are still pending; this does not yet close
the full Batch 2A gate.

The historical dedicated source-preview responsive matrix also passed on
2026-07-28, including `fullwidthtopinset-mobile-200`. After the subsequent
Gate 2 action-contract patch, the replacement five-cell run is recorded as
blocked: three cells passed, while desktop 100% failed during login/navigation
and tablet 100% timed out in `page.goto`, before CCB geometry assertions. The
controls remain inside a bounded panel, action labels can wrap at narrow
effective widths, and primary actions share a row whenever the available width
permits it; the replacement run must be green before using this evidence to
advance the full Batch 2A gate.

## Incidents and corrections

The generic matrix harness initially lacked the selected source key and a
sufficient preview-frame wait. The dedicated 2A.2 scenario was then corrected
to apply and restore the global CCB format. The diagnostic revealed that the
more-specific native admin 4:1 rule and its 8.25rem minimum height overrode
the global format modifiers. Equal-specificity admin format selectors with
`min-height: 0` now preserve the standard 4:1 fallback while allowing the
selected non-standard format ratio. The failed attempts and both diagnostic/
rerun artifacts remain external and did not alter the final fixture state.

## Dependencies

Complete Batch 2F-A.1 and 2F-B.1 first, then obtain a controlled fixture and
human review.

## Migration and recovery notes

Do not use a full-page screenshot as a CI gate; retain DOM measurements and
fixture metadata.

## Release and commit references

No completed release or commit reference is claimed.

## Remaining work

Continue the remaining documented format/viewport cells. Do not rerun 2A.1
unless its external evidence is lost.

## Evidence

`docs/testing/functional-protocol.md` and
`docs/architecture/banner-geometry.md` are the current authorities.
