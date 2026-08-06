# Overlay geometry adapter — public compatibility layer

## Status

Implemented — validation pending.

## Summary

The course-header overlay adapter translates existing public HTML image-overlay
fields into the deterministic geometry contract without taking ownership of
storage or unrelated renderers.

## Problem or motivation

Legacy public overlay fields need a stable migration boundary before public
rendering can adopt the canonical geometry mathematics.

## Scope

Fit mode, anchor and offset fields, crop percentages, custom dimensions, and
fixed-centre placement for public course-header HTML image overlays.

## Implementation

`course_header_overlay_geometry_adapter` performs the legacy-field translation;
`banner_geometry` remains the projection owner.

## Files and components

- `classes/course_header_overlay_geometry_adapter.php`
- `classes/banner_geometry.php`
- `tests/course_header_overlay_geometry_adapter_test.php`
- [architecture contract](../../architecture/banner-geometry.md)

## Decisions

Storage, Moodle APIs, GD, and browser behaviour remain with existing helpers
until a later measured migration.

## Validation

The focused test file is present but was not run here. No cross-surface public
measurement is claimed.

## Incidents and corrections

None recorded.

## Dependencies

Batch 2A geometry measurements and R0.5B PHPUnit isolation.

## Migration and recovery notes

This is restored worktree content; preserve it during controlled handoff.

## Release and commit references

No controlled commit reference.

## Remaining work

Run focused PHPUnit and measured preview/public comparisons before migration.

## Evidence

[Banner geometry architecture](../../architecture/banner-geometry.md) documents
ownership and the planned migration order.
