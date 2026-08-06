[CmdletBinding()]
param(
    [switch]$DiscoveryOnly,
    [ValidateRange(60, 1800)]
    [int]$WatchdogSeconds = 900
)

$ErrorActionPreference = 'Stop'
$scriptDir = Split-Path -Parent $PSCommandPath
$pluginRoot = (Resolve-Path (Join-Path $scriptDir '..\..')).Path
$moodleRoot = (Resolve-Path (Join-Path $scriptDir '..\..\..\..')).Path
$moodlePhp = Join-Path $moodleRoot '..\php\php.exe'
$playwrightCli = Join-Path $scriptDir 'node_modules\@playwright\test\cli.js'
$playwrightConfig = Join-Path $scriptDir 'playwright.config.js'
$playwrightSpec = Join-Path $scriptDir 'ccb-banner-batch-2a-geometry.spec.js'
$fixtureHelper = Join-Path $scriptDir 'ccb-2a-fixture.php'
$credentialLoader = Join-Path $scriptDir 'Use-CCBMoodle51Credentials.ps1'
$orchestrationModule = 'C:\dev\easyedu-platform\tools\orchestration\EasyEduOrchestration.psm1'
$artifactManifestScript = Join-Path (Split-Path -Parent $orchestrationModule) 'Register-EasyEduArtifactManifest.ps1'
$artifactBase = if ($env:EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT) { $env:EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT } else {
    Join-Path $env:LOCALAPPDATA 'EasyEdu\artifacts'
}
$artifactBase = [IO.Path]::GetFullPath($artifactBase)
$runId = 'ccb-2a1-supervised-{0}-{1}' -f [DateTime]::UtcNow.ToString('yyyyMMddTHHmmssfffZ'), $PID
$runRoot = Join-Path $artifactBase ('ccb\batch-2a\supervised\' + $runId)
$profile = Join-Path $runRoot 'profiles'
$manifestFile = Join-Path $runRoot 'restoration-manifest.json'
$cleanupFile = Join-Path $runRoot 'cleanup.json'
$runnerResultFile = Join-Path $runRoot 'runner-result.json'
$summaryFile = Join-Path $runRoot 'artifact-summary.json'
$phaseFile = Join-Path $runRoot 'phase-progress.jsonl'
$child = $null
$childExitCode = $null
$childTimedOut = $false
$temporary = $null
$manifest = $null
$cleanupResult = $null
$cleanupError = $null
$lease = $null
$mutex = $null
$mutexHeld = $false
$loadedEnvironment = @()
$cellResults = @()
$cells = @(
    [ordered]@{ id = 'standard-desktop-100'; format = 'standard'; viewport = '1600x900'; zoom = 100 },
    [ordered]@{ id = 'contentwide-tablet-100'; format = 'contentwide'; viewport = '1024x768'; zoom = 100 },
    [ordered]@{ id = 'fullwidthtop-portrait-100'; format = 'fullwidthtop'; viewport = '768x1024'; zoom = 100 },
    [ordered]@{ id = 'fullwidthtopcompact-mobile-100'; format = 'fullwidthtopcompact'; viewport = '390x844'; zoom = 100 },
    [ordered]@{ id = 'fullwidthtopinset-desktop-200'; format = 'fullwidthtopinset'; viewport = '1600x900'; zoom = 200 },
    [ordered]@{ id = 'fullwidthtopinset-mobile-200'; format = 'fullwidthtopinset'; viewport = '390x844'; zoom = 200 }
)
$grep = 'CCB Batch 2A\.1 measures preview/public geometry across approved format cells'

function Safe([string]$Value) {
    if ($null -eq $Value) { return '' }
    $safe = $Value -replace '(?i)([?&](?:sesskey|password|token|session(?:id)?|auth(?:orization)?)=)[^&#\s"'']*', '$1[redacted]'
    if ($env:EASYEDU_MOODLE_PASSWORD) { $safe = $safe.Replace($env:EASYEDU_MOODLE_PASSWORD, '[redacted]') }
    return $safe
}

function Write-Phase([string]$Phase, [string]$Status, [string]$Message = '') {
    Add-Content -LiteralPath $phaseFile -Value (($([ordered]@{
        timestamp = [DateTime]::UtcNow.ToString('o'); phase = $Phase; status = $Status; message = Safe $Message
    }) | ConvertTo-Json -Compress)) -Encoding UTF8
}

function Invoke-Helper([string]$Command, [string]$Argument = '') {
    if (-not (Test-Path -LiteralPath $moodlePhp)) { throw "Moodle PHP is unavailable: $moodlePhp" }
    $args = @('-f', $fixtureHelper, '--', $Command)
    if ($Argument) { $args += $Argument }
    $lines = @(& $moodlePhp @args 2>&1)
    if ($LASTEXITCODE -ne 0) { throw (Safe ($lines -join "`n")) }
    $json = $lines | Where-Object { $_ -match '^\s*\{' } | Select-Object -Last 1
    if (-not $json) { throw "Fixture helper returned no JSON for $Command." }
    return ($json | ConvertFrom-Json)
}

function Quote-Argument([string]$Value) { return '"' + ($Value -replace '"', '\\"') + '"' }

function Start-Node([string[]]$Arguments) {
    $info = [Diagnostics.ProcessStartInfo]::new()
    $info.FileName = 'node.exe'; $info.WorkingDirectory = $scriptDir
    $info.UseShellExecute = $false; $info.CreateNoWindow = $true
    $info.RedirectStandardOutput = $true; $info.RedirectStandardError = $true
    $info.StandardOutputEncoding = [Text.Encoding]::UTF8; $info.StandardErrorEncoding = [Text.Encoding]::UTF8
    $info.Arguments = (($Arguments | ForEach-Object { Quote-Argument $_ }) -join ' ')
    $process = [Diagnostics.Process]::new(); $process.StartInfo = $info
    if (-not $process.Start()) { throw 'Unable to start Playwright Node process.' }
    return $process
}

function Stop-Child {
    if ($child -and -not $child.HasExited) {
        try { & taskkill.exe /PID $child.Id /T /F 2>$null | Out-Null } catch { }
    }
}

function Acquire-CcbLease {
    Import-Module -Name $orchestrationModule -Force
    $script:lease = Acquire-EasyEduResourceLease -Resource 'moodle51-active-fixture-write' `
        -ProjectNamespace 'ccb' -RunId $runId -Repository $pluginRoot `
        -Purpose 'CCB Batch 2A.1 course 11 geometry fixture' -LeaseSeconds ([Math]::Max(120, $WatchdogSeconds + 120))
    $script:mutex = New-Object Threading.Mutex($false, 'Global\EasyEdu_CCB_Moodle51_Course11')
    try {
        if (-not $mutex.WaitOne(0)) { throw 'CCB course 11 mutex is already held.' }
        $script:mutexHeld = $true
    } catch [Threading.AbandonedMutexException] {
        $script:mutexHeld = $true
    }
    Write-Phase 'lease-acquire' 'complete' 'CCB resource lease and course 11 mutex acquired.'
}

function Remove-Profile {
    if (-not (Test-Path -LiteralPath $profile)) { return $true }
    $resolved = [IO.Path]::GetFullPath($profile)
    $approved = [IO.Path]::GetFullPath($runRoot)
    if (-not $resolved.StartsWith($approved + [IO.Path]::DirectorySeparatorChar, [StringComparison]::OrdinalIgnoreCase)) {
        throw 'Refusing to remove a profile outside this run artifact root.'
    }
    Remove-Item -LiteralPath $resolved -Recurse -Force
    return -not (Test-Path -LiteralPath $resolved)
}

New-Item -ItemType Directory -Path $runRoot -Force | Out-Null
Set-Content -LiteralPath $phaseFile -Value '' -Encoding UTF8
Write-Phase 'preflight' 'started' 'Batch 2A supervisor initialised.'

try {
    foreach ($required in @($playwrightCli, $playwrightConfig, $playwrightSpec, $fixtureHelper, $credentialLoader)) {
        if (-not (Test-Path -LiteralPath $required)) { throw "Required harness file is missing: $required" }
    }
    $env:EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT = $runRoot; $loadedEnvironment += 'EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT'
    Write-Phase 'playwright-discovery' 'started' 'Selecting exactly one Batch 2A test before fixture work.'
    $discoveryArgs = @($playwrightCli, 'test', $playwrightSpec.Replace('\', '/'), ('--config=' + $playwrightConfig.Replace('\', '/')), ('--grep=' + $grep), '--list')
    $discoveryOutput = Safe ((@(& node.exe @discoveryArgs 2>&1)) -join "`n")
    Set-Content -LiteralPath (Join-Path $runRoot 'playwright.discovery.txt') -Value $discoveryOutput -Encoding UTF8
    if ($LASTEXITCODE -ne 0 -or $discoveryOutput -notmatch 'Total:\s+1\s+test\s+in\s+1\s+file') {
        throw 'Playwright discovery did not select exactly one Batch 2A test.'
    }
    Write-Phase 'playwright-discovery' 'complete' 'Exactly one Batch 2A test selected.'
    if ($DiscoveryOnly) { $childExitCode = 0; return }

    Write-Phase 'fixture-snapshot' 'started' 'Capturing course 11 restoration state.'
    $initial = $null
    for ($attempt = 1; $attempt -le 3 -and $null -eq $initial; $attempt++) {
        try {
            $initial = Invoke-Helper 'snapshot'
        } catch {
            if ($attempt -eq 3) { throw }
            Start-Sleep -Seconds 2
        }
    }
    $manifest = [ordered]@{
        runId = $runId; course = $initial.course; stableCategoryId = $initial.stableCategoryId
        activityCmid = $initial.activityCmid; courseTitle = $initial.courseTitle; activityTitle = $initial.activityTitle
        coursebanneractivitiesenabled = $initial.coursebanneractivitiesenabled; coursebannerenabled = $initial.coursebannerenabled
        coursebannerformat = $initial.coursebannerformat
        stableSourceSettings = $initial.stableSourceSettings; stableSourceElementCount = $initial.stableSourceElementCount
    }
    Set-Content -LiteralPath $manifestFile -Value ($manifest | ConvertTo-Json -Depth 30) -Encoding UTF8
    # Snapshot is read-only; acquire the exclusive lease before the first
    # fixture mutation (temporary category creation).
    Acquire-CcbLease
    $temporary = Invoke-Helper 'setup'
    $manifest.temporary = $temporary
    Set-Content -LiteralPath $manifestFile -Value ($manifest | ConvertTo-Json -Depth 30) -Encoding UTF8
    Write-Phase 'fixture-setup' 'complete' "Temporary source category $($temporary.categoryid) created for course 11."

    . $credentialLoader | Out-Null
    $loadedEnvironment += @('EASYEDU_MOODLE_URL', 'EASYEDU_MOODLE_USERNAME', 'EASYEDU_MOODLE_PASSWORD', 'CCB_MOODLE_URL', 'CCB_MOODLE_USERNAME', 'CCB_MOODLE_PASSWORD')
    $env:EASYEDU_CCB_FIXTURE_COURSE_ID = '11'; $loadedEnvironment += 'EASYEDU_CCB_FIXTURE_COURSE_ID'
    $env:EASYEDU_CCB_2A_SOURCE_CATEGORY_ID = [string]$temporary.categoryid; $loadedEnvironment += 'EASYEDU_CCB_2A_SOURCE_CATEGORY_ID'
    $env:EASYEDU_CCB_2A_PROFILE = $profile; $loadedEnvironment += 'EASYEDU_CCB_2A_PROFILE'
    foreach ($cell in $cells) {
        $cellRoot = Join-Path $runRoot ('cells\' + $cell.id)
        $cellProfile = Join-Path $profile $cell.id
        New-Item -ItemType Directory -Path $cellRoot -Force | Out-Null
        $env:EASYEDU_CCB_2A_SCENARIO_ID = $cell.id; $loadedEnvironment += 'EASYEDU_CCB_2A_SCENARIO_ID'
        $env:EASYEDU_CCB_2A_FORMAT = $cell.format; $loadedEnvironment += 'EASYEDU_CCB_2A_FORMAT'
        $env:EASYEDU_CCB_2A_VIEWPORT = $cell.viewport; $loadedEnvironment += 'EASYEDU_CCB_2A_VIEWPORT'
        $env:EASYEDU_CCB_2A_ZOOM = [string]$cell.zoom; $loadedEnvironment += 'EASYEDU_CCB_2A_ZOOM'
        $env:EASYEDU_CCB_2A_PROFILE = $cellProfile
        $formatResult = Invoke-Helper 'set-format' $cell.format
        if ([string]$formatResult.coursebannerformat -ne $cell.format) {
            throw "CCB format mutation did not persist for $($cell.id)."
        }
        $childTimedOut = $false
        Write-Phase 'playwright-child' 'started' "Running Batch 2A geometry cell $($cell.id)."
        $child = Start-Node @($playwrightCli, 'test', $playwrightSpec.Replace('\', '/'), ('--config=' + $playwrightConfig.Replace('\', '/')), ('--grep=' + $grep), '--reporter=line', '--workers=1', '--retries=0')
        $started = Get-Date
        while (-not $child.HasExited) {
            if (((Get-Date) - $started).TotalSeconds -ge $WatchdogSeconds) {
                $childTimedOut = $true; Stop-Child; break
            }
            Start-Sleep -Seconds 1
        }
        $child.WaitForExit(); $child.Refresh()
        $cellExitCode = if ($childTimedOut) { 124 } else { $child.ExitCode }
        $stdout = Safe $child.StandardOutput.ReadToEnd()
        $stderr = Safe $child.StandardError.ReadToEnd()
        Set-Content -LiteralPath (Join-Path $cellRoot 'playwright.stdout.txt') -Value $stdout -Encoding UTF8
        Set-Content -LiteralPath (Join-Path $cellRoot 'playwright.stderr.txt') -Value $stderr -Encoding UTF8
        $cellResults += [ordered]@{
            id = $cell.id; format = $cell.format; viewport = $cell.viewport; zoom = $cell.zoom
            exitCode = $cellExitCode; timedOut = $childTimedOut; artifactDirectory = $cellRoot
        }
        Write-Phase 'playwright-child' 'complete' "Batch 2A cell $($cell.id) exit code $cellExitCode."
        $child = $null
        if ($cellExitCode -ne 0 -and $null -eq $childExitCode) { $childExitCode = $cellExitCode }
    }
    if ($null -eq $childExitCode) { $childExitCode = 0 }
    Set-Content -LiteralPath (Join-Path $runRoot 'cell-results.json') -Value ($cellResults | ConvertTo-Json -Depth 20) -Encoding UTF8
    Write-Phase 'playwright-child' 'complete' "Batch 2A geometry matrix completed with $($cellResults.Count) cells."
}
catch {
    if ($null -eq $childExitCode) { $childExitCode = 70 }
    Write-Phase 'supervisor' 'error' $_.Exception.Message
}
finally {
    Stop-Child
    try {
        if ($manifestFile -and (Test-Path -LiteralPath $manifestFile)) {
            $manifestOnDisk = Get-Content -LiteralPath $manifestFile -Raw | ConvertFrom-Json
            if ($manifestOnDisk.temporary -and $manifestOnDisk.temporary.categoryid) {
                $cleanupResult = Invoke-Helper 'cleanup' $manifestFile
            }
        }
    } catch { $cleanupError = Safe $_.Exception.Message }
    try { $profileRemoved = Remove-Profile } catch { $profileRemoved = $false; if (-not $cleanupError) { $cleanupError = Safe $_.Exception.Message } }
    $cleanup = [ordered]@{
        complete = ($null -eq $cleanupError -and (($null -eq $manifest) -or ($null -ne $cleanupResult -and $cleanupResult.courseBannerFormatRestored)) -and $profileRemoved)
        courseRestored = if ($cleanupResult) { $cleanupResult.courseRestored } else { $null }
        categoryRestored = if ($cleanupResult) { $cleanupResult.courseCategory -eq 3 } else { $null }
        temporaryCategoryRemoved = if ($cleanupResult) { $cleanupResult.temporaryCategoryRemoved } else { $null }
        courseBannerFormatRestored = if ($cleanupResult) { $cleanupResult.courseBannerFormatRestored } else { $null }
        profileRemoved = $profileRemoved; cleanupError = $cleanupError
        completedAt = [DateTime]::UtcNow.ToString('o')
    }
    Set-Content -LiteralPath $cleanupFile -Value ($cleanup | ConvertTo-Json -Depth 30) -Encoding UTF8
    $status = if ($DiscoveryOnly -and $childExitCode -eq 0) { 'discovery-pass' } elseif ($childExitCode -eq 0 -and $cleanup.complete) { 'pass' } else { 'fail' }
    $result = [ordered]@{runId = $runId; status = $status; childExitCode = $childExitCode; cellCount = $cellResults.Count; cells = $cellResults; artifactDirectory = $runRoot; environmentCleared = $true}
    Set-Content -LiteralPath $runnerResultFile -Value ($result | ConvertTo-Json -Depth 30) -Encoding UTF8
    $summary = [ordered]@{runId = $runId; status = $status; cleanupComplete = $cleanup.complete; cellCount = $cellResults.Count; cells = $cellResults; artifactDirectory = $runRoot; repositoryLocalArtifacts = @()}
    Set-Content -LiteralPath $summaryFile -Value ($summary | ConvertTo-Json -Depth 30) -Encoding UTF8
    if (Test-Path -LiteralPath $artifactManifestScript -PathType Leaf) {
        $retentionStatus = if ($status -eq 'pass') { 'passed' } elseif ($DiscoveryOnly) { 'incomplete' } else { 'failed' }
        try {
            & $artifactManifestScript -RunRoot $runRoot -ApprovedRoot $artifactBase `
                -ProjectNamespace 'ccb' -RunId $runId -Status $retentionStatus | Out-Null
        } catch {
            Write-Phase 'artifact-manifest' 'error' (Safe $_.Exception.Message)
        }
    }
    foreach ($name in ($loadedEnvironment | Select-Object -Unique)) { Remove-Item -LiteralPath ("Env:" + $name) -ErrorAction SilentlyContinue }
    if ($lease) { try { Release-EasyEduResourceLease -Resource 'moodle51-active-fixture-write' -RunId $runId -Force } catch { } }
    if ($mutexHeld -and $mutex) { try { $mutex.ReleaseMutex() } catch { }; $mutex.Dispose() }
}

if ($childExitCode -ne 0 -or -not (Test-Path -LiteralPath $cleanupFile)) { exit 1 }
exit 0
