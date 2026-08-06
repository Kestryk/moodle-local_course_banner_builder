# Public title semantics — Moodle-owned headings

## Status

Partially validated.

## Summary

CCB treats the Moodle page heading as the semantic authority while keeping the
visual banner title decorative. Activity context is a distinct secondary
heading when it adds information.

## Problem or motivation

Visual title replacement must not create duplicate accessible headings or hide
the canonical Moodle heading from assistive technology.

## Scope

Course, activity, and site title ownership, decorative isolation, contextual
`h2`, exact-text de-duplication, and future control mounting.

## Implementation

`hook_callbacks` preserves Moodle's `#page-header h1`; decorative image/title
descendants are individually isolated; activity/site context uses the
secondary semantic marker. The public banner surface remains available to
future interactive controls.

## Files and components

- `classes/hook_callbacks.php`
- [architecture note](../../architecture/public-title-semantics.md)
- [accessibility protocol](../../testing/accessibility.md)

## Decisions

JavaScript may suppress an exact duplicate secondary title, but it can never
remove the Moodle primary heading. Do not place future controls under an
`aria-hidden` ancestor.

## Validation

The targeted 100% activity scenario validated one accessible `h1`, one distinct
secondary `h2`, order, accessibility snapshot, decorative state, geometry, and
cleanup on 2026-07-26. Genuine 200% review remains separate.

## Incidents and corrections

The scrollbar-reservation width assertion was corrected in QA; no title
geometry regression was found.

## Dependencies

Batch 2F-B.1 and manual screen-reader/200% review.

## Migration and recovery notes

The contract is Moodle 5.1 validated. Moodle 4.5 remains static port review
only.

## Release and commit references

Focused test added at `c5f33c8`; current implementation remains in the dirty
restored worktree.

## Remaining work

Complete the genuine 200% gate and controlled commit.

## Evidence

[Public title semantics architecture](../../architecture/public-title-semantics.md)
and the external focused QA artifacts.
