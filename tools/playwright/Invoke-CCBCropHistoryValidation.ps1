[CmdletBinding()]
param(
    [switch]$DiscoveryOnly,
    [string]$ExpectedAppliedCommit,
    [ValidateRange(180, 900)]
    [int]$WatchdogSeconds = 360,
    [ValidateRange(0, 900)]
    [int]$WaitForLeaseSeconds = 300
)

$ErrorActionPreference = 'Stop'
$scriptDir = Split-Path -Parent $PSCommandPath
$pluginRoot = (Resolve-Path -LiteralPath (Join-Path $scriptDir '..\..')).Path
$workspaceRoot = Split-Path -Parent (Split-Path -Parent $pluginRoot)
$platformRoot = if ($env:EASYEDU_PLATFORM_ROOT) { [IO.Path]::GetFullPath($env:EASYEDU_PLATFORM_ROOT) } else { Join-Path $workspaceRoot 'easyedu-platform' }
$orchestrationRoot = Join-Path $platformRoot 'tools\orchestration'
$orchestrationModule = Join-Path $orchestrationRoot 'EasyEduOrchestration.psm1'
$statusScript = Join-Path $orchestrationRoot 'Get-EasyEduRuntimePreviewStatus.ps1'
$artifactManifestScript = Join-Path $orchestrationRoot 'Register-EasyEduArtifactManifest.ps1'
$profileFile = Join-Path $env:LOCALAPPDATA 'EasyEdu\orchestration\profiles\runtime-preview-profiles.json'
$credentialLoader = Join-Path $scriptDir 'Use-CCBMoodle51Credentials.ps1'
$playwrightSpec = Join-Path $scriptDir 'ccb-crop-recrop-history.spec.js'
$fixtureHelper = Join-Path $scriptDir 'ccb-crop-history-fixture.php'
$artifactBase = if ($env:EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT) { [IO.Path]::GetFullPath($env:EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT) } else { Join-Path $env:LOCALAPPDATA 'EasyEdu\artifacts' }
$runId = 'ccb-crop-history-{0}-{1}' -f [DateTime]::UtcNow.ToString('yyyyMMddTHHmmssfffZ'), $PID
$runRoot = Join-Path $artifactBase ('ccb\crop-history\supervised\' + $runId)
$manifestFile = Join-Path $runRoot 'fixture-manifest.json'
$cleanupFile = Join-Path $runRoot 'cleanup.json'
$resultFile = Join-Path $runRoot 'runner-result.json'
$phaseFile = Join-Path $runRoot 'phase-progress.jsonl'
$discoveryFile = Join-Path $runRoot 'playwright.discovery.txt'
$child = $null; $childExitCode = $null; $lease = $null; $fixture = $null; $cleanupResult = $null; $cleanupError = $null
$loadedEnvironment = @()

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
    Add-Content -LiteralPath $phaseFile -Value (([ordered]@{ timestamp = [DateTime]::UtcNow.ToString('o'); phase = $Phase; status = $Status; message = Safe $Message } | ConvertTo-Json -Compress)) -Encoding UTF8
}
function Quote-Argument([string]$Value) { return '"' + ($Value -replace '"', '\"') + '"' }
function Start-Node([string[]]$Arguments, [string]$WorkingDirectory) {
    $info = [Diagnostics.ProcessStartInfo]::new(); $info.FileName = 'node.exe'; $info.WorkingDirectory = $WorkingDirectory
    $info.UseShellExecute = $false; $info.CreateNoWindow = $true; $info.RedirectStandardOutput = $true; $info.RedirectStandardError = $true
    $info.StandardOutputEncoding = [Text.Encoding]::UTF8; $info.StandardErrorEncoding = [Text.Encoding]::UTF8
    $info.Arguments = (($Arguments | ForEach-Object { Quote-Argument $_ }) -join ' ')
    $process = [Diagnostics.Process]::new(); $process.StartInfo = $info
    if (-not $process.Start()) { throw 'Unable to start CROP-08 Playwright process.' }
    $process | Add-Member -NotePropertyName StandardOutputTask -NotePropertyValue $process.StandardOutput.ReadToEndAsync()
    $process | Add-Member -NotePropertyName StandardErrorTask -NotePropertyValue $process.StandardError.ReadToEndAsync()
    return $process
}
function Stop-Child { if ($child -and -not $child.HasExited) { try { & taskkill.exe /PID $child.Id /T /F 2>$null | Out-Null } catch {} } }
function Invoke-Fixture([string]$Command, [string]$Manifest = '') {
    $arguments = @('-f', $fixtureHelper, '--', $Command, $profile.moodleRoot)
    if ($Manifest) { $arguments += $Manifest }
    $lines = @(& $profile.phpExecutable @arguments 2>&1)
    if ($LASTEXITCODE -ne 0) { throw (Safe ($lines -join "`n")) }
    $json = $lines | Where-Object { $_ -match '^\s*\{' } | Select-Object -Last 1
    if (-not $json) { throw "CROP-08 fixture returned no JSON for $Command." }
    return ($json | ConvertFrom-Json)
}
function Acquire-ValidationLease {
    $deadline = (Get-Date).AddSeconds($WaitForLeaseSeconds)
    do {
        try {
            return Acquire-EasyEduResourceLease -Resource 'moodle51-active-fixture-write' -ProjectNamespace 'ccb' -RunId $runId -Repository $pluginRoot -Purpose 'CCB CROP-08 transformation-history validation' -LeaseSeconds ([Math]::Max(240, $WatchdogSeconds + 120))
        } catch {
            if ($WaitForLeaseSeconds -le 0 -or $_.Exception.Message -notmatch 'already leased' -or (Get-Date) -ge $deadline) { throw }
            Write-Phase 'lease-wait' 'waiting' $_.Exception.Message; Start-Sleep -Seconds 3
        }
    } while ((Get-Date) -lt $deadline)
    throw 'Timed out waiting for the shared Moodle fixture lease.'
}

