[CmdletBinding()]
param(
    [switch]$DiscoveryOnly,
    [ValidateSet(
        'fullwidthtopinset-desktop-100',
        'fullwidthtopinset-tablet-100',
        'fullwidthtopinset-mobile-320-100',
        'fullwidthtopinset-mobile-100',
        'fullwidthtopinset-desktop-200',
        'fullwidthtopinset-mobile-200'
    )]
    [string]$CellId,
    [ValidateRange(60, 1800)]
    [int]$WatchdogSeconds = 900
)

$ErrorActionPreference = 'Stop'
$scriptDir = Split-Path -Parent $PSCommandPath
$pluginRoot = (Resolve-Path -LiteralPath (Join-Path $scriptDir '..\..')).Path
$moodleRoot = (Resolve-Path -LiteralPath (Join-Path $scriptDir '..\..\..\..')).Path
$moodlePhp = Join-Path $moodleRoot '..\php\php.exe'
$playwrightCli = Join-Path $scriptDir 'node_modules\@playwright\test\cli.js'
$playwrightConfig = Join-Path $scriptDir 'playwright.config.js'
$playwrightSpec = Join-Path $scriptDir 'ccb-banner-source-preview-responsive.spec.js'
$fixtureHelper = Join-Path $scriptDir 'ccb-2a-fixture.php'
$credentialLoader = Join-Path $scriptDir 'Use-CCBMoodle51Credentials.ps1'
$orchestrationModule = 'C:\dev\easyedu-platform\tools\orchestration\EasyEduOrchestration.psm1'
$artifactManifestScript = Join-Path (Split-Path -Parent $orchestrationModule) 'Register-EasyEduArtifactManifest.ps1'
$artifactBase = if ($env:EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT) { $env:EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT } else {
    Join-Path $env:LOCALAPPDATA 'EasyEdu\artifacts'
}
$artifactBase = [IO.Path]::GetFullPath($artifactBase)
$runId = 'ccb-source-preview-responsive-{0}-{1}' -f [DateTime]::UtcNow.ToString('yyyyMMddTHHmmssfffZ'), $PID
$runRoot = Join-Path $artifactBase ('ccb\responsive\supervised\' + $runId)
$profileRoot = Join-Path $runRoot 'profiles'
$manifestFile = Join-Path $runRoot 'restoration-manifest.json'
$discoveryFile = Join-Path $runRoot 'playwright.discovery.txt'
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
$format = 'fullwidthtopinset'
$cells = @(
    [ordered]@{ id = 'fullwidthtopinset-desktop-100'; viewport = '1600x900'; zoom = 100 },
    [ordered]@{ id = 'fullwidthtopinset-tablet-100'; viewport = '1024x768'; zoom = 100 },
    [ordered]@{ id = 'fullwidthtopinset-mobile-320-100'; viewport = '320x844'; zoom = 100 },
    [ordered]@{ id = 'fullwidthtopinset-mobile-100'; viewport = '390x844'; zoom = 100 },
    [ordered]@{ id = 'fullwidthtopinset-desktop-200'; viewport = '1600x900'; zoom = 200 },
    [ordered]@{ id = 'fullwidthtopinset-mobile-200'; viewport = '390x844'; zoom = 200 }
)
if ($CellId) {
    $cells = @($cells | Where-Object { $_.id -eq $CellId })
    if ($cells.Count -ne 1) {
        throw "Unknown responsive matrix cell: $CellId"
    }
}

function Safe([string]$Value) {
    if ($null -eq $Value) { return '' }
    $safe = $Value -replace '(?i)([?&](?:sesskey|password|token|session(?:id)?|auth(?:orization)?)=)[^&#\s"'']*', '$1[redacted]'
    foreach ($name in @('EASYEDU_MOODLE_PASSWORD', 'CCB_MOODLE_PASSWORD')) {
        $secret = [Environment]::GetEnvironmentVariable($name)
        if ($secret) { $safe = $safe.Replace($secret, '[redacted]') }
    }
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
        -Purpose 'CCB source preview responsive audit fixture' -LeaseSeconds ([Math]::Max(120, $WatchdogSeconds + 120))
    $script:mutex = New-Object Threading.Mutex($false, 'Global\EasyEdu_CCB_Moodle51_Course11')
    try {
        if (-not $mutex.WaitOne(0)) { throw 'CCB course 11 mutex is already held.' }
        $script:mutexHeld = $true
    } catch [Threading.AbandonedMutexException] {
        $script:mutexHeld = $true
    }
    Write-Phase 'lease-acquire' 'complete' 'CCB resource lease and course 11 mutex acquired.'
}

function Remove-OwnedProfile([string]$ProfilePath) {
    if (-not (Test-Path -LiteralPath $ProfilePath)) { return $true }
    $resolved = [IO.Path]::GetFullPath($ProfilePath)
    $approved = [IO.Path]::GetFullPath($runRoot)
    if (-not $resolved.StartsWith($approved + [IO.Path]::DirectorySeparatorChar, [StringComparison]::OrdinalIgnoreCase)) {
        throw "Refusing to remove a profile outside this run root: $resolved"
    }
    Remove-Item -LiteralPath $resolved -Recurse -Force
    return -not (Test-Path -LiteralPath $resolved)
}

New-Item -ItemType Directory -Path $runRoot -Force | Out-Null
Set-Content -LiteralPath $phaseFile -Value '' -Encoding UTF8
Write-Phase 'preflight' 'started' 'CCB source preview responsive supervisor initialised.'

try {
    foreach ($required in @($playwrightCli, $playwrightConfig, $playwrightSpec, $fixtureHelper, $credentialLoader)) {
        if (-not (Test-Path -LiteralPath $required)) { throw "Required harness file is missing: $required" }
    }
    if ([IO.Path]::GetFullPath($runRoot).StartsWith([IO.Path]::GetFullPath($pluginRoot) + [IO.Path]::DirectorySeparatorChar, [StringComparison]::OrdinalIgnoreCase)) {
        throw 'Responsive artifact root resolves inside the CCB repository.'
    }
    $env:EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT = $runRoot; $loadedEnvironment += 'EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT'
    . $credentialLoader | Out-Null
    $loadedEnvironment += @('EASYEDU_MOODLE_URL', 'EASYEDU_MOODLE_USERNAME', 'EASYEDU_MOODLE_PASSWORD', 'CCB_MOODLE_URL', 'CCB_MOODLE_USERNAME', 'CCB_MOODLE_PASSWORD')
    $env:EASYEDU_CCB_RESPONSIVE_FIXTURE_COURSE_ID = '11'; $loadedEnvironment += 'EASYEDU_CCB_RESPONSIVE_FIXTURE_COURSE_ID'
    $env:EASYEDU_CCB_RESPONSIVE_SOURCE_CATEGORY_ID = '3'; $loadedEnvironment += 'EASYEDU_CCB_RESPONSIVE_SOURCE_CATEGORY_ID'
    $env:EASYEDU_CCB_RESPONSIVE_PROFILE = (Join-Path $profileRoot 'discovery'); $loadedEnvironment += 'EASYEDU_CCB_RESPONSIVE_PROFILE'
    $env:EASYEDU_CCB_RESPONSIVE_ARTIFACT_ROOT = $runRoot; $loadedEnvironment += 'EASYEDU_CCB_RESPONSIVE_ARTIFACT_ROOT'
    $env:EASYEDU_CCB_RESPONSIVE_VIEWPORT = '1600x900'; $loadedEnvironment += 'EASYEDU_CCB_RESPONSIVE_VIEWPORT'
    $env:EASYEDU_CCB_RESPONSIVE_ZOOM = '100'; $loadedEnvironment += 'EASYEDU_CCB_RESPONSIVE_ZOOM'
    $env:EASYEDU_CCB_RESPONSIVE_FORMAT = $format; $loadedEnvironment += 'EASYEDU_CCB_RESPONSIVE_FORMAT'
    Write-Phase 'playwright-discovery' 'started' 'Selecting exactly one responsive test before fixture work.'
    $discoveryArgs = @($playwrightCli, 'test', $playwrightSpec.Replace('\', '/'), ('--config=' + $playwrightConfig.Replace('\', '/')), '--list')
    $discoveryOutput = Safe ((@(& node.exe @discoveryArgs 2>&1)) -join "`n")
    Set-Content -LiteralPath $discoveryFile -Value $discoveryOutput -Encoding UTF8
    if ($LASTEXITCODE -ne 0 -or $discoveryOutput -notmatch 'Total:\s+1\s+test\s+in\s+1\s+file') {
        throw 'Playwright discovery did not select exactly one CCB responsive test.'
    }
    Write-Phase 'playwright-discovery' 'complete' 'Exactly one responsive test selected.'
    if ($DiscoveryOnly) { $childExitCode = 0; return }

    Write-Phase 'fixture-snapshot' 'started' 'Capturing course 11 restoration state.'
    $initial = Invoke-Helper 'snapshot'
    $manifest = [ordered]@{
        runId = $runId; course = $initial.course; stableCategoryId = $initial.stableCategoryId
        activityCmid = $initial.activityCmid; courseTitle = $initial.courseTitle; activityTitle = $initial.activityTitle
        coursebanneractivitiesenabled = $initial.coursebanneractivitiesenabled; coursebannerenabled = $initial.coursebannerenabled
        coursebannerformat = $initial.coursebannerformat; stableSourceSettings = $initial.stableSourceSettings
        stableSourceElementCount = $initial.stableSourceElementCount; formatUnderTest = $format
    }
    Set-Content -LiteralPath $manifestFile -Value ($manifest | ConvertTo-Json -Depth 30) -Encoding UTF8
    Acquire-CcbLease
    $temporary = Invoke-Helper 'setup'
    $manifest.temporary = $temporary
    Set-Content -LiteralPath $manifestFile -Value ($manifest | ConvertTo-Json -Depth 30) -Encoding UTF8
    $env:EASYEDU_CCB_RESPONSIVE_SOURCE_CATEGORY_ID = [string]$temporary.categoryid
    $formatResult = Invoke-Helper 'set-format' $format
    if ([string]$formatResult.coursebannerformat -ne $format) { throw 'Responsive format mutation did not persist.' }
    Write-Phase 'fixture-setup' 'complete' "Temporary source category $($temporary.categoryid) created for course 11."

    foreach ($cell in $cells) {
        $cellRoot = Join-Path $runRoot ('cells\' + $cell.id)
        New-Item -ItemType Directory -Path $cellRoot -Force | Out-Null
        $cellProfile = Join-Path $cellRoot 'profile'
        $env:EASYEDU_CCB_RESPONSIVE_PROFILE = $cellProfile
        $env:EASYEDU_CCB_RESPONSIVE_ARTIFACT_ROOT = $cellRoot
        $env:EASYEDU_CCB_RESPONSIVE_VIEWPORT = $cell.viewport
        $env:EASYEDU_CCB_RESPONSIVE_ZOOM = [string]$cell.zoom
        $env:EASYEDU_CCB_RESPONSIVE_PORT = [string](9580 + ($cellResults.Count % 80))
        Write-Phase 'playwright-child' 'started' "Running CCB responsive cell $($cell.id)."
        $childTimedOut = $false
        $child = Start-Node @($playwrightCli, 'test', $playwrightSpec.Replace('\', '/'), ('--config=' + $playwrightConfig.Replace('\', '/')), '--reporter=line', '--workers=1', '--retries=0')
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
        $cellResults += [ordered]@{ id = $cell.id; viewport = $cell.viewport; zoom = $cell.zoom; format = $format; exitCode = $cellExitCode; timedOut = $childTimedOut; artifactDirectory = $cellRoot }
        Write-Phase 'playwright-child' 'complete' "CCB responsive cell $($cell.id) exit code $cellExitCode."
        $child = $null
        if ($cellExitCode -ne 0 -and $null -eq $childExitCode) { $childExitCode = $cellExitCode }
    }
    if ($null -eq $childExitCode) { $childExitCode = 0 }
    Set-Content -LiteralPath (Join-Path $runRoot 'cell-results.json') -Value ($cellResults | ConvertTo-Json -Depth 20) -Encoding UTF8
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
    $profilesRemoved = $true
    foreach ($cell in $cells) {
        try {
            $profilesRemoved = (Remove-OwnedProfile (Join-Path $runRoot ('cells\' + $cell.id + '\profile'))) -and $profilesRemoved
        } catch {
            $profilesRemoved = $false
            if (-not $cleanupError) { $cleanupError = Safe $_.Exception.Message }
        }
    }
    try {
        $profilesRemoved = (Remove-OwnedProfile (Join-Path $profileRoot 'discovery')) -and $profilesRemoved
    } catch {
        $profilesRemoved = $false
        if (-not $cleanupError) { $cleanupError = Safe $_.Exception.Message }
    }
    $cleanup = [ordered]@{
        complete = ($null -eq $cleanupError -and (($null -eq $manifest) -or ($null -ne $cleanupResult -and $cleanupResult.courseBannerFormatRestored)) -and $profilesRemoved)
        courseRestored = if ($cleanupResult) { $cleanupResult.courseRestored } else { $null }
        categoryRestored = if ($cleanupResult) { $cleanupResult.courseCategory -eq 3 } else { $null }
        temporaryCategoryRemoved = if ($cleanupResult) { $cleanupResult.temporaryCategoryRemoved } else { $null }
        courseBannerFormatRestored = if ($cleanupResult) { $cleanupResult.courseBannerFormatRestored } else { $null }
        profilesRemoved = $profilesRemoved; cleanupError = $cleanupError; completedAt = [DateTime]::UtcNow.ToString('o')
    }
    Set-Content -LiteralPath $cleanupFile -Value ($cleanup | ConvertTo-Json -Depth 30) -Encoding UTF8
    $status = if ($DiscoveryOnly -and $childExitCode -eq 0) { 'discovery-pass' } elseif ($childExitCode -eq 0 -and $cleanup.complete) { 'pass' } else { 'fail' }
    $result = [ordered]@{ runId = $runId; status = $status; childExitCode = $childExitCode; cellCount = $cellResults.Count; cells = $cellResults; artifactDirectory = $runRoot; environmentCleared = $true }
    Set-Content -LiteralPath $runnerResultFile -Value ($result | ConvertTo-Json -Depth 30) -Encoding UTF8
    Set-Content -LiteralPath $summaryFile -Value (([ordered]@{ runId = $runId; status = $status; cleanupComplete = $cleanup.complete; cells = $cellResults; artifactDirectory = $runRoot }) | ConvertTo-Json -Depth 30) -Encoding UTF8
    if (Test-Path -LiteralPath $artifactManifestScript -PathType Leaf) {
        $retentionStatus = if ($status -eq 'pass') { 'passed' } elseif ($DiscoveryOnly) { 'incomplete' } else { 'failed' }
        try { & $artifactManifestScript -RunRoot $runRoot -ApprovedRoot $artifactBase -ProjectNamespace 'ccb' -RunId $runId -Status $retentionStatus | Out-Null } catch { Write-Phase 'artifact-manifest' 'error' (Safe $_.Exception.Message) }
    }
    foreach ($name in ($loadedEnvironment | Select-Object -Unique)) { Remove-Item -LiteralPath ("Env:" + $name) -ErrorAction SilentlyContinue }
    if ($lease) { try { Release-EasyEduResourceLease -Resource 'moodle51-active-fixture-write' -RunId $runId -Force } catch { } }
    if ($mutexHeld -and $mutex) { try { $mutex.ReleaseMutex() } catch { }; $mutex.Dispose() }
}

if ($childExitCode -ne 0 -or -not (Test-Path -LiteralPath $cleanupFile)) { exit 1 }
exit 0
