# Public sizing — 128px non-standard floor

## Status

Validated.

## Summary

The approved public minimum for non-standard native banner formats is 128px.
Existing format ratios and maximum heights remain in force.

## Problem or motivation

Responsive containers could become too short at narrow widths, while changing
the canonical authoring geometry would create a different problem.

## Scope

Public `contentwide`, `fullwidthtop`, `fullwidthtopcompact`, and
`fullwidthtopinset` formats. The `standard` 4:1 base rule is unchanged.

## Implementation

Both public authorities use the same grouped selector and policy:

```text
displayHeight = clamp(128px, containerWidth / selectedFormatRatio, existingFormatMaxHeight)
```

The authorities are the runtime stylesheet emitted by `hook_callbacks` and the
compiled SCSS fallback.

## Files and components

- `classes/hook_callbacks.php`
- `scss/components/_native-banner-core.scss`
- `styles.css`
- [geometry and sizing architecture](../../architecture/banner-geometry.md)

## Decisions

The 128px floor is a responsive container policy, not a change to the 1600x400
authoring space, overlay coordinates, cards, thumbnails, or admin modals.

## Validation

The accepted generated `styles.css` SHA-256 is
`9D22821DA714691B53D7BD03E85D8A22F65934EAB08782C8A9137C1C6D389573`.
The current file matches that hash. The documented policy and authority were
verified; no build was run in this task.

## Incidents and corrections

An earlier continuity investigation found stale public sizing in a checkout;
the accepted hash and SCSS/runtime dual authority are now recorded explicitly.

## Dependencies

R0.5B PHPUnit isolation and a controlled commit of the restored worktree.

## Migration and recovery notes

The current checkout's sizing changes are part of the intentional dirty state;
do not replace the whole stylesheet during recovery.

## Release and commit references

The 128px policy is documented in the 2026-07-22 changelog entry. No new
commit is made by this task.

## Remaining work

Complete R0.5B and preserve both authorities in the controlled commit.

## Evidence

[Banner geometry architecture](../../architecture/banner-geometry.md), the
accepted SHA-256, and the current file hash.
