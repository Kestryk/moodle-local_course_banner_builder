# CCB Playwright handoff

This file is the single handoff contract for Codex windows working on Course
Banner Builder on Moodle 5.1.

## Recommended model

- **Luna XHigh**: supervised CCB QA, fixture-backed Playwright, and known
  multi-file UI work.
- **Terra Medium**: read-only diagnostics and documentation-only changes.
- **Sol Medium**: shared EasyEdu UI Kit or cross-plugin architecture work.

Choose the model for the next concrete operation before starting it. Do not
report the model used for an earlier diagnostic as the next recommendation.

## One-time credentials setup per Windows machine

The saved credential is scoped by Windows DPAPI to the current Windows user and
machine. It is intentionally not synchronized through Git, Syncthing, or user
environment variables. Run this once on each machine, from this directory:

```powershell
.\Configure-CCBMoodle51Credentials.ps1
```

If another window reports that `EASYEDU_MOODLE_*` variables are absent from
process, user, and machine scope, that is expected. It is checking the old
security contract. Do not fix it by creating global variables or by putting a
password in a command history.

## Commands for later windows

For the supervised 2F-B.1 test, use the fixture-aware runner:

```powershell
.\Invoke-CCB2FB1Supervised.ps1 -WatchdogSeconds 900
```

The same runner now supports the real 2F-A.1 test. It uses 100% native
browser zoom and the same disposable category/cleanup cycle:

```powershell
.\Invoke-CCB2FB1Supervised.ps1 -Batch 2FA1 -WatchdogSeconds 600
```

For discovery only, which does not log in or mutate Moodle:

```powershell
.\Invoke-CCB2FB1Supervised.ps1 -DiscoveryOnly
```

Use `-Batch 2FA1 -DiscoveryOnly` to verify the A.1 selection without logging
in or mutating Moodle.

For another Playwright spec, use the shared credential wrapper. Set only the
non-secret batch variables required by that spec in the current shell, then:

```powershell
.\Invoke-CCBPlaywrightWithSavedCredentials.ps1 `
    -Spec ccb-banner-admin-mobile-preview-2eb.spec.js `
    -PlaywrightArgument @('--reporter=line', '--workers=1')
```

The wrapper imports the DPAPI credential into its own process, lets the Node
child inherit it, and removes all credential variables before returning. Batch
fixture IDs remain explicit and temporary; they are never saved as machine
settings.

## Chrome profile rules

The specs must launch Chrome with an owned `--user-data-dir`, never the user's
normal Chrome profile. A normal Chrome session may remain open. Each run owns
its profile and must remove it during cleanup.

If Chrome reports a profile-loading error:

1. Do not delete the normal Chrome profile.
2. Check the run artifact's `playwright.stderr.txt`, `runner-result.json`, and
   `cleanup.json`.
3. Check free space on the drive hosting `%TEMP%`, `%LOCALAPPDATA%`, and the
   configured artifact root.
4. End only the test-owned Chrome process identified by the run artifact, then
   rerun the supervised command.

The known black-screen risk comes from foreground/native-zoom capture. Evidence
must stay in the dedicated profile and through CDP; do not add desktop capture
or reuse the interactive profile.

## Interpretation rules

- `-DiscoveryOnly` is not a real QA result; it only verifies test selection.
- A real result requires a supervised runner, a Playwright exit code of zero,
  and a complete fixture/profile cleanup report.
- Missing global credentials are not a blocker after the DPAPI setup exists.
- Missing fixture variables are a separate batch configuration issue and must be
  solved by that batch runner, not by persisting IDs globally.
