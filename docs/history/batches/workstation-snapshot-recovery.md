# Workstation snapshot and recovery

## Status

Pending historical confirmation.

## Summary

The CCB worktree was restored on the professional Moodle 5.1 installation from
the verified workstation snapshot workflow. The intentional dirty state is
preserved for later controlled continuity.

## Problem or motivation

The former workstation paths and the historical handoff package were not
available on this machine. Recovery therefore had to map real local paths and
avoid treating an unrelated snapshot as an identical package.

## Scope

Snapshot manifest/READY evidence, repository identity, branch and HEAD
continuity, dirty-worktree preservation, artifact portability, and handoff
ownership.

## Implementation

The active checkout is the Moodle 5.1 CCB repository on branch
`wip/desktop-k1gsrvt/ccb-continuity-2026-07` at HEAD
`c5f33c8ab50bdc32f181dc31c22df97bb0c5fd00`. No reset, clean, stash, history
rewrite, or destructive restoration is part of this record.

## Files and components

- `docs/history/`
- [testing portability](playwright-artifact-portability.md)
- [workstation handoff guidance](../../../CHANGELOG.md)

## Decisions

Preserve dirty worktrees, use real local paths, keep external artifacts out of
Git, and require human lineage review before branch creation or push.

## Validation

The snapshot and continuity reports were used during recovery. Exact historical
package equivalence and every source lineage detail remain `Pending historical
confirmation` in this registry.

## Incidents and corrections

The absent historical package was not silently substituted with the snapshot;
the distinction is retained as a migration note.

## Dependencies

Controlled commit review, R0.5B isolation, and orchestrator approval for any
future cross-workstation transfer.

## Migration and recovery notes

This document intentionally avoids obsolete absolute workstation paths. Consult
the workstation-sync snapshot reports for path mappings and manifests.

## Release and commit references

No release or push is claimed. The current branch and HEAD are recorded above.

## Remaining work

Confirm the historical lineage and package relationship before final release.

## Evidence

Verified snapshot reports, READY/manifest records, and the preserved Git state
are the authorities. Where those reports are not present in this repository,
the status remains `Pending historical confirmation`.
