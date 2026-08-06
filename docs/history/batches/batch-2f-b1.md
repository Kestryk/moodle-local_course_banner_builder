# Batch 2F-B.1 — Contextual `h2` at genuine 200%

## Status

Validated.

## Summary

The scenario is present for the narrow, headed-browser 200% continuation of
the activity title contract. The real Moodle 5.1 run is now recorded as
passed, with the fixture and browser ownership fully released.

## Problem or motivation

Real browser zoom can reserve a different layout width from CSS viewport
emulation. The public title must remain readable, ordered, and non-duplicated.

## Scope

The single scenario named `CCB Batch 2F-B.1 exposes contextual h2 at narrow
genuine 200 percent zoom`, including cleanup and evidence ownership.

## Implementation

No production banner change is claimed. The QA harness now awaits Moodle form
navigation and writes the native Chrome `localhost` host-zoom preference in an
isolated external profile.

## Files and components

`tools/playwright/ccb-banner-public-title-accessibility-2fa.spec.js`

## Decisions

Do not substitute `deviceScaleFactor` or a 100% result for genuine 200% zoom.

## Validation

The exact single scenario passed on 2026-07-27 at genuine 200% native Chrome
zoom. The evidence proved a 2.00 width ratio and 2.00 device-pixel-ratio
ratio, one visible accessible `h1`, one distinct contextual `h2`, semantic
ordering and accessibility-tree expectations, no horizontal overflow, and
complete fixture/profile cleanup. The external artifact run is
`ccb-2fb1-supervised-20260727T143916775Z-9640` under the approved local
EasyEdu artifact root.

## Incidents and corrections

The first real attempts exposed two QA-harness defects: form saves were not
awaited before the next navigation, and the Chrome host-zoom preference used
an invalid host key. The helper now waits for the Moodle navigation and writes
Chrome's native `localhost` host entry. No production banner code was changed
for this correction.

## Dependencies

The passing run used human-approved headed Chrome and complete fixture cleanup.
Batch 2A/2B remain blocked until the controlled recovery commit is reviewed.

## Migration and recovery notes

Artifacts must remain outside Git and credentials must remain process-only.

## Release and commit references

Scenario definition present at `c5f33c8`; no release claim.

## Remaining work

Obtain the controlled recovery-commit review. R0.5B PHPUnit evidence is
recorded as validated in the isolated Moodle 5.1.3 environment. The next
implementation step is to harden the headed Chrome launcher so visual runs do
not depend on the user's desktop focus or an interactive profile window.

## Evidence

The external run contains `cleanup.json`, `artifact-summary.json`,
`contextual-h2-100.json`, `contextual-h2-narrow-200.json`, and the keyboard
evidence under the approved CCB artifact root.
