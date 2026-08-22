[CmdletBinding()]
param(
    [switch]$DiscoveryOnly,

    [ValidateRange(120, 1800)]
    [int]$WatchdogSeconds = 900,

    [ValidateRange(0, 900)]
    [int]$WaitForLeaseSeconds = 900,

    [string]$RuntimePlaywrightRoot,

    [string]$RuntimeRepository,

    [string]$OrchestrationModule = 'C:\dev\easyedu-platform\tools\orchestration\EasyEduOrchestration.psm1'
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$scriptDir = Split-Path -Parent $PSCommandPath
$pluginRoot = (Resolve-Path -LiteralPath (Join-Path $scriptDir '..\..')).Path
$playwrightConfig = Join-Path $scriptDir 'playwright.primary-accordion.config.js'
$playwrightSpec = Join-Path $scriptDir 'ccb-primary-accordion-parity.spec.js'
$credentialLoader = Join-Path $scriptDir 'Use-CCBMoodle51Credentials.ps1'
$artifactManifestScript = Join-Path (Split-Path -Parent $OrchestrationModule) 'Register-EasyEduArtifactManifest.ps1'
$artifactBase = if ($env:EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT) { $env:EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT } else {
    Join-Path $env:LOCALAPPDATA 'EasyEdu\artifacts'
}
$artifactBase = [IO.Path]::GetFullPath($artifactBase)
$runId = 'ccb-primary-accordion-parity-{0}-{1}' -f [DateTime]::UtcNow.ToString('yyyyMMddTHHmmssfffZ'), $PID
$runRoot = Join-Path $artifactBase ('ccb\primary-accordion\supervised\' + $runId)
$profileRoot = Join-Path $runRoot 'profile'
$discoveryFile = Join-Path $runRoot 'playwright.discovery.txt'
$cleanupFile = Join-Path $runRoot 'cleanup.json'
$runnerResultFile = Join-Path $runRoot 'runner-result.json'
$summaryFile = Join-Path $runRoot 'artifact-summary.json'
$phaseFile = Join-Path $runRoot 'phase-progress.jsonl'
$child = $null
$childExitCode = $null
$childTimedOut = $false
$lease = $null
$cleanupError = $null
$failureReason = ''
$environmentSnapshots = @{}

function Safe([string]$Value) {
    if ($null -eq $Value) { return '' }
    $safe = $Value -replace '(?i)([?&](?:sesskey|password|token|session(?:id)?|auth(?:orization)?)=)[^&#\s"'']*', '$1[redacted]'
    foreach ($name in @('EASYEDU_MOODLE_PASSWORD', 'CCB_MOODLE_PASSWORD')) {
        $secret = [Environment]::GetEnvironmentVariable($name, 'Process')
        if ($secret) { $safe = $safe.Replace($secret, '[redacted]') }
    }
    return $safe
}

function Write-Phase([string]$Phase, [string]$Status, [string]$Message = '') {
    Add-Content -LiteralPath $phaseFile -Value (($([ordered]@{
        timestamp = [DateTime]::UtcNow.ToString('o'); phase = $Phase; status = $Status; message = Safe $Message
    }) | ConvertTo-Json -Compress)) -Encoding UTF8
}

function Save-Environment([string]$Name) {
    if (-not $environmentSnapshots.ContainsKey($Name)) {
        $environmentSnapshots[$Name] = [Environment]::GetEnvironmentVariable($Name, 'Process')
    }
}

function Set-TemporaryEnvironment([string]$Name, [AllowEmptyString()][string]$Value) {
    Save-Environment $Name
    [Environment]::SetEnvironmentVariable($Name, $Value, 'Process')
}

function Restore-Environment {
    foreach ($name in $environmentSnapshots.Keys) {
        [Environment]::SetEnvironmentVariable($name, $environmentSnapshots[$name], 'Process')
    }
}

function Quote-Argument([string]$Value) {
    '"' + ($Value -replace '"', '\\"') + '"'
}

function Start-Node([string[]]$Arguments) {
    $info = [Diagnostics.ProcessStartInfo]::new()
    $info.FileName = $nodeExecutable
    $info.WorkingDirectory = $runtimePlaywrightRootResolved
    $info.UseShellExecute = $false
    $info.CreateNoWindow = $true
    $info.RedirectStandardOutput = $true
    $info.RedirectStandardError = $true
    $info.StandardOutputEncoding = [Text.Encoding]::UTF8
    $info.StandardErrorEncoding = [Text.Encoding]::UTF8
    $info.Arguments = (($Arguments | ForEach-Object { Quote-Argument $_ }) -join ' ')
    $process = [Diagnostics.Process]::new()
    $process.StartInfo = $info
    if (-not $process.Start()) { throw 'Unable to start the dedicated Primary accordion Node process.' }
    return $process
}

function Stop-Child {
    if ($child -and -not $child.HasExited) {
        try { & taskkill.exe /PID $child.Id /T /F 2>$null | Out-Null } catch { }
    }
}

function Invoke-Node([string[]]$Arguments, [string]$StdoutFile, [string]$StderrFile, [int]$TimeoutSeconds) {
    $script:child = Start-Node $Arguments
    $started = Get-Date
    while (-not $child.HasExited) {
        if (((Get-Date) - $started).TotalSeconds -ge $TimeoutSeconds) {
            $script:childTimedOut = $true
            Stop-Child
            break
        }
        Start-Sleep -Seconds 1
    }
    $child.WaitForExit()
    $child.Refresh()
    $stdout = Safe $child.StandardOutput.ReadToEnd()
    $stderr = Safe $child.StandardError.ReadToEnd()
    Set-Content -LiteralPath $StdoutFile -Value $stdout -Encoding UTF8
    Set-Content -LiteralPath $StderrFile -Value $stderr -Encoding UTF8
    $result = [ordered]@{
        exitCode = if ($childTimedOut) { 124 } else { $child.ExitCode }
        timedOut = $childTimedOut
    }
    $script:child = $null
    return $result
}

function Remove-OwnedProfile([string]$Path) {
    if (-not (Test-Path -LiteralPath $Path)) { return $true }
    $resolved = [IO.Path]::GetFullPath($Path)
    $approved = [IO.Path]::GetFullPath($runRoot).TrimEnd('\')
    if (-not $resolved.StartsWith($approved + [IO.Path]::DirectorySeparatorChar, [StringComparison]::OrdinalIgnoreCase)) {
        throw "Refusing to remove a profile outside this Primary run: $resolved"
    }
    Remove-Item -LiteralPath $resolved -Recurse -Force
    return -not (Test-Path -LiteralPath $resolved)
}

function Get-CCBMoodle51RuntimeProfile {
    $profileFile = Join-Path $env:LOCALAPPDATA 'EasyEdu\orchestration\profiles\runtime-preview-profiles.json'
    if (-not (Test-Path -LiteralPath $profileFile)) {
        throw 'The ccb-moodle51 runtime profile is unavailable.'
    }
    $profile = (Get-Content -LiteralPath $profileFile -Raw | ConvertFrom-Json).profiles |
        Where-Object { $_.name -eq 'ccb-moodle51' } | Select-Object -First 1
    if (-not $profile) { throw 'The ccb-moodle51 runtime profile is unavailable.' }
    return $profile
}

New-Item -ItemType Directory -Path $runRoot -Force | Out-Null
Set-Content -LiteralPath $phaseFile -Value '' -Encoding UTF8
Write-Phase 'preflight' 'started' 'Dedicated Primary accordion supervisor initialised.'

try {
    $runtimeProfile = Get-CCBMoodle51RuntimeProfile
    if (-not $RuntimeRepository) { $RuntimeRepository = [string]$runtimeProfile.runtimeRepository }
    if (-not $RuntimePlaywrightRoot) { $RuntimePlaywrightRoot = Join-Path $RuntimeRepository 'tools\playwright' }
    $runtimeRepositoryResolved = (Resolve-Path -LiteralPath $RuntimeRepository).Path
    $runtimePlaywrightRootResolved = (Resolve-Path -LiteralPath $RuntimePlaywrightRoot).Path
    $playwrightCli = Join-Path $runtimePlaywrightRootResolved 'node_modules\@playwright\test\cli.js'
    $playwrightSpecForNode = $playwrightSpec.Replace('\', '/')
    $runtimeNodeModules = Join-Path $runtimePlaywrightRootResolved 'node_modules'
    $nodeCommand = Get-Command node.exe -ErrorAction Stop
    $nodeExecutable = $nodeCommand.Source

    foreach ($required in @($playwrightCli, $playwrightConfig, $playwrightSpec, $credentialLoader, $OrchestrationModule)) {
        if (-not (Test-Path -LiteralPath $required -PathType Leaf)) { throw "Required Primary validation file is missing: $required" }
    }
    if (-not (Test-Path -LiteralPath $runtimeNodeModules -PathType Container)) {
        throw "The runtime Playwright dependencies are unavailable: $runtimeNodeModules"
    }
    if ([IO.Path]::GetFullPath($runRoot).StartsWith([IO.Path]::GetFullPath($pluginRoot).TrimEnd('\') + '\', [StringComparison]::OrdinalIgnoreCase) -or
            [IO.Path]::GetFullPath($runRoot).StartsWith($runtimeRepositoryResolved.TrimEnd('\') + '\', [StringComparison]::OrdinalIgnoreCase)) {
        throw 'Primary artifacts must remain outside both the QA worktree and the runtime repository.'
    }

    foreach ($name in @(
        'EASYEDU_CCB_PRIMARY_ACCORDION_ARTIFACT_ROOT',
        'EASYEDU_CCB_PRIMARY_ACCORDION_SOURCE_CATEGORY_ID',
        'EASYEDU_CCB_PRIMARY_RUNTIME_PLAYWRIGHT_ROOT',
        'EASYEDU_MOODLE_URL', 'EASYEDU_MOODLE_USERNAME', 'EASYEDU_MOODLE_PASSWORD',
        'CCB_MOODLE_URL', 'CCB_MOODLE_USERNAME', 'CCB_MOODLE_PASSWORD', 'NODE_PATH'
    )) { Save-Environment $name }
    Set-TemporaryEnvironment 'EASYEDU_CCB_PRIMARY_ACCORDION_ARTIFACT_ROOT' $runRoot
    Set-TemporaryEnvironment 'EASYEDU_CCB_PRIMARY_ACCORDION_SOURCE_CATEGORY_ID' '3'
    Set-TemporaryEnvironment 'EASYEDU_CCB_PRIMARY_RUNTIME_PLAYWRIGHT_ROOT' $runtimePlaywrightRootResolved
    Set-TemporaryEnvironment 'NODE_PATH' $runtimeNodeModules
    . $credentialLoader | Out-Null

    Write-Phase 'playwright-discovery' 'started' 'Discovering exactly one Primary accordion scenario with the dedicated config.'
    $discovery = Invoke-Node @(
        $playwrightCli, 'test', $playwrightSpecForNode, ('--config=' + $playwrightConfig), '--list', '--workers=1', '--retries=0'
    ) (Join-Path $runRoot 'discovery.stdout.txt') (Join-Path $runRoot 'discovery.stderr.txt') 120
    Get-Content -LiteralPath (Join-Path $runRoot 'discovery.stdout.txt') -Raw | Set-Content -LiteralPath $discoveryFile -Encoding UTF8
    $discoveryOutput = Get-Content -LiteralPath $discoveryFile -Raw
    if ($discovery.exitCode -ne 0 -or $discoveryOutput -notmatch 'Total:\s+1\s+test\s+in\s+1\s+file') {
        throw 'Dedicated Primary discovery did not select exactly one test.'
    }
    Write-Phase 'playwright-discovery' 'complete' 'Exactly one Primary accordion test selected.'
    if ($DiscoveryOnly) {
        $childExitCode = 0
        return
    }

    Import-Module -Name $OrchestrationModule -Force
    $leaseParameters = @{
        Resource = 'moodle51-active-fixture-write'
        ProjectNamespace = 'ccb'
        RunId = $runId
        Repository = $runtimeRepositoryResolved
        Purpose = 'CCB Primary accordion parity validation on managed preview'
        LeaseSeconds = [Math]::Max(240, $WatchdogSeconds + 120)
    }
    Write-Phase 'lease-acquire' 'started' 'Waiting for the exclusive Moodle validation lease.'
    if ($WaitForLeaseSeconds -gt 0) {
        $lease = Wait-EasyEduResourceLease @leaseParameters -MaxWaitSeconds $WaitForLeaseSeconds
    } else {
        $lease = Acquire-EasyEduResourceLease @leaseParameters
    }
    Write-Phase 'lease-acquire' 'complete' 'Exclusive Moodle validation lease acquired.'

    Write-Phase 'playwright-child' 'started' 'Running only the dedicated Primary accordion scenario.'
    $execution = Invoke-Node @(
        $playwrightCli, 'test', $playwrightSpecForNode, ('--config=' + $playwrightConfig), '--reporter=line', '--workers=1', '--retries=0'
    ) (Join-Path $runRoot 'playwright.stdout.txt') (Join-Path $runRoot 'playwright.stderr.txt') $WatchdogSeconds
    $childExitCode = $execution.exitCode
    Write-Phase 'playwright-child' 'complete' "Primary accordion scenario exit code $childExitCode."
}
catch {
    if ($null -eq $childExitCode) { $childExitCode = 70 }
    $failureReason = Safe (($_ | Out-String).Trim())
    if ([string]::IsNullOrWhiteSpace($failureReason)) { $failureReason = 'Primary accordion supervisor stopped without an error message.' }
    Write-Phase 'supervisor' 'error' $failureReason
}
finally {
    Stop-Child
    $profilesRemoved = $true
    try { $profilesRemoved = Remove-OwnedProfile $profileRoot } catch {
        $profilesRemoved = $false
        $cleanupError = Safe $_.Exception.Message
    }
    try { Restore-Environment } catch {
        if (-not $cleanupError) { $cleanupError = Safe $_.Exception.Message }
    }
    if ($lease) {
        try {
            Release-EasyEduResourceLease -Resource 'moodle51-active-fixture-write' -RunId $runId -Force
            Write-Phase 'lease-release' 'complete' 'Exclusive Moodle validation lease released.'
        } catch {
            if (-not $cleanupError) { $cleanupError = Safe $_.Exception.Message }
        }
    }
    $cleanup = [ordered]@{
        complete = ($profilesRemoved -and [string]::IsNullOrWhiteSpace($cleanupError))
        profilesRemoved = $profilesRemoved
        credentialsRestored = $true
        leaseReleased = [bool]$lease
        cleanupError = $cleanupError
        completedAt = [DateTime]::UtcNow.ToString('o')
    }
    Set-Content -LiteralPath $cleanupFile -Value ($cleanup | ConvertTo-Json -Depth 20) -Encoding UTF8
    $status = if ($DiscoveryOnly -and $childExitCode -eq 0) { 'discovery-pass' } elseif ($childExitCode -eq 0 -and $cleanup.complete) { 'pass' } else { 'fail' }
    $result = [ordered]@{
        runId = $runId
        status = $status
        childExitCode = $childExitCode
        runtimeRepository = $runtimeRepositoryResolved
        runtimePlaywrightRoot = $runtimePlaywrightRootResolved
        artifactDirectory = $runRoot
        error = $failureReason
        cleanup = $cleanup
    }
    Set-Content -LiteralPath $runnerResultFile -Value ($result | ConvertTo-Json -Depth 20) -Encoding UTF8
    Set-Content -LiteralPath $summaryFile -Value (($result | Select-Object runId, status, childExitCode, runtimeRepository, artifactDirectory, cleanup) | ConvertTo-Json -Depth 20) -Encoding UTF8
    if (Test-Path -LiteralPath $artifactManifestScript -PathType Leaf) {
        $retentionStatus = if ($status -eq 'pass') { 'passed' } elseif ($DiscoveryOnly) { 'incomplete' } else { 'failed' }
        $retainedFiles = @()
        if ($status -eq 'pass') { $retainedFiles = @() }
        try {
            & $artifactManifestScript -RunRoot $runRoot -ApprovedRoot $artifactBase -ProjectNamespace 'ccb' -RunId $runId -Status $retentionStatus -RetainFile $retainedFiles | Out-Null
            Write-Phase 'artifact-manifest' 'complete' 'Primary artifact manifest registered.'
        } catch {
            Write-Phase 'artifact-manifest' 'error' (Safe $_.Exception.Message)
        }
    }
}

if ($childExitCode -ne 0 -or -not (Test-Path -LiteralPath $cleanupFile)) { exit 1 }
exit 0
