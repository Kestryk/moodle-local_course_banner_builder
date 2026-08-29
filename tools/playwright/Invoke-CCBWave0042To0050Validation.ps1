[CmdletBinding()]
param(
    [switch]$DiscoveryOnly,
    [ValidatePattern('^[0-9a-fA-F]{7,40}$')]
    [string]$ExpectedAppliedCommit,
    [ValidateRange(900, 900)]
    [int]$WatchdogSeconds = 900,
    [ValidateRange(0, 0)]
    [int]$WaitForLeaseSeconds = 0
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest
$scriptDir = Split-Path -Parent $PSCommandPath
$pluginRoot = (Resolve-Path -LiteralPath (Join-Path $scriptDir '..\..')).Path
$platformRoot = if ($env:EASYEDU_PLATFORM_ROOT) { [IO.Path]::GetFullPath($env:EASYEDU_PLATFORM_ROOT) } else { 'C:\dev\easyedu-platform' }
$orchestrationRoot = Join-Path $platformRoot 'tools\orchestration'
$orchestrationModule = Join-Path $orchestrationRoot 'EasyEduOrchestration.psm1'
$statusScript = Join-Path $orchestrationRoot 'Get-EasyEduRuntimePreviewStatus.ps1'
$artifactManifestScript = Join-Path $orchestrationRoot 'Register-EasyEduArtifactManifest.ps1'
$profileFile = Join-Path $env:LOCALAPPDATA 'EasyEdu\orchestration\profiles\runtime-preview-profiles.json'
$credentialLoader = Join-Path $scriptDir 'Use-CCBMoodle51Credentials.ps1'
$playwrightSpec = Join-Path $scriptDir 'ccb-wave-0042-0050-cumulative.spec.js'
$fixtureHelper = Join-Path $scriptDir 'ccb-wave-0042-0050-fixture.php'
$scenario = 'ccb-wave-0042-0050-cumulative'
$artifactBase = if ($env:EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT) { [IO.Path]::GetFullPath($env:EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT) } else { Join-Path $env:LOCALAPPDATA 'EasyEdu\artifacts' }
$pluginRootFull = [IO.Path]::GetFullPath($pluginRoot).TrimEnd('\', '/')
$artifactBase = [IO.Path]::GetFullPath($artifactBase).TrimEnd('\', '/')
if ($artifactBase.Equals($pluginRootFull, [StringComparison]::OrdinalIgnoreCase) -or
        $artifactBase.StartsWith($pluginRootFull + [IO.Path]::DirectorySeparatorChar, [StringComparison]::OrdinalIgnoreCase)) {
    throw 'Cumulative-wave artifacts must stay outside the CCB repository.'
}
$runId = 'ccb-wave-0042-0050-{0}-{1}' -f [DateTime]::UtcNow.ToString('yyyyMMddTHHmmssfffZ'), $PID
$runRoot = Join-Path $artifactBase ('ccb\wave-0042-0050\supervised\' + $runId)
$profileRoot = Join-Path $runRoot 'profile'
$manifestFile = Join-Path $runRoot 'fixture-manifest.json'; $cleanupFile = Join-Path $runRoot 'cleanup.json'
$resultFile = Join-Path $runRoot 'runner-result.json'; $phaseFile = Join-Path $runRoot 'phase-progress.jsonl'
$discoveryFile = Join-Path $runRoot 'playwright.discovery.txt'; $child = $null; $lease = $null; $fixture = $null
$childExitCode = $null; $cleanupResult = $null; $cleanupError = $null; $loadedEnvironment = @()
$environmentNames = @(
    'NODE_PATH', 'EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT', 'PLAYWRIGHT_PROFILE_DIR',
    'EASYEDU_PLAYWRIGHT_PROFILE_ROOT', 'EASYEDU_MOODLE_URL', 'EASYEDU_MOODLE_USERNAME',
    'EASYEDU_MOODLE_PASSWORD', 'CCB_MOODLE_URL', 'CCB_MOODLE_USERNAME', 'CCB_MOODLE_PASSWORD',
    'EASYEDU_CCB_WAVE_SOURCE_CATEGORY_ID', 'EASYEDU_CCB_WAVE_ARTIFACT_ROOT',
    'EASYEDU_CCB_WAVE_IMAGE_FIXTURE'
)
$savedEnvironment = @{}
foreach ($name in $environmentNames) {
    $savedEnvironment[$name] = [Environment]::GetEnvironmentVariable($name, 'Process')
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
    Add-Content -LiteralPath $phaseFile -Encoding UTF8 -Value (([ordered]@{ timestamp = [DateTime]::UtcNow.ToString('o'); phase = $Phase; status = $Status; message = Safe $Message } | ConvertTo-Json -Compress))
}
function Quote-Argument([string]$Value) { '"' + ($Value -replace '"', '\"') + '"' }
function Invoke-Fixture([string]$Command, [string]$Argument = '') {
    $args = @('-f', $fixtureHelper, '--', $Command, $profile.moodleRoot); if ($Argument) { $args += $Argument }
    $lines = @(& $profile.phpExecutable @args 2>&1); if ($LASTEXITCODE -ne 0) { throw (Safe ($lines -join "`n")) }
    $json = $lines | Where-Object { $_ -match '^\s*\{' } | Select-Object -Last 1
    if (!$json) { throw "Cumulative-wave fixture returned no JSON for $Command." }; $json | ConvertFrom-Json
}
function Start-Node([string[]]$Arguments) {
    $info = [Diagnostics.ProcessStartInfo]::new(); $info.FileName = 'node.exe'; $info.WorkingDirectory = $scriptDir
    $info.UseShellExecute = $false; $info.CreateNoWindow = $true; $info.RedirectStandardOutput = $true; $info.RedirectStandardError = $true
    $info.Arguments = (($Arguments | ForEach-Object { Quote-Argument $_ }) -join ' ')
    $process = [Diagnostics.Process]::new(); $process.StartInfo = $info; if (!$process.Start()) { throw 'Unable to start cumulative-wave Playwright process.' }
    $process | Add-Member StandardOutputTask $process.StandardOutput.ReadToEndAsync(); $process | Add-Member StandardErrorTask $process.StandardError.ReadToEndAsync(); $process
}
function Stop-Child { if ($child -and -not $child.HasExited) { try { & taskkill.exe /PID $child.Id /T /F 2>$null | Out-Null } catch {} } }
function Acquire-ValidationLease {
    Acquire-EasyEduResourceLease -Resource 'moodle51-active-fixture-write' -ProjectNamespace 'ccb' -RunId $runId -Repository $pluginRoot -Purpose 'CCB cumulative 0042-0050 disposable fixture' -LeaseSeconds 1020
}

try {
    New-Item -ItemType Directory -Path $runRoot -Force | Out-Null
    Set-Content -LiteralPath $phaseFile -Encoding UTF8 -Value ''
    Write-Phase 'preflight' 'started' 'Cumulative 0042-0050 supervisor initialised.'
    foreach ($required in @($orchestrationModule, $statusScript, $artifactManifestScript, $profileFile, $credentialLoader, $playwrightSpec, $fixtureHelper)) {
        if (-not (Test-Path -LiteralPath $required)) { throw "Required cumulative-wave file is missing: $required" }
    }
    $profiles = Get-Content -LiteralPath $profileFile -Raw | ConvertFrom-Json
    $profile = @($profiles.profiles | Where-Object { $_.name -eq 'ccb-moodle51' }) | Select-Object -First 1
    if (!$profile) { throw 'The ccb-moodle51 runtime profile is unavailable.' }
    $runtimePlaywrightRoot = Join-Path $profile.runtimeRepository 'tools\playwright'; $playwrightCli = Join-Path $runtimePlaywrightRoot 'node_modules\@playwright\test\cli.js'
    $runtimeConfig = Join-Path $runtimePlaywrightRoot 'playwright.config.js'; $imageFixture = Join-Path $profile.moodleRoot 'mod\workshop\tests\fixtures\moodlelogo.png'
    foreach ($required in @($playwrightCli, $runtimeConfig, $profile.phpExecutable, $imageFixture)) { if (-not (Test-Path -LiteralPath $required)) { throw "Required runtime validation file is missing: $required" } }
    $tempConfig = Join-Path $runRoot 'playwright.wave.config.js'
    Set-Content -LiteralPath $tempConfig -Encoding UTF8 -Value @"
const base = require($(ConvertTo-Json $runtimeConfig));
module.exports = {...base, testDir: $(ConvertTo-Json $scriptDir), outputDir: $(ConvertTo-Json (Join-Path $runRoot 'playwright-output')), workers: 1, retries: 0, reporter: 'line'};
"@
    $env:NODE_PATH = Join-Path $runtimePlaywrightRoot 'node_modules'; $loadedEnvironment += 'NODE_PATH'
    $env:EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT = $runRoot; $loadedEnvironment += 'EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT'
    Write-Phase 'playwright-discovery' 'started' 'Selecting exactly one test before credentials or fixture work.'
    $discovery = Safe ((@(& node.exe $playwrightCli test (Split-Path -Leaf $playwrightSpec) ('--config=' + $tempConfig) --list 2>&1)) -join "`n")
    Set-Content -LiteralPath $discoveryFile -Encoding UTF8 -Value $discovery
    if ($LASTEXITCODE -ne 0 -or $discovery -notmatch 'Total:\s+1\s+test\s+in\s+1\s+file') { throw 'Playwright discovery did not select exactly one cumulative-wave test.' }
    Write-Phase 'playwright-discovery' 'complete' 'Exactly one cumulative-wave test selected.'
    if ($DiscoveryOnly) { $childExitCode = 0; return }
    if (!$ExpectedAppliedCommit) { throw 'ExpectedAppliedCommit is required for a runtime run.' }
    if ($scenario -notin @($profile.allowedScenarios)) { throw "Scenario '$scenario' is not approved for profile ccb-moodle51." }
    $previewStatus = & $statusScript -ProfileName ccb-moodle51 | ConvertFrom-Json
    if (-not $previewStatus.managedPreview -or -not $previewStatus.currentlyPreviewed -or -not $previewStatus.runtimeClean) {
        throw 'The CCB runtime is not a clean managed preview.'
    }
    if ($ExpectedAppliedCommit -notin @($previewStatus.appliedCommits)) { throw "Expected commit $ExpectedAppliedCommit is not visible in appliedCommits." }
    Import-Module -Name $orchestrationModule -Force -DisableNameChecking
    $lease = Acquire-ValidationLease; Write-Phase 'lease-acquire' 'complete' 'Single CCB fixture lease acquired without retry.'
    $fixture = Invoke-Fixture 'setup'
    $manifest = [ordered]@{ runId = $runId; categoryid = [int]$fixture.categoryid; categoryids = @($fixture.categoryids); sourcekey = [string]$fixture.sourcekey; draftitemids = @($fixture.draftitemids) }
    Set-Content -LiteralPath $manifestFile -Encoding UTF8 -Value ($manifest | ConvertTo-Json -Depth 10); Write-Phase 'fixture-setup' 'complete' "Three-source fixture category $($fixture.categoryid) created."
    New-Item -ItemType Directory -Path $profileRoot -Force | Out-Null
    $env:PLAYWRIGHT_PROFILE_DIR = $profileRoot; $env:EASYEDU_PLAYWRIGHT_PROFILE_ROOT = $profileRoot
    $loadedEnvironment += @('PLAYWRIGHT_PROFILE_DIR', 'EASYEDU_PLAYWRIGHT_PROFILE_ROOT')
    $loadedEnvironment += @('EASYEDU_MOODLE_URL', 'EASYEDU_MOODLE_USERNAME', 'EASYEDU_MOODLE_PASSWORD', 'CCB_MOODLE_URL', 'CCB_MOODLE_USERNAME', 'CCB_MOODLE_PASSWORD')
    . $credentialLoader | Out-Null; $loadedEnvironment += @('EASYEDU_MOODLE_URL', 'EASYEDU_MOODLE_USERNAME', 'EASYEDU_MOODLE_PASSWORD', 'CCB_MOODLE_URL', 'CCB_MOODLE_USERNAME', 'CCB_MOODLE_PASSWORD')
    $env:EASYEDU_CCB_WAVE_SOURCE_CATEGORY_ID = [string]$fixture.categoryid; $env:EASYEDU_CCB_WAVE_ARTIFACT_ROOT = $runRoot; $env:EASYEDU_CCB_WAVE_IMAGE_FIXTURE = $imageFixture
    $loadedEnvironment += @('EASYEDU_CCB_WAVE_SOURCE_CATEGORY_ID', 'EASYEDU_CCB_WAVE_ARTIFACT_ROOT', 'EASYEDU_CCB_WAVE_IMAGE_FIXTURE')
    $child = Start-Node @($playwrightCli, 'test', (Split-Path -Leaf $playwrightSpec), ('--config=' + $tempConfig), '--workers=1', '--retries=0', '--reporter=line')
    $started = Get-Date; while (-not $child.HasExited) { if (((Get-Date) - $started).TotalSeconds -ge $WatchdogSeconds) { Stop-Child; $childExitCode = 124; break }; Start-Sleep -Seconds 1 }
    $child.WaitForExit(); $child.Refresh(); if ($null -eq $childExitCode) { $childExitCode = $child.ExitCode }
    Set-Content -LiteralPath (Join-Path $runRoot 'playwright.stdout.txt') -Encoding UTF8 -Value (Safe $child.StandardOutputTask.GetAwaiter().GetResult())
    Set-Content -LiteralPath (Join-Path $runRoot 'playwright.stderr.txt') -Encoding UTF8 -Value (Safe $child.StandardErrorTask.GetAwaiter().GetResult())
    Write-Phase 'playwright-child' 'complete' "Cumulative-wave exit code $childExitCode."; $child = $null
} catch { if ($null -eq $childExitCode) { $childExitCode = 70 }; Write-Phase 'supervisor' 'error' $_.Exception.Message
} finally {
    Stop-Child
    try { if ($fixture -and (Test-Path -LiteralPath $manifestFile)) { $cleanupResult = Invoke-Fixture 'cleanup' $manifestFile } } catch { $cleanupError = Safe $_.Exception.Message }
    try {
        if (Test-Path -LiteralPath $profileRoot) { Remove-Item -LiteralPath $profileRoot -Recurse -Force }
    } catch {
        $cleanupError = if ($cleanupError) { $cleanupError + ' | ' + (Safe $_.Exception.Message) } else { Safe $_.Exception.Message }
    }
    $profileRemoved = -not (Test-Path -LiteralPath $profileRoot)
    $environmentRestored = $true
    foreach ($name in $environmentNames) {
        try {
            if ($null -eq $savedEnvironment[$name]) { Remove-Item -LiteralPath ('Env:' + $name) -ErrorAction SilentlyContinue }
            else { Set-Item -Path ('Env:' + $name) -Value $savedEnvironment[$name] }
        } catch {
            $environmentRestored = $false
            $cleanupError = if ($cleanupError) { $cleanupError + ' | ' + (Safe $_.Exception.Message) } else { Safe $_.Exception.Message }
        }
    }
    if ($lease) {
        try { Release-EasyEduResourceLease -Resource 'moodle51-active-fixture-write' -RunId $runId -Force }
        catch { $cleanupError = if ($cleanupError) { $cleanupError + ' | ' + (Safe $_.Exception.Message) } else { Safe $_.Exception.Message } }
    }
    $fixtureCleanupComplete = ($null -eq $fixture) -or ($cleanupResult -and $cleanupResult.categoriesRemoved -and
        $cleanupResult.remainingCategories -eq 0 -and $cleanupResult.remainingElements -eq 0 -and $cleanupResult.draftsRemoved)
    $cleanup = [ordered]@{ complete = ($null -eq $cleanupError -and $profileRemoved -and $environmentRestored -and $fixtureCleanupComplete); categoriesRemoved = if ($cleanupResult) { $cleanupResult.categoriesRemoved } else { $null }; remainingCategories = if ($cleanupResult) { $cleanupResult.remainingCategories } else { $null }; remainingElements = if ($cleanupResult) { $cleanupResult.remainingElements } else { $null }; draftsRemoved = if ($cleanupResult) { $cleanupResult.draftsRemoved } else { $null }; profileRemoved = $profileRemoved; environmentRestored = $environmentRestored; cleanupError = $cleanupError; completedAt = [DateTime]::UtcNow.ToString('o') }
    Set-Content -LiteralPath $cleanupFile -Encoding UTF8 -Value ($cleanup | ConvertTo-Json -Depth 10)
    $status = if ($DiscoveryOnly -and $childExitCode -eq 0) { 'discovery-pass' } elseif ($childExitCode -eq 0 -and $cleanup.complete) { 'pass' } else { 'fail' }
    Set-Content -LiteralPath $resultFile -Encoding UTF8 -Value (([ordered]@{ runId = $runId; status = $status; childExitCode = $childExitCode; artifactDirectory = $runRoot; cleanup = $cleanup; environmentCleared = $environmentRestored } | ConvertTo-Json -Depth 10))
    try { & $artifactManifestScript -RunRoot $runRoot -ApprovedRoot $artifactBase -ProjectNamespace ccb -RunId $runId -Status $(if ($status -eq 'pass') {'passed'} else {'incomplete'}) -RetainFile @('ccb-wave-0042-0050-evidence.json', '01-0042-parent-list-before-sensitive.png', '02-0042-parent-modal-before-sensitive.png', '08-0050-source-tree-before-sensitive.png', '09-0050-preview-loading-before-sensitive.png', '10-0050-preview-ready-before-sensitive.png', '11-0050-preview-error-before-sensitive.png') | Out-Null } catch { Write-Phase 'artifact-manifest' 'error' $_.Exception.Message }
}
if ($childExitCode -ne 0) { exit 1 }; exit 0
