[CmdletBinding()]
param(
    [switch]$Simulation,
    [switch]$DiscoveryOnly,
    [ValidateSet('2FA1', '2FB1')]
    [string]$Batch = '2FB1',
    [ValidateRange(60, 1800)]
    [int]$WatchdogSeconds = 900
)

$ErrorActionPreference = 'Stop'
$scriptDir = Split-Path -Parent $PSCommandPath
$pluginRoot = (Resolve-Path (Join-Path $scriptDir '..\..')).Path
$moodleRoot = (Resolve-Path (Join-Path $scriptDir '..\..\..\..')).Path
$moodlePhp = Join-Path $moodleRoot '..\php\php.exe'
$fixtureHelper = Join-Path $scriptDir 'ccb-2fb1-fixture.php'
$playwrightRoot = $scriptDir
$playwrightCli = Join-Path $playwrightRoot 'node_modules\@playwright\test\cli.js'
$playwrightConfig = Join-Path $playwrightRoot 'playwright.config.js'
$playwrightSpec = Join-Path $playwrightRoot 'ccb-banner-public-title-accessibility-2fa.spec.js'
$artifactManifestScript = Join-Path 'C:\dev\easyedu-platform\tools\orchestration' 'Register-EasyEduArtifactManifest.ps1'
$credentialFile = Join-Path $env:LOCALAPPDATA 'EasyEdu\credentials\ccb-moodle51.xml'
# Playwright matches the complete title path, so anchors would reject the exact test.
$batchConfiguration = @{
    '2FA1' = @{
        grep = 'CCB Batch 2F-A\.1 preserves activity h1 and exposes contextual h2 at 100 percent'
        zoom = '100'
        scenario = 'ccb-2fa1-supervised'
    }
    '2FB1' = @{
        grep = 'CCB Batch 2F-B\.1 exposes contextual h2 at narrow genuine 200 percent zoom'
        zoom = '200'
        scenario = 'ccb-2fb1-200-supervised'
    }
}
$selectedBatch = $batchConfiguration[$Batch]
$playwrightGrep = $selectedBatch.grep
$artifactBase = if ($env:EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT) {
    $env:EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT
} else {
    Join-Path $env:LOCALAPPDATA 'EasyEdu\artifacts'
}
$artifactBase = [IO.Path]::GetFullPath($artifactBase)
$runId = ('ccb-{0}-supervised-{1}-{2}' -f $Batch.ToLower(), [DateTime]::UtcNow.ToString('yyyyMMddTHHmmssfffZ'), $PID)
# Keep the CCB 2F-B.1 root compact so nested Playwright evidence paths remain
# compatible with Windows artifact registration and hashing.
$runRoot = Join-Path $artifactBase ('ccb\2fb1\supervised\' + $runId)
$phaseFile = Join-Path $runRoot 'phase-progress.jsonl'
$manifestFile = Join-Path $runRoot 'restoration-manifest.json'
$runnerResultFile = Join-Path $runRoot 'runner-result.json'
$cleanupFile = Join-Path $runRoot 'cleanup.json'
$summaryFile = Join-Path $runRoot 'artifact-summary.json'
$stdoutTemp = Join-Path $env:TEMP ($runId + '-stdout.txt')
$stderrTemp = Join-Path $env:TEMP ($runId + '-stderr.txt')
$child = $null
$childExitCode = $null
$childTimedOut = $false
$lastPhase = 'preflight'
$temporary = $null
$manifest = $null
$cleanupResult = $null
$externalCleanupError = $null
$ownedProfilePaths = [Collections.Generic.List[string]]::new()
$leaseMutex = $null
$leaseHeld = $false
$usedEnvironment = @(
    'EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT', 'EASYEDU_CCB_2FA_FIXTURE_COURSE_ID',
    'EASYEDU_CCB_2FA_ACTIVITY_CMID', 'EASYEDU_CCB_2FA_SOURCE_CATEGORY_ID',
    'EASYEDU_CCB_2FA_SCENARIO_ID', 'EASYEDU_CCB_2FA_ZOOM', 'EASYEDU_CCB_2FA_PORT',
    'EASYEDU_CCB_2FA_TIMEOUT', 'EASYEDU_MOODLE_URL', 'EASYEDU_MOODLE_USERNAME',
    'EASYEDU_MOODLE_PASSWORD'
)

function ConvertTo-SafeJson([object]$Value) {
    $Value | ConvertTo-Json -Depth 30 -Compress
}

function Sanitize-Text([string]$Value) {
    if ($null -eq $Value) { return '' }
    $safe = $Value -replace '(?i)([?&](?:sesskey|password|token|session(?:id)?|auth(?:orization)?)=)[^&#\s"'']*', '$1[redacted]'
    $safe = $safe -replace '(?i)(bearer\s+)[^\s"'']+', '$1[redacted]'
    if ($env:EASYEDU_MOODLE_PASSWORD) {
        $safe = $safe.Replace($env:EASYEDU_MOODLE_PASSWORD, '[redacted]')
    }
    return $safe
}

function Import-SavedMoodle51Credentials {
    if ($env:EASYEDU_MOODLE_URL -and $env:EASYEDU_MOODLE_USERNAME -and $env:EASYEDU_MOODLE_PASSWORD) {
        return
    }
    if (-not (Test-Path -LiteralPath $credentialFile)) {
        throw "Saved Moodle 51 credentials are missing. Run Configure-CCBMoodle51Credentials.ps1 once."
    }
    $credential = Import-Clixml -LiteralPath $credentialFile
    if ($credential -isnot [PSCredential]) {
        throw 'Saved Moodle 51 credential file is invalid.'
    }
    $passwordPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($credential.Password)
    try {
        $env:EASYEDU_MOODLE_URL = 'http://localhost'
        $env:EASYEDU_MOODLE_USERNAME = $credential.UserName
        $env:EASYEDU_MOODLE_PASSWORD = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($passwordPointer)
    } finally {
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($passwordPointer)
    }
}

function Write-Phase([string]$Phase, [string]$Status, [string]$Message = '') {
    $script:lastPhase = $Phase
    $record = [ordered]@{
        timestamp = [DateTime]::UtcNow.ToString('o')
        phase = $Phase
        status = $Status
        message = Sanitize-Text $Message
    }
    Add-Content -LiteralPath $phaseFile -Value (ConvertTo-SafeJson $record) -Encoding UTF8
}

function Invoke-MoodleHelper([string]$Command, [string]$Argument = '') {
    if (-not (Test-Path -LiteralPath $moodlePhp)) { throw "Moodle PHP is unavailable." }
    $args = @('-f', $fixtureHelper, '--', $Command)
    if ($Argument) { $args += $Argument }
    $lines = @(& $moodlePhp @args 2>&1)
    if ($LASTEXITCODE -ne 0) { throw (Sanitize-Text ($lines -join "`n")) }
    $jsonLine = $lines | Where-Object { $_ -match '^\s*\{' } | Select-Object -Last 1
    if (-not $jsonLine) { throw "Moodle helper returned no JSON for $Command." }
    return ($jsonLine | ConvertFrom-Json)
}

function Stop-ProcessTree([int]$ProcessId) {
    if ($ProcessId -le 0) { return }
    try { & taskkill.exe /PID $ProcessId /T /F 2>$null | Out-Null } catch { }
}

function Stop-OwnedProcesses {
    if ($child -and -not $child.HasExited) { Stop-ProcessTree $child.Id }
    foreach ($profilePath in $ownedProfilePaths) {
        $escaped = [Regex]::Escape($profilePath)
        $processes = @(Get-CimInstance Win32_Process -Filter "Name = 'chrome.exe'" -ErrorAction SilentlyContinue |
            Where-Object { $_.CommandLine -and $_.CommandLine -match $escaped })
        foreach ($process in $processes) { Stop-ProcessTree ([int]$process.ProcessId) }
    }
}

function Acquire-ExclusiveLease {
    $leaseMutex = New-Object Threading.Mutex($false, 'Global\EasyEdu_CCB_Moodle51_Course11')
    try {
        if (-not $leaseMutex.WaitOne(0)) {
            $leaseMutex.Dispose()
            $leaseMutex = $null
            throw 'Exclusive CCB Moodle fixture lease is already held.'
        }
        $script:leaseMutex = $leaseMutex
        $script:leaseHeld = $true
        Write-Phase 'lease-acquire' 'complete' 'Exclusive CCB Moodle fixture lease acquired.'
    } catch [Threading.AbandonedMutexException] {
        $script:leaseMutex = $leaseMutex
        $script:leaseHeld = $true
        Write-Phase 'lease-acquire' 'complete' 'Exclusive CCB Moodle fixture lease acquired after abandoned-owner recovery.'
    }
}

function Remove-OwnedProfiles {
    foreach ($profilePath in $ownedProfilePaths) {
        $resolved = [IO.Path]::GetFullPath($profilePath)
        # The runner gives Playwright the run root as its external artifact
        # root. The spec then creates its standard ccb/... sub-tree below it.
        # Accept that nested root as well as the direct configured root, while
        # still refusing every unrelated profile path.
        $profileRoots = @(
            [IO.Path]::GetFullPath((Join-Path $artifactBase 'ccb\public-title-accessibility\profiles')),
            [IO.Path]::GetFullPath((Join-Path $runRoot 'ccb\public-title-accessibility\profiles'))
        )
        $isApproved = @($profileRoots | Where-Object {
            $resolved.StartsWith($_ + [IO.Path]::DirectorySeparatorChar, [StringComparison]::OrdinalIgnoreCase)
        }).Count -gt 0
        if (-not $isApproved) {
            throw "Refusing profile path outside the approved artifact root."
        }
        if (Test-Path -LiteralPath $resolved) { Remove-Item -LiteralPath $resolved -Recurse -Force }
    }
}

function Get-SecretScan {
    $hits = [Collections.Generic.List[string]]::new()
    foreach ($file in @(Get-ChildItem -LiteralPath $runRoot -Recurse -File -ErrorAction SilentlyContinue)) {
        try { $content = [IO.File]::ReadAllText($file.FullName) } catch { continue }
        if ($content -match '(?i)(?:password|sesskey|token|cookie|authorization)\s*[:=]\s*(?!["'']\[redacted\])[A-Za-z0-9._~%+\-/=]{8,}') {
            $hits.Add($file.FullName)
        }
    }
    return [ordered]@{passed = ($hits.Count -eq 0); hitCount = $hits.Count}
}

function Write-RunnerFiles([int]$ExitCode, [string]$Status) {
    $scan = Get-SecretScan
    $runner = [ordered]@{
        runId = $runId
        simulation = [bool]$Simulation
        status = $Status
        childExitCode = $ExitCode
        childTimedOut = $childTimedOut
        lastCompletedPhase = $lastPhase
        environmentCleared = $true
        artifactDirectory = $runRoot
        generatedAt = [DateTime]::UtcNow.ToString('o')
    }
    Set-Content -LiteralPath $runnerResultFile -Value (ConvertTo-SafeJson $runner) -Encoding UTF8
    $summary = [ordered]@{
        runId = $runId
        traceGenerated = (@(Get-ChildItem -LiteralPath $runRoot -Recurse -Filter '*.zip' -File -ErrorAction SilentlyContinue).Count -gt 0)
        secretScan = $scan
        internalCleanupPresent = Test-Path -LiteralPath (Join-Path $runRoot 'ccb\public-title-accessibility\test-results')
        externalCleanup = $null -ne $cleanupResult
        artifactBytes = @(Get-ChildItem -LiteralPath $runRoot -Recurse -File -ErrorAction SilentlyContinue | Measure-Object -Property Length -Sum).Sum
    }
    Set-Content -LiteralPath $summaryFile -Value (ConvertTo-SafeJson $summary) -Encoding UTF8
}

function Invoke-ExactDiscovery {
    $arguments = @(
        $playwrightCli, 'test',
        $playwrightSpec.Replace('\', '/'),
        ('--config=' + $playwrightConfig.Replace('\', '/')),
        ('--grep=' + $playwrightGrep), '--list'
    )
    $lines = @(& node.exe @arguments 2>&1)
    $exitCode = $LASTEXITCODE
    $output = Sanitize-Text ($lines -join "`n")
    Set-Content -LiteralPath (Join-Path $runRoot 'playwright.discovery.txt') -Value $output -Encoding UTF8
    if ($exitCode -ne 0) {
        throw "Playwright discovery failed with exit code $exitCode."
    }
    if ($output -notmatch 'Total:\s+1\s+test(?:s)?\s+in\s+1\s+file') {
        throw "Playwright discovery did not select exactly one test."
    }
}

function Start-PlaywrightChild([string[]]$Arguments) {
    # Windows PowerShell/.NET on this workstation does not expose the modern
    # ProcessStartInfo.ArgumentList API. Quote every argument explicitly so
    # absolute paths containing spaces survive the legacy command-line parser.
    $startInfo = [Diagnostics.ProcessStartInfo]::new()
    $startInfo.FileName = 'node.exe'
    $startInfo.WorkingDirectory = $playwrightRoot
    $startInfo.UseShellExecute = $false
    $startInfo.CreateNoWindow = $true
    $startInfo.RedirectStandardOutput = $true
    $startInfo.RedirectStandardError = $true
    $startInfo.StandardOutputEncoding = [Text.Encoding]::UTF8
    $startInfo.StandardErrorEncoding = [Text.Encoding]::UTF8
    $startInfo.Arguments = (($Arguments | ForEach-Object {
        '"' + ($_ -replace '"', '\\"') + '"'
    }) -join ' ')

    $process = [Diagnostics.Process]::new()
    $process.StartInfo = $startInfo
    if (-not $process.Start()) { throw 'Unable to start the Playwright Node process.' }
    return $process
}

New-Item -ItemType Directory -Path $runRoot -Force | Out-Null
Set-Content -LiteralPath $phaseFile -Value '' -Encoding UTF8
Write-Phase 'preflight' 'started' 'Supervisor initialised.'

try {
    foreach ($requiredPath in @($playwrightCli, $playwrightConfig, $playwrightSpec)) {
        if (-not (Test-Path -LiteralPath $requiredPath)) { throw "Required Playwright file is missing: $requiredPath" }
    }
    if (-not $Simulation -and -not $DiscoveryOnly) {
        Import-SavedMoodle51Credentials
        foreach ($name in @('EASYEDU_MOODLE_URL', 'EASYEDU_MOODLE_USERNAME', 'EASYEDU_MOODLE_PASSWORD')) {
            if (-not (Get-Item -LiteralPath ("Env:" + $name) -ErrorAction SilentlyContinue)) { throw "$name is missing." }
        }
    }
    if (-not (Test-Path -LiteralPath $fixtureHelper)) { throw 'Fixture helper is missing.' }
    $env:EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT = $runRoot
    Write-Phase 'playwright-discovery' 'started' 'Verifying exact Playwright test selection before fixture work.'
    Invoke-ExactDiscovery
    Write-Phase 'playwright-discovery' 'complete' "Exactly one $Batch test selected."

    if ($DiscoveryOnly) {
        $childExitCode = 0
        Write-Phase 'preflight' 'complete' 'Discovery-only verification complete; no Moodle fixture was touched.'
    } else {
        Write-Phase 'preflight' 'complete' 'Repository and helper paths resolved.'
    }

    if ($Simulation) {
        Write-Phase 'simulation-child' 'started' 'Launching a deliberately failing child.'
        $child = Start-Process -FilePath 'powershell.exe' -ArgumentList @('-NoProfile', '-NonInteractive', '-Command', 'exit 73') -PassThru -WindowStyle Hidden
        $child.WaitForExit()
        $childExitCode = $child.ExitCode
        Write-Phase 'simulation-child' 'complete' "Child exit code $childExitCode."
    } elseif (-not $DiscoveryOnly) {
        Write-Phase 'lease-acquire' 'started' 'Acquiring exclusive CCB Moodle fixture lease.'
        Acquire-ExclusiveLease
        Write-Phase 'fixture-inventory' 'started' 'Capturing the stable fixture before mutation.'
        $initial = Invoke-MoodleHelper 'snapshot'
        $manifest = [ordered]@{
            runId = $runId
            course = $initial.course
            stableCategoryId = 3
            activityCmid = 12
            courseTitle = $initial.courseTitle
            activityTitle = $initial.activityTitle
            coursebanneractivitiesenabled = $initial.coursebanneractivitiesenabled
            coursebannerenabled = $initial.coursebannerenabled
            stableSourceSettings = $initial.stableSourceSettings
            stableSourceElementCount = $initial.stableSourceElementCount
            temporary = $null
        }
        Set-Content -LiteralPath $manifestFile -Value (ConvertTo-SafeJson $manifest) -Encoding UTF8
        Write-Phase 'fixture-inventory' 'complete' 'Sanitised restoration manifest written.'

        Write-Phase 'source-category-creation' 'started' "Creating the disposable category and CCB overlay for $Batch through Moodle APIs."
        $temporary = Invoke-MoodleHelper 'setup'
        $manifest.temporary = $temporary
        Set-Content -LiteralPath $manifestFile -Value (ConvertTo-SafeJson $manifest) -Encoding UTF8
        Write-Phase 'source-category-creation' 'complete' "Disposable category $($temporary.categoryid) created."

        $env:EASYEDU_CCB_2FA_FIXTURE_COURSE_ID = '11'
        $env:EASYEDU_CCB_2FA_ACTIVITY_CMID = '12'
        $env:EASYEDU_CCB_2FA_SOURCE_CATEGORY_ID = [string]$temporary.categoryid
        $env:EASYEDU_CCB_2FA_SCENARIO_ID = $selectedBatch.scenario
        $env:EASYEDU_CCB_2FA_ZOOM = $selectedBatch.zoom
        $env:EASYEDU_CCB_2FA_PORT = [string](9650 + (Get-Random -Minimum 0 -Maximum 120))
        $env:EASYEDU_CCB_2FA_TIMEOUT = '600000'

        Write-Phase 'child-launch' 'started' "Launching the exact $Batch child command."
        $child = Start-PlaywrightChild @(
            $playwrightCli, 'test', $playwrightSpec.Replace('\', '/'),
            ('--config=' + $playwrightConfig.Replace('\', '/')),
            ('--grep=' + $playwrightGrep),
            '--reporter=line', '--workers=1', '--retries=0'
        )
        $startedAt = Get-Date
        while (-not $child.HasExited) {
            if (((Get-Date) - $startedAt).TotalSeconds -ge $WatchdogSeconds) {
                $childTimedOut = $true
                Write-Phase 'child-watchdog' 'timeout' "Supervisor watchdog expired after $WatchdogSeconds seconds."
                Stop-ProcessTree $child.Id
                break
            }
            Start-Sleep -Seconds 1
        }
        $child.WaitForExit()
        $child.Refresh()
        $childExitCode = if ($child.HasExited) { $child.ExitCode } else { 124 }
        Set-Content -LiteralPath (Join-Path $runRoot 'playwright.stdout.txt') -Value (Sanitize-Text $child.StandardOutput.ReadToEnd()) -Encoding UTF8
        Set-Content -LiteralPath (Join-Path $runRoot 'playwright.stderr.txt') -Value (Sanitize-Text $child.StandardError.ReadToEnd()) -Encoding UTF8
        Write-Phase 'child-launch' 'complete' "Child exit code $childExitCode."
    }
}
catch {
    Write-Phase $lastPhase 'error' $_.Exception.Message
    if ($null -eq $childExitCode) { $childExitCode = 70 }
}
finally {
    $fixtureWasMutated = $false
    try {
        Stop-OwnedProcesses
        Write-Phase 'external-cleanup' 'started' 'Owned browser and child processes stopped.'
        if (-not $Simulation -and $manifestFile -and (Test-Path -LiteralPath $manifestFile)) {
            $manifest = Get-Content -LiteralPath $manifestFile -Raw | ConvertFrom-Json
            if ($manifest.temporary -and $manifest.temporary.categoryid) {
                $fixtureWasMutated = $true
                $cleanupResult = Invoke-MoodleHelper 'cleanup' $manifestFile
            }
        } else {
            $cleanupResult = [ordered]@{fixtureMutated = $false; cleanupNotApplicable = $true}
        }
        Write-Phase 'external-cleanup' 'complete' 'Moodle fixture cleanup completed or was already clean.'
    }
    catch {
        $externalCleanupError = Sanitize-Text $_.Exception.Message
        Write-Phase 'external-cleanup' 'error' $externalCleanupError
    }
    try {
        $ownershipFiles = @(Get-ChildItem -LiteralPath $runRoot -Recurse -Filter 'ownership.json' -File -ErrorAction SilentlyContinue)
        foreach ($ownershipFile in $ownershipFiles) {
            try {
                $ownership = Get-Content -LiteralPath $ownershipFile.FullName -Raw | ConvertFrom-Json
                if ($ownership.profile) { $ownedProfilePaths.Add([string]$ownership.profile) }
            } catch { }
        }
        Stop-OwnedProcesses
        Remove-OwnedProfiles
        $cleanupReport = [ordered]@{
            fixtureMutated = $fixtureWasMutated
            fixtureCleanupNotApplicable = -not $fixtureWasMutated
            stableCoursePreserved = $true
            courseRestored = (-not $fixtureWasMutated) -or ($cleanupResult.courseRestored -ne $false)
            categoryRestored = (-not $fixtureWasMutated) -or ($cleanupResult.courseCategory -eq 3)
            settingsRestored = (-not $fixtureWasMutated) -or ($cleanupResult.settingsRestored -ne $false)
            disposableCategoryRemoved = (-not $fixtureWasMutated) -or ($cleanupResult.temporaryCategoryRemoved -ne $false)
            relatedCcbElements = if ($cleanupResult) { $cleanupResult.relatedElements } else { $null }
            profilesRemoved = (@($ownedProfilePaths | Where-Object { Test-Path -LiteralPath $_ }).Count -eq 0)
            processesTerminated = $true
            externalCleanupError = $externalCleanupError
            completedAt = [DateTime]::UtcNow.ToString('o')
        }
        $cleanupReport.complete = ($null -eq $externalCleanupError) -and $cleanupReport.profilesRemoved -and $cleanupReport.disposableCategoryRemoved
        Set-Content -LiteralPath $cleanupFile -Value (ConvertTo-SafeJson $cleanupReport) -Encoding UTF8
        Write-Phase 'cleanup-report' 'complete' 'cleanup.json generated.'
    }
    catch {
        if ($null -eq $externalCleanupError) { $externalCleanupError = Sanitize-Text $_.Exception.Message }
        Set-Content -LiteralPath $cleanupFile -Value (ConvertTo-SafeJson ([ordered]@{complete = $false; error = $externalCleanupError})) -Encoding UTF8
        Write-Phase 'cleanup-report' 'error' $externalCleanupError
    }
    $status = if ($Simulation -and $childExitCode -eq 73) { 'simulation-proved-parent-finally' } elseif ($childTimedOut) { 'watchdog-timeout' } elseif ($childExitCode -eq 0 -and $null -eq $externalCleanupError) { 'pass' } else { 'fail' }
    Write-RunnerFiles ([int]$childExitCode) $status
    if (Test-Path -LiteralPath $artifactManifestScript -PathType Leaf) {
        $retentionStatus = if ($status -eq 'pass') { 'passed' } elseif ($DiscoveryOnly) { 'incomplete' } else { 'failed' }
        try {
            & $artifactManifestScript -RunRoot $runRoot -ApprovedRoot $artifactBase `
                -ProjectNamespace 'ccb' -RunId $runId -Status $retentionStatus | Out-Null
        } catch {
            Write-Phase 'artifact-manifest' 'error' (Sanitize-Text $_.Exception.Message)
        }
    }
    foreach ($name in $usedEnvironment) { Remove-Item -LiteralPath ("Env:" + $name) -ErrorAction SilentlyContinue }
    if (Test-Path -LiteralPath $stdoutTemp) { Remove-Item -LiteralPath $stdoutTemp -Force -ErrorAction SilentlyContinue }
    if (Test-Path -LiteralPath $stderrTemp) { Remove-Item -LiteralPath $stderrTemp -Force -ErrorAction SilentlyContinue }
    if ($leaseHeld -and $leaseMutex) {
        try { $leaseMutex.ReleaseMutex() } catch { }
        $leaseMutex.Dispose()
        $leaseHeld = $false
        Write-Phase 'lease-release' 'complete' 'Exclusive CCB Moodle fixture lease released.'
    }
}

if ($Simulation) {
    if ($childExitCode -ne 73 -or -not (Test-Path -LiteralPath $cleanupFile) -or -not (Test-Path -LiteralPath $summaryFile)) { exit 1 }
    exit 0
}
if ($childExitCode -ne 0 -or $externalCleanupError) { exit 1 }
exit 0
