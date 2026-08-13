[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [ValidatePattern('^[0-9a-fA-F]{7,40}$')]
    [string]$ExpectedCommit,

    [ValidatePattern('^/[A-Za-z0-9._/-]+(?:\?[A-Za-z0-9._~=&-]+)?$')]
    [string]$AdminPath = '/local/course_banner_builder/admin_manage.php',

    [switch]$DiscoveryOnly,

    [ValidateRange(120, 1800)]
    [int]$WatchdogSeconds = 600,

    [ValidateRange(0, 900)]
    [int]$WaitForLeaseSeconds = 600
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$scriptDir = Split-Path -Parent $PSCommandPath
$pluginRoot = (Resolve-Path -LiteralPath (Join-Path $scriptDir '..\..')).Path
$platformRoot = $env:EASYEDU_PLATFORM_ROOT
$moodleRoot = $env:EASYEDU_MOODLE_ROOT
$credentialLoader = $env:EASYEDU_CCB_CREDENTIAL_LOADER
$playwrightRoot = if ($env:EASYEDU_CCB_PLAYWRIGHT_ROOT) { $env:EASYEDU_CCB_PLAYWRIGHT_ROOT } else { $scriptDir }
$playwrightCli = Join-Path $playwrightRoot 'node_modules\@playwright\test\cli.js'
$nodeModules = Join-Path $playwrightRoot 'node_modules'
$playwrightSpec = Join-Path $scriptDir 'ccb-guide-05.spec.js'
$orchestrationModule = if ($platformRoot) { Join-Path $platformRoot 'tools\orchestration\EasyEduOrchestration.psm1' } else { '' }
$previewStatusScript = if ($platformRoot) { Join-Path $platformRoot 'tools\orchestration\Get-EasyEduRuntimePreviewStatus.ps1' } else { '' }
$artifactManifestScript = if ($platformRoot) { Join-Path $platformRoot 'tools\orchestration\Register-EasyEduArtifactManifest.ps1' } else { '' }
$retentionScript = if ($platformRoot) { Join-Path $platformRoot 'tools\orchestration\Invoke-EasyEduArtifactRetention.ps1' } else { '' }
$artifactBase = if ($env:EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT) { $env:EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT } else {
    Join-Path $env:LOCALAPPDATA 'EasyEdu\artifacts'
}
$artifactBase = [IO.Path]::GetFullPath($artifactBase)
$runId = 'ccb-guide-05-{0}-{1}' -f [DateTime]::UtcNow.ToString('yyyyMMddTHHmmssfffZ'), $PID
$runRoot = Join-Path $artifactBase ('ccb\guide\supervised\' + $runId)
$profile = Join-Path $runRoot 'profile'
$configFile = Join-Path $runRoot 'playwright.config.cjs'
$discoveryFile = Join-Path $runRoot 'playwright.discovery.txt'
$cleanupFile = Join-Path $runRoot 'cleanup.json'
$summaryFile = Join-Path $runRoot 'artifact-summary.json'
$phaseFile = Join-Path $runRoot 'phase-progress.jsonl'
$child = $null
$lease = $null
$childExitCode = 70
$cleanupError = $null
$loadedEnvironment = @()
$originalNodePath = $env:NODE_PATH
$nodePathWasSet = Test-Path Env:NODE_PATH

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
    Add-Content -LiteralPath $phaseFile -Value (([ordered]@{
        timestamp = [DateTime]::UtcNow.ToString('o'); phase = $Phase; status = $Status; message = Safe $Message
    } | ConvertTo-Json -Compress)) -Encoding UTF8
}

function Quote-Argument([string]$Value) { return '"' + ($Value -replace '"', '\\"') + '"' }

function Start-Node([string[]]$Arguments) {
    $info = [Diagnostics.ProcessStartInfo]::new()
    $info.FileName = 'node.exe'; $info.WorkingDirectory = $playwrightRoot
    $info.UseShellExecute = $false; $info.CreateNoWindow = $true
    $info.RedirectStandardOutput = $true; $info.RedirectStandardError = $true
    $info.StandardOutputEncoding = [Text.Encoding]::UTF8; $info.StandardErrorEncoding = [Text.Encoding]::UTF8
    $info.Arguments = (($Arguments | ForEach-Object { Quote-Argument $_ }) -join ' ')
    $process = [Diagnostics.Process]::new(); $process.StartInfo = $info
    if (-not $process.Start()) { throw 'Unable to start the GUIDE-05 Playwright process.' }
    return [pscustomobject]@{
        Process = $process
        StandardOutput = $process.StandardOutput.ReadToEndAsync()
        StandardError = $process.StandardError.ReadToEndAsync()
    }
}

function Stop-Child {
    if ($child -and -not $child.Process.HasExited) {
        try { & taskkill.exe /PID $child.Process.Id /T /F 2>$null | Out-Null } catch { }
    }
}

function Remove-OwnedProfile {
    if (-not (Test-Path -LiteralPath $profile)) { return $true }
    $resolved = [IO.Path]::GetFullPath($profile)
    $approved = [IO.Path]::GetFullPath($runRoot)
    if (-not $resolved.StartsWith($approved + [IO.Path]::DirectorySeparatorChar, [StringComparison]::OrdinalIgnoreCase)) {
        throw "Refusing to remove a profile outside this run root: $resolved"
    }
    Remove-Item -LiteralPath $resolved -Recurse -Force
    return -not (Test-Path -LiteralPath $resolved)
}

function Acquire-ValidationLease {
    $deadline = (Get-Date).AddSeconds($WaitForLeaseSeconds)
    do {
        try {
            return Acquire-EasyEduResourceLease -Resource 'moodle51-active-fixture-write' `
                -ProjectNamespace 'ccb' -RunId $runId -Repository $pluginRoot `
                -Purpose 'CCB GUIDE-05 focused browser validation' `
                -LeaseSeconds ([Math]::Max(180, $WatchdogSeconds + 120))
        } catch {
            if ($WaitForLeaseSeconds -le 0 -or $_.Exception.Message -notmatch 'already leased' -or (Get-Date) -ge $deadline) {
                throw
            }
            Write-Phase 'lease-wait' 'waiting' $_.Exception.Message
            Start-Sleep -Seconds 3
        }
    } while ((Get-Date) -lt $deadline)
    throw 'Timed out waiting for the Moodle 5.1 lease.'
}

New-Item -ItemType Directory -Path $runRoot -Force | Out-Null
Set-Content -LiteralPath $phaseFile -Value '' -Encoding UTF8
Write-Phase 'preflight' 'started' 'CCB GUIDE-05 supervisor initialised.'

try {
    foreach ($required in @($playwrightCli, $playwrightSpec)) {
        if (-not (Test-Path -LiteralPath $required)) { throw "Required validation file is missing: $required" }
    }
    if ([IO.Path]::GetFullPath($runRoot).StartsWith([IO.Path]::GetFullPath($pluginRoot) + [IO.Path]::DirectorySeparatorChar, [StringComparison]::OrdinalIgnoreCase)) {
        throw 'GUIDE-05 artifacts must remain outside the repository.'
    }
    $javascriptScriptDir = $scriptDir -replace '\\', '\\\\'
    $javascriptOutputDir = (Join-Path $runRoot 'playwright-output') -replace '\\', '\\\\'
    $javascriptPlaywrightPackage = (Join-Path $nodeModules '@playwright\test') -replace '\\', '\\\\'
    Set-Content -LiteralPath $configFile -Encoding UTF8 -Value @"
const {defineConfig} = require('$javascriptPlaywrightPackage');
module.exports = defineConfig({
    testDir: '$javascriptScriptDir',
    outputDir: '$javascriptOutputDir',
    timeout: 90000,
    use: {screenshot: 'off', trace: 'off', video: 'off'},
});
"@
    $env:NODE_PATH = $nodeModules
    $discovery = Safe ((@(& node.exe $playwrightCli 'test' (Split-Path -Leaf $playwrightSpec) ('--config=' + $configFile) '--list' 2>&1)) -join "`n")
    Set-Content -LiteralPath $discoveryFile -Value $discovery -Encoding UTF8
    if ($LASTEXITCODE -ne 0 -or $discovery -notmatch 'Total:\s+1\s+test\s+in\s+1\s+file') {
        throw 'Playwright discovery did not select exactly one GUIDE-05 test.'
    }
    Write-Phase 'playwright-discovery' 'complete' 'Exactly one GUIDE-05 test selected.'
    if ($DiscoveryOnly) { $childExitCode = 0; return }

    foreach ($required in @($platformRoot, $moodleRoot, $credentialLoader, $orchestrationModule, $previewStatusScript, $artifactManifestScript, $retentionScript)) {
        if (-not $required -or -not (Test-Path -LiteralPath $required)) { throw 'Runtime configuration is incomplete for GUIDE-05 validation.' }
    }
    Import-Module -Name $orchestrationModule -Force
    $lease = Acquire-ValidationLease
    Write-Phase 'lease-acquire' 'complete' 'Exclusive Moodle 5.1 lease acquired.'
    $preview = & $previewStatusScript -ProfileName 'ccb-moodle51' | ConvertFrom-Json
    $visible = [bool]($preview.managedPreview -and $preview.currentlyPreviewed -and
        (@($preview.appliedCommits | ForEach-Object { ([string]$_).ToLowerInvariant() }) -contains $ExpectedCommit.ToLowerInvariant()))
    if (-not $visible) { throw "Expected GUIDE commit $ExpectedCommit is not visible in the managed CCB preview." }
    Write-Phase 'preview-check' 'complete' 'Expected CCB Guide commit is visible in the managed preview.'
    . $credentialLoader | Out-Null
    $loadedEnvironment += @('EASYEDU_MOODLE_URL', 'EASYEDU_MOODLE_USERNAME', 'EASYEDU_MOODLE_PASSWORD', 'CCB_MOODLE_URL', 'CCB_MOODLE_USERNAME', 'CCB_MOODLE_PASSWORD')
    $env:EASYEDU_CCB_GUIDE05_ADMIN_PATH = $AdminPath; $loadedEnvironment += 'EASYEDU_CCB_GUIDE05_ADMIN_PATH'
    $env:EASYEDU_CCB_GUIDE05_PROFILE = $profile; $loadedEnvironment += 'EASYEDU_CCB_GUIDE05_PROFILE'
    $env:EASYEDU_CCB_GUIDE05_ARTIFACT_ROOT = $runRoot; $loadedEnvironment += 'EASYEDU_CCB_GUIDE05_ARTIFACT_ROOT'
    Write-Phase 'playwright-child' 'started' 'Running the one selected GUIDE-05 scenario.'
    $child = Start-Node @($playwrightCli, 'test', (Split-Path -Leaf $playwrightSpec), ('--config=' + $configFile), '--reporter=line', '--workers=1', '--retries=0')
    $started = Get-Date
    while (-not $child.Process.HasExited) {
        if (((Get-Date) - $started).TotalSeconds -ge $WatchdogSeconds) { Stop-Child; $childExitCode = 124; break }
        Start-Sleep -Seconds 1
    }
    $child.Process.WaitForExit(); $child.Process.Refresh()
    if ($childExitCode -ne 124) { $childExitCode = $child.Process.ExitCode }
    Set-Content -LiteralPath (Join-Path $runRoot 'playwright.stdout.txt') -Value (Safe $child.StandardOutput.GetAwaiter().GetResult()) -Encoding UTF8
    Set-Content -LiteralPath (Join-Path $runRoot 'playwright.stderr.txt') -Value (Safe $child.StandardError.GetAwaiter().GetResult()) -Encoding UTF8
    Write-Phase 'playwright-child' 'complete' "GUIDE-05 scenario exit code $childExitCode."
} catch {
    $runError = Safe $_.Exception.Message
} finally {
    Stop-Child
    try { $profilesRemoved = Remove-OwnedProfile } catch { $profilesRemoved = $false; $cleanupError = Safe $_.Exception.Message }
    $cleanup = [ordered]@{ complete = ($null -eq $cleanupError -and $profilesRemoved); profilesRemoved = $profilesRemoved; cleanupError = $cleanupError }
    Set-Content -LiteralPath $cleanupFile -Value ($cleanup | ConvertTo-Json -Depth 12) -Encoding UTF8
    $status = if ($DiscoveryOnly -and $childExitCode -eq 0) { 'discovery-pass' } elseif ($childExitCode -eq 0 -and $cleanup.complete) { 'pass' } else { 'fail' }
    Set-Content -LiteralPath $summaryFile -Value (([ordered]@{ runId = $runId; status = $status; cleanup = $cleanup; error = $runError; artifactDirectory = $runRoot } | ConvertTo-Json -Depth 12)) -Encoding UTF8
    if (Test-Path -LiteralPath $artifactManifestScript) {
        $manifestStatus = if ($status -eq 'pass') { 'passed' } elseif ($DiscoveryOnly) { 'incomplete' } else { 'failed' }
        try { & $artifactManifestScript -RunRoot $runRoot -ApprovedRoot $artifactBase -ProjectNamespace 'ccb' -RunId $runId -Status $manifestStatus | Out-Null } catch { Write-Phase 'artifact-manifest' 'error' (Safe $_.Exception.Message) }
    }
    if (Test-Path -LiteralPath $retentionScript) {
        try { & $retentionScript -ApprovedRoot $artifactBase -KeepRunId $runId | Set-Content -LiteralPath (Join-Path $runRoot 'retention-dry-run.json') -Encoding UTF8 } catch { Write-Phase 'retention-dry-run' 'error' (Safe $_.Exception.Message) }
    }
    foreach ($name in ($loadedEnvironment | Select-Object -Unique)) { Remove-Item -LiteralPath ('Env:' + $name) -ErrorAction SilentlyContinue }
    if ($nodePathWasSet) { $env:NODE_PATH = $originalNodePath } else { Remove-Item Env:NODE_PATH -ErrorAction SilentlyContinue }
    if ($lease) { try { Release-EasyEduResourceLease -Resource 'moodle51-active-fixture-write' -RunId $runId -Force } catch { } }
}

if ($childExitCode -ne 0 -or -not (Test-Path -LiteralPath $cleanupFile)) { exit 1 }
exit 0