New-Item -ItemType Directory -Path $runRoot -Force | Out-Null
Set-Content -LiteralPath $phaseFile -Value '' -Encoding UTF8
Write-Phase 'preflight' 'started' 'CROP-08 supervisor initialised.'
try {
    foreach ($required in @($orchestrationModule, $statusScript, $artifactManifestScript, $profileFile, $credentialLoader, $playwrightSpec, $fixtureHelper)) {
        if (-not (Test-Path -LiteralPath $required)) { throw "Required CROP-08 file is missing: $required" }
    }
    $profiles = Get-Content -LiteralPath $profileFile -Raw | ConvertFrom-Json
    $profile = @($profiles.profiles | Where-Object { $_.name -eq 'ccb-moodle51' }) | Select-Object -First 1
    if (-not $profile) { throw 'The ccb-moodle51 runtime profile is unavailable.' }
    $runtimePlaywrightRoot = Join-Path $profile.runtimeRepository 'tools\playwright'
    $playwrightCli = Join-Path $runtimePlaywrightRoot 'node_modules\@playwright\test\cli.js'
    $runtimeConfig = Join-Path $runtimePlaywrightRoot 'playwright.config.js'
    foreach ($required in @($playwrightCli, $runtimeConfig, $profile.phpExecutable)) { if (-not (Test-Path -LiteralPath $required)) { throw "Required runtime validation file is missing: $required" } }
    $tempConfig = Join-Path $runRoot 'playwright.crop08.config.js'
    Set-Content -LiteralPath $tempConfig -Encoding UTF8 -Value @"
const base = require($(ConvertTo-Json $runtimeConfig));
module.exports = {...base, testDir: $(ConvertTo-Json $scriptDir), outputDir: $(ConvertTo-Json (Join-Path $runRoot 'playwright-output')), workers: 1, retries: 0, reporter: 'line'};
"@
    $env:NODE_PATH = Join-Path $runtimePlaywrightRoot 'node_modules'; $loadedEnvironment += 'NODE_PATH'
    $env:EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT = $runRoot; $loadedEnvironment += 'EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT'
    Write-Phase 'playwright-discovery' 'started' 'Selecting exactly one CROP-08 test before credentials or fixture work.'
    $discoveryOutput = Safe ((@(& node.exe $playwrightCli test (Split-Path -Leaf $playwrightSpec) ('--config=' + $tempConfig) --list 2>&1)) -join "`n")
    Set-Content -LiteralPath $discoveryFile -Value $discoveryOutput -Encoding UTF8
    if ($LASTEXITCODE -ne 0 -or $discoveryOutput -notmatch 'Total:\s+1\s+test\s+in\s+1\s+file') { throw 'Playwright discovery did not select exactly one CROP-08 test.' }
    Write-Phase 'playwright-discovery' 'complete' 'Exactly one CROP-08 test selected.'
    if ($DiscoveryOnly) { $childExitCode = 0; return }
    $previewStatus = & $statusScript -ProfileName ccb-moodle51 | ConvertFrom-Json
    if (-not $previewStatus.managedPreview -or -not $previewStatus.runtimeClean) { throw 'The CCB runtime is not a clean managed preview.' }
    if ($ExpectedAppliedCommit -and $ExpectedAppliedCommit -notin @($previewStatus.appliedCommits)) { throw "Expected commit $ExpectedAppliedCommit is not visible in appliedCommits." }
    Import-Module -Name $orchestrationModule -Force -DisableNameChecking
    $lease = Acquire-ValidationLease; Write-Phase 'lease-acquire' 'complete' 'CROP-08 fixture lease acquired.'
    $fixture = Invoke-Fixture 'setup'
    if ([int]$fixture.filecount -ne 2) { throw 'CROP-08 setup did not create exactly two existing image files.' }
    $manifest = [ordered]@{ runId = $runId; categoryid = [int]$fixture.categoryid; elementid = [int]$fixture.elementid; sourcekey = [string]$fixture.sourcekey; seedDraftItemId = [int]$fixture.seedDraftItemId; draftitemid = 0 }
    Set-Content -LiteralPath $manifestFile -Value ($manifest | ConvertTo-Json -Depth 10) -Encoding UTF8
    Write-Phase 'fixture-setup' 'complete' "Disposable CROP-08 category $($fixture.categoryid) created with two existing images."
    . $credentialLoader | Out-Null
    $loadedEnvironment += @('EASYEDU_MOODLE_URL', 'EASYEDU_MOODLE_USERNAME', 'EASYEDU_MOODLE_PASSWORD', 'CCB_MOODLE_URL', 'CCB_MOODLE_USERNAME', 'CCB_MOODLE_PASSWORD')
    $env:EASYEDU_CCB_CROP_HISTORY_MODAL_URL = ([uri]::new([uri]$env:EASYEDU_MOODLE_URL, [string]$fixture.modalPath)).AbsoluteUri
    $env:EASYEDU_CCB_CROP_HISTORY_ELEMENT_ID = [string]$fixture.elementid
    $env:EASYEDU_CCB_CROP_HISTORY_ARTIFACT_ROOT = $runRoot
    $env:EASYEDU_CCB_CROP_HISTORY_MANIFEST = $manifestFile
    $loadedEnvironment += @('EASYEDU_CCB_CROP_HISTORY_MODAL_URL', 'EASYEDU_CCB_CROP_HISTORY_ELEMENT_ID', 'EASYEDU_CCB_CROP_HISTORY_ARTIFACT_ROOT', 'EASYEDU_CCB_CROP_HISTORY_MANIFEST')
    $child = Start-Node @($playwrightCli, 'test', (Split-Path -Leaf $playwrightSpec), ('--config=' + $tempConfig), '--workers=1', '--retries=0', '--reporter=line') $scriptDir
    $started = Get-Date
    while (-not $child.HasExited) { if (((Get-Date) - $started).TotalSeconds -ge $WatchdogSeconds) { Stop-Child; $childExitCode = 124; break }; Start-Sleep -Seconds 1 }
    $child.WaitForExit(); $child.Refresh(); if ($null -eq $childExitCode) { $childExitCode = $child.ExitCode }
    Set-Content -LiteralPath (Join-Path $runRoot 'playwright.stdout.txt') -Value (Safe $child.StandardOutputTask.GetAwaiter().GetResult()) -Encoding UTF8
    Set-Content -LiteralPath (Join-Path $runRoot 'playwright.stderr.txt') -Value (Safe $child.StandardErrorTask.GetAwaiter().GetResult()) -Encoding UTF8
    Write-Phase 'playwright-child' 'complete' "CROP-08 exit code $childExitCode."; $child = $null
} catch {
    if ($null -eq $childExitCode) { $childExitCode = 70 }
    Write-Phase 'supervisor' 'error' $_.Exception.Message
} finally {
    Stop-Child
    try { if ($fixture -and (Test-Path -LiteralPath $manifestFile)) { $cleanupResult = Invoke-Fixture 'cleanup' $manifestFile } } catch { $cleanupError = Safe $_.Exception.Message }
    $cleanup = [ordered]@{ complete = ($null -eq $cleanupError -and (($null -eq $fixture) -or ($cleanupResult -and $cleanupResult.categoryRemoved))); categoryRemoved = if ($cleanupResult) { $cleanupResult.categoryRemoved } else { $null }; draftItemRemoved = if ($cleanupResult) { $cleanupResult.draftItemRemoved } else { $null }; remainingElements = if ($cleanupResult) { $cleanupResult.remainingElements } else { $null }; cleanupError = $cleanupError; completedAt = [DateTime]::UtcNow.ToString('o') }
    Set-Content -LiteralPath $cleanupFile -Value ($cleanup | ConvertTo-Json -Depth 10) -Encoding UTF8
    $status = if ($DiscoveryOnly -and $childExitCode -eq 0) { 'discovery-pass' } elseif ($childExitCode -eq 0 -and $cleanup.complete) { 'pass' } else { 'fail' }
    Set-Content -LiteralPath $resultFile -Value (([ordered]@{ runId = $runId; status = $status; childExitCode = $childExitCode; artifactDirectory = $runRoot; cleanup = $cleanup; environmentCleared = $true } | ConvertTo-Json -Depth 10)) -Encoding UTF8
    foreach ($name in ($loadedEnvironment | Select-Object -Unique)) { Remove-Item -LiteralPath ('Env:' + $name) -ErrorAction SilentlyContinue }
    if ($lease) { try { Release-EasyEduResourceLease -Resource 'moodle51-active-fixture-write' -RunId $runId -Force } catch {} }
    if (Test-Path -LiteralPath $artifactManifestScript) { try { & $artifactManifestScript -RunRoot $runRoot -ApprovedRoot $artifactBase -ProjectNamespace ccb -RunId $runId -Status $(if ($status -eq 'pass') {'passed'} elseif ($DiscoveryOnly) {'incomplete'} else {'failed'}) -RetainFile @('crop-08-history-final.png') | Out-Null } catch { Write-Phase 'artifact-manifest' 'error' $_.Exception.Message } }
}
if ($childExitCode -ne 0) { exit 1 }
exit 0
