# EED-CCB-2026-0043-QA1 — Crop/Recrop evidence harness

Status: source-ready; static validation complete, browser validation not run.
Parent: `EED-CCB-2026-0043` / product candidate `74f10ab`.
Scope: one supervised Playwright scenario only; no product, Kit, profile,
runtime, cache, browser or preview activity is part of this source lot.

## Purpose

The reported defect is a visual geometry regression in the add-image modal:
changing Crop or Recrop must never shrink or reposition the image itself. This
QA lot supplies deterministic evidence before any further product change.

## Scenario

`tools/playwright/ccb-crop-recrop.spec.js` owns one test:
`EED-CCB-2026-0043-QA1 Crop and Recrop preserve image placement across widths`.
It creates two image drafts, captures before interaction, then checks at 1440
and 760 px: initial Crop, Cancelled Recrop, Undo/Redo and an in-progress crop
committed by switching draft/image. Crop payload may change; current/visual
placement payloads and measured geometry may not shrink or move.

## Supervision and cleanup

`Invoke-CCBCropRecropValidation.ps1` proves that exactly one test is selected
before credentials or fixture work. Only then may an authorised run check the
managed preview, take the fixture lease, create the disposable category and
load credentials. It reuses the established owned-category/draft cleanup
fixture, records cleanup, clears process variables, stops its child and
releases the lease. One worker and zero retries are compulsory.

## Required Platform allowlist

- `tools/playwright/ccb-crop-recrop.spec.js`
- `tools/playwright/Invoke-CCBCropRecropValidation.ps1`
- `tools/test-ccb-crop-recrop-contract.ps1`
- `docs/history/batches/eed-ccb-2026-0043-qa1-crop-recrop.md`
- `docs/testing/ccb-ui-harmonisation.md`
- `docs/history/easyedu-active-lot-registry.md`
- `CHANGELOG.md`

No product or AMD path is eligible for promotion from this QA-only lot.
