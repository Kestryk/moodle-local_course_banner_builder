# Batch 2F-A — Public title accessibility gate

## Status

Partially validated.

The focused scenario and its 100% activity cell are validated; the wider
Batch 2F-A scope still has a separate manual and 200% continuation.

## Summary

The batch establishes that CCB does not replace Moodle's canonical activity
heading with an accessible duplicate. It covers the public title ownership
contract and reversible authenticated fixture checks.

## Problem or motivation

Visual course/activity title overlays can duplicate Moodle headings. Hiding a
page-header ancestor or emitting a second primary heading would damage the
accessibility tree.

## Scope

Moodle 5.1 public activity pages, course-title replacement, decorative banner
layers, contextual titles, heading order, and focused browser evidence.
Moodle 4.5 remains a later static port review.

## Implementation

The current restored implementation keeps the Moodle activity `h1`, emits a
distinct contextual CCB `h2` with the secondary semantic marker, and isolates
decorative title layers individually. Existing geometry and title styling are
outside this batch.

## Files and components

- `classes/hook_callbacks.php`
- `tools/playwright/ccb-banner-public-title-accessibility-2fa.spec.js`
- [public title semantics](public-title-semantics.md)
- [accessibility protocol](../../testing/accessibility.md)

## Decisions

The public banner surface itself is not `aria-hidden`; only decorative
descendants are isolated. A contextual title is suppressed only when it is an
exact duplicate of Moodle's mounted `h1`.

## Validation

Batch 2F-A.1 passed at 100% zoom on 2026-07-26, including accessibility-tree,
heading-order, duplicate-heading, geometry, and fixture/profile cleanup
assertions. This does not prove the genuine 200% continuation.

## Incidents and corrections

An early width assertion treated the vertical scrollbar reservation as content
overflow. The assertion was corrected to measure the real document width; no
production geometry change was needed for that correction.

## Dependencies

Batch 2F-B.1 owns the genuine 200% continuation. Batch 2A/2B remain blocked
until the 2F gates are complete.

## Migration and recovery notes

The scenario runs from the Moodle 5.1 CCB checkout and writes evidence outside
Git. Fixture cleanup is mandatory before releasing shared Moodle resources.

## Release and commit references

Commit `c5f33c8ab50bdc32f181dc31c22df97bb0c5fd00` adds the focused scenario.
The current restored production implementation is intentionally dirty; no
release or push is claimed.

## Remaining work

Run Batch 2F-B.1 only after separate approval, then complete the controlled
commit and the deferred 2A/2B gates.

## Evidence

The scenario name, assertions, cleanup contract, and external artifact summary
are the authority for the validated 100% cell. See [Batch 2F-A.1](batch-2f-a1.md).
