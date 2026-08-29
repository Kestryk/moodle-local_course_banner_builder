# EED-CCB-2026-0043-RF6 - Recrop-cancel history repair

Status: source candidate pending AMD build and one scoped validation.
Parent: RF5 candidate `daa0dcf`.

## Evidence

The single RF5 run reached and passed the first Crop and Cancel checks. It
preserved Crop `88%` and the image's outer placement. The subsequent Undo left
Crop at `88%` rather than restoring the initial `100%` state.

Entering Crop records the current state for Undo. A cancelled Recrop restores
that same state but previously retained its duplicate top history entry, so
the first Undo only removed the duplicate.

## Correction

After a modal Crop cancellation restores the current state, RF6 removes the
top undo entry only when it exactly equals that restored snapshot. Accepted
Crop history, Redo, outer placement and all non-Crop history are untouched.

## Required validation

Build AMD, run the focused source contracts, promote the exact candidate and
run the one Crop/Recrop scenario once. It must prove initial Crop, Cancel,
Undo, Redo and draft switching at 1440 and 760 px.

## Allowlist

- `amd/src/admin_manage.js`
- `amd/build/admin_manage.min.js`
- `amd/build/admin_manage.min.js.map`
- `tools/test-image-modal-transform-contract.ps1`
- `docs/history/batches/eed-ccb-2026-0043-rf6-recrop-cancel-history.md`
- `docs/history/batch-registry.md`
- `CHANGELOG.md`
