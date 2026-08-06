# Batch 2F-A.1 — Activity `h1` and contextual `h2` at 100%

## Status

Validated.

## Summary

The real Moodle activity page retains exactly one accessible Moodle `h1` and
exposes one distinct contextual CCB `h2` at 100% browser zoom.

## Problem or motivation

Course-title replacement must not hide or replace the activity heading. A
course-and-activity context needs a second semantic heading without an
accessible duplicate.

## Scope

One reversible Moodle 5.1 activity fixture, the authenticated public page,
heading semantics, accessibility snapshot, title decoration, geometry, and
cleanup.

## Implementation

The activity heading remains Moodle-owned. CCB's context title is marked as the
secondary semantic heading. Visual duplicate titles remain decorative and
keyboard-hidden descendants are rejected.

## Files and components

- `classes/hook_callbacks.php`
- `tools/playwright/ccb-banner-public-title-accessibility-2fa.spec.js`
- `tools/playwright/playwright.config.js`

## Decisions

The test uses the real activity URL and Moodle context; it does not hard-code a
module route, URL, or activity identifier.

## Validation

The selected scenario passed at 100% on 2026-07-27 through the fixture-aware
supervisor. It proved one visible
accessible `h1`, one distinct secondary `h2`, semantic marker, `h1` before `h2`,
accessibility-tree expectations, no duplicate accessible heading, no real
horizontal overflow, and complete fixture/profile cleanup.

## Incidents and corrections

The prior 15px width failure was a QA assertion issue caused by the vertical
scrollbar layout reservation, not real page overflow.

## Dependencies

Batch 2F-B.1 is the genuine 200% follow-up. R0.5B PHPUnit isolation is still
required before a controlled commit.

## Migration and recovery notes

Artifacts and owned browser profiles are external to Git. The disposable local
fixture was restored before QA ownership was released.

## Release and commit references

The scenario was added by `c5f33c8`. The accepted generated CSS authority was
kept unchanged during this validation.

## Remaining work

Do not call this entry evidence for genuine 200% behavior; that belongs to
Batch 2F-B.1.

## Evidence

The external run contains `cleanup.json`, `artifact-summary.json`, and the
activity accessibility evidence under the approved CCB artifact root. The
accepted run is `ccb-2fa1-supervised-20260727T151314953Z-19692`.
