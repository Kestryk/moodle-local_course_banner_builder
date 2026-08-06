# Batch 2F-B — Narrow public title continuation

## Status

Validated.

## Summary

This is the continuation of the public title semantics work at restrictive
layout and genuine 200% browser zoom. Batch 2F-B.1 now supplies the recorded
passing gate for this scope.

## Problem or motivation

The 100% contract does not establish that a long contextual title remains
usable and semantically correct at real browser zoom.

## Scope

Narrow public activity layout, title wrapping, visible focus, heading order,
banner containment, and cleanup at genuine 200% zoom.

## Implementation

The current scenario file contains the 2F-B.1 test definition. No separate
production implementation or completed result is asserted here.

## Files and components

- `tools/playwright/ccb-banner-public-title-accessibility-2fa.spec.js`
- [accessibility protocol](../../testing/accessibility.md)

## Decisions

Genuine browser zoom is required; device scale emulation is not equivalent.

## Validation

Batch 2F-B.1 passed on 2026-07-27 in the Moodle 5.1 headed Chrome harness.
The run proved native 200% zoom, the H1/H2 accessibility contract, narrow
layout containment, and complete fixture/profile cleanup. See the detailed
[2F-B.1 evidence](batch-2f-b1.md) and external run
`ccb-2fb1-supervised-20260727T141424977Z-26360`.

## Incidents and corrections

The supervised runner initially failed before the scenario because Windows
paths containing spaces were passed through the legacy process API. A later
QA-only run also corrected Moodle form-navigation waiting and the native
Chrome host-zoom preference. No production banner behavior was changed.

## Dependencies

Batch 2A/2B remain blocked until the controlled recovery commit is reviewed.

## Migration and recovery notes

Use the external artifact root and disposable fixture rules documented by the
testing protocol.

## Release and commit references

The test definition is present at commit `c5f33c8`. The passing evidence is
from the intentionally dirty continuity worktree; no release commit exists.

## Remaining work

Prepare the controlled recovery commit plan before resuming Batch 2A/2B.
R0.5B PHPUnit evidence is recorded as validated in the isolated Moodle 5.1.3
environment.

## Evidence

The external B2F.1 run contains the accessibility, zoom, keyboard, cleanup,
and artifact-summary evidence under the approved EasyEdu artifact root.
