# Playwright artifact portability

## Status

Implemented — validation pending.

## Summary

CCB browser scenarios use a portable configuration and keep profiles, traces,
screenshots, JSON evidence, and cleanup reports outside Git.

## Problem or motivation

The former harness could resolve the wrong test directory or leave browser
artifacts in a repository checkout, making handoffs non-deterministic.

## Scope

The CCB Playwright configuration, owned Chrome profiles, external artifact
root, process-only credentials, scenario markers, and cleanup reports.

## Implementation

`tools/playwright/playwright.config.js` is the active local configuration. The
focused 2F-A.1 scenario records ownership and cleanup evidence in its external
run directory and removes its temporary profile.

## Files and components

- `tools/playwright/playwright.config.js`
- `tools/playwright/ccb-banner-public-title-accessibility-2fa.spec.js`
- [functional protocol](../../testing/functional-protocol.md)

## Decisions

Use `-c .` from the CCB `tools/playwright` directory and keep credentials in
environment variables only. Do not use the historical GroupImport path for
this checkout.

## Validation

The 2F-A.1 run produced complete cleanup and external evidence on 2026-07-26.
The full portability matrix remains pending.

## Incidents and corrections

An earlier grep invocation selected the scenario by a shorter title fragment;
the runner's one-test listing still confirmed a single selected scenario. No
repository-local `test-results` directory remained.

## Dependencies

R0.5B isolation and future approved browser fixtures.

## Migration and recovery notes

Artifacts must remain outside Git on every workstation. Snapshot or branch
paths are migration metadata, not runtime artifact roots.

## Release and commit references

The scenario is in `c5f33c8`; the active configuration is uncommitted restored
worktree content.

## Remaining work

Run only the separately approved scenarios and record their own artifact
summaries.

## Evidence

The existing functional protocol and the 2F-A.1 external `cleanup.json` and
`artifact-summary.json` define the current evidence boundary.
