# Banner geometry — deterministic contract

## Status

Implemented — validation pending.

## Summary

The restored worktree contains a pure geometry contract and a compatibility
adapter for public course-header image overlays. The service owns projection
mathematics; renderers retain storage and Moodle integration.

## Problem or motivation

Preview and public renderers previously carried overlapping coordinate logic.
A deterministic contract gives later migrations one measurable source of
normalised geometry.

## Scope

Canonical coordinates, format ratios, crop/fit policy, title safe width,
border percentages, thumbnail adaptation, and explicit renderer ownership.

## Implementation

`banner_geometry` computes deterministic projection values. The course-header
adapter translates legacy public fields into that contract without changing
other renderers.

## Files and components

- `classes/banner_geometry.php`
- `classes/course_header_overlay_geometry_adapter.php`
- `tests/banner_geometry_test.php`
- `tests/course_header_overlay_geometry_adapter_test.php`
- [architecture contract](../../architecture/banner-geometry.md)

## Decisions

The renderer migration order is measurement first, public-background
correction second, card migration third, and helper retirement last.

## Validation

Focused PHPUnit files are present in the restored worktree. They were not run
in this documentation task; status remains `Implemented — validation pending`.

## Incidents and corrections

None recorded for the pure contract itself.

## Dependencies

Batch 2A cross-surface evidence and R0.5B PHPUnit isolation.

## Migration and recovery notes

The files are part of the intentional dirty restored state and must not be
overwritten by a branch operation.

## Release and commit references

No controlled commit is claimed for the restored additions.

## Remaining work

Run the focused tests in the approved PHPUnit environment and obtain the
cross-surface geometry evidence.

## Evidence

[Banner geometry architecture](../../architecture/banner-geometry.md) records
the formulas, format ratios, ownership, and migration order.
