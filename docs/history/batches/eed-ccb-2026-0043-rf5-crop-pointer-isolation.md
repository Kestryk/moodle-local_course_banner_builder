# EED-CCB-2026-0043-RF5 - Crop pointer-completion isolation

Status: source candidate pending AMD build and focused validation.
Parent: `37cc288` / RF4, already applied to the managed CCB preview before RF5.

## Evidence

QA7 recorded a real native gesture on the Crop southeast handle. The Crop
state changed through `100%` to `88%` during `pointermove`, but a later global
pointer-completion route restored `100%`. Calling the exported Crop handlers
directly retained `88%`. The Crop handler and geometry are therefore sound;
the completion ordering was not.

## Correction

While a Crop interaction is active, `pointerup` and `pointercancel` are
handled in document capture phase. The narrow handler prevents unrelated
general preview completion listeners from committing stale state, then closes
only the Crop interaction. Outside active Crop it does nothing.

No Kit primitive, markup, field mapping, outer image placement or Crop math is
modified.

## Required validation

1. Build the AMD artifact from `amd/src/admin_manage.js`.
2. Run the focused static contracts and AMD syntax checks.
3. Promote only the resulting exact candidate, then run the single
   `EED-CCB-2026-0043-QA1` Crop/Recrop scenario once under its lease.

## Allowlist

- `amd/src/admin_manage.js`
- `amd/build/admin_manage.min.js`
- `amd/build/admin_manage.min.js.map`
- `tools/test-image-modal-transform-contract.ps1`
- `docs/history/batches/eed-ccb-2026-0043-rf5-crop-pointer-isolation.md`
- `docs/history/batch-registry.md`
- `CHANGELOG.md`
