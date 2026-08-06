# Modal action-rail runner output-drain recovery

## Scope

Local QA-only correction to `Invoke-CCBLayerModalActionRailValidation.ps1`.
It drains redirected stdout and stderr with `ReadToEndAsync()` immediately
after Node starts, then awaits both tasks after process exit. Moodle product
code, leases, fixture cleanup, credentials and artifact paths are unchanged.

## Evidence

`Test-CCBAsyncProcessDrain.ps1 -BytesPerStream 1048576` passed on 2026-08-04,
reading 1 MiB from each stream without Moodle or Chromium.

## Remaining gate

Acquire the normal Moodle lease and run only the modal action-rail scenario.
