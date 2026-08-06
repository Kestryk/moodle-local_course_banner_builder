[CmdletBinding()]
param(
    [switch]$DiscoveryOnly,
    [ValidateRange(180, 1800)]
    [int]$WatchdogSeconds = 900,
    [ValidateRange(0, 900)]
    [int]$WaitForLeaseSeconds = 0
)

$ErrorActionPreference = 'Stop'
$scriptDir = Split-Path -Parent $PSCommandPath
$pluginRoot = (Resolve-Path -LiteralPath (Join-Path $scriptDir '..\..')).Path
$moodleRoot = (Resolve-Path -LiteralPath (Join-Path $scriptDir '..\..\..\..')).Path
$moodlePhp = Join-Path $moodleRoot '..\php\php.exe'
$playwrightCli = Join-Path $scriptDir 'node_modules\@playwright\test\cli.js'
$playwrightConfig = Join-Path $scriptDir 'playwright.config.js'
$playwrightSpec = Join-Path $scriptDir 'ccb-layer-modal-action-rail.spec.js'
$fixtureHelper = Join-Path $scriptDir 'ccb-layer-object-row-fixture.php'
$credentialLoader = Join-Path $scriptDir 'Use-CCBMoodle51Credentials.ps1'
$orchestrationModule = 'C:\dev\easyedu-platform\tools\orchestration\EasyEduOrchestration.psm1'
$artifactManifestScript = Join-Path (Split-Path -Parent $orchestrationModule) 'Register-EasyEduArtifactManifest.ps1'
$artifactBase = if ($env:EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT) { $env:EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT } else {
    Join-Path $env:LOCALAPPDATA 'EasyEdu\artifacts'
}
$artifactBase = [IO.Path]::GetFullPath($artifactBase)
$runId = 'ccb-layer-modal-action-rail-{0}-{1}' -f [DateTime]::UtcNow.ToString('yyyyMMddTHHmmssfffZ'), $PID
$runRoot = Join-Path $artifactBase ('ccb\modal-action-rail\supervised\' + $runId)
$manifestFile = Join-Path $runRoot 'restoration-manifest.json'
$discoveryFile = Join-Path $runRoot 'playwright.discovery.txt'
$cleanupFile = Join-Path $runRoot 'cleanup.json'
$runnerResultFile = Join-Path $runRoot 'runner-result.json'
$summaryFile = Join-Path $runRoot 'artifact-summary.json'
$phaseFile = Join-Path $runRoot 'phase-progress.jsonl'
$ownedProfiles = @(
    (Join-Path $runRoot 'modal-action-rail\desktop-100\profile'),
    (Join-Path $runRoot 'modal-action-rail\tablet-100\profile'),
    (Join-Path $runRoot 'modal-action-rail\mobile-100\profile'),
    (Join-Path $runRoot 'modal-action-rail\desktop-200\profile'),
    (Join-Path $runRoot 'modal-action-rail\mobile-200\profile')
)
$child = $null
$childExitCode = $null
$childTimedOut = $false
$lease = $null
$manifest = $null
$cleanupResult = $null
$cleanupError = $null
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
    Add-Content -LiteralPath $phaseFile -Value (($([ordered]@{
        timestamp = [DateTime]::UtcNow.ToString('o'); phase = $Phase; status = $Status; message = Safe $Message
    }) | ConvertTo-Json -Compress)) -Encoding UTF8
}

function Invoke-Fixture([string]$Command, [string]$Argument = '') {
    if (-not (Test-Path -LiteralPath $moodlePhp)) { throw "Moodle PHP is unavailable: $moodlePhp" }
    $arguments = @('-f', $fixtureHelper, '--', $Command)
    if ($Argument) { $arguments += $Argument }
    $lines = @(& $moodlePhp @arguments 2>&1)
    if ($LASTEXITCODE -ne 0) { throw (Safe ($lines -join "`n")) }
    $json = $lines | Where-Object { $_ -match '^\s*\{' } | Select-Object -Last 1
    if (-not $json) { throw "Layer modal action-rail fixture returned no JSON for $Command." }
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
    # Drain both redirected streams immediately. Waiting until process exit can
    # deadlock Windows pipes when Playwright reports a verbose failure.
    $process | Add-Member -NotePropertyName StandardOutputTask -NotePropertyValue $process.StandardOutput.ReadToEndAsync()
    $process | Add-Member -NotePropertyName StandardErrorTask -NotePropertyValue $process.StandardError.ReadToEndAsync()
    return $process
}

function Stop-Child {
    if ($child -and -not $child.HasExited) {
        try { & taskkill.exe /PID $child.Id /T /F 2>$null | Out-Null } catch { }
    }
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

function Acquire-ValidationLease {
    $deadline = (Get-Date).AddSeconds($WaitForLeaseSeconds)
    do {
        try {
            return Acquire-EasyEduResourceLease -Resource 'moodle51-active-fixture-write' `
                -ProjectNamespace 'ccb' -RunId $runId -Repository $pluginRoot `
                -Purpose 'CCB layer-modal action-rail visual validation fixture' `
                -LeaseSeconds ([Math]::Max(240, $WatchdogSeconds + 120))
        } catch {
            $message = $_.Exception.Message
            if ($WaitForLeaseSeconds -le 0 -or $message -notmatch 'already leased' -or (Get-Date) -ge $deadline) {
                throw
            }
            Write-Phase 'lease-wait' 'waiting' $message
            Start-Sleep -Seconds 3
        }
    } while ((Get-Date) -lt $deadline)
    throw 'Timed out waiting for the shared Moodle fixture lease.'
}

New-Item -ItemType Directory -Path $runRoot -Force | Out-Null
Set-Content -LiteralPath $phaseFile -Value '' -Encoding UTF8
Write-Phase 'preflight' 'started' 'CCB layer-modal action-rail validation supervisor initialised.'

try {
    foreach ($required in @($playwrightCli, $playwrightConfig, $playwrightSpec, $fixtureHelper, $credentialLoader, $orchestrationModule)) {
        if (-not (Test-Path -LiteralPath $required)) { throw "Required validation file is missing: $required" }
    }
    if ([IO.Path]::GetFullPath($runRoot).StartsWith([IO.Path]::GetFullPath($pluginRoot) + [IO.Path]::DirectorySeparatorChar, [StringComparison]::OrdinalIgnoreCase)) {
        throw 'Modal action-rail artifact root resolves inside the CCB repository.'
    }
    $env:EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT = $runRoot; $loadedEnvironment += 'EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT'
    . $credentialLoader | Out-Null
    $loadedEnvironment += @('EASYEDU_MOODLE_URL', 'EASYEDU_MOODLE_USERNAME', 'EASYEDU_MOODLE_PASSWORD', 'CCB_MOODLE_URL', 'CCB_MOODLE_USERNAME', 'CCB_MOODLE_PASSWORD')
    $env:EASYEDU_CCB_MODAL_RAIL_ARTIFACT_ROOT = $runRoot; $loadedEnvironment += 'EASYEDU_CCB_MODAL_RAIL_ARTIFACT_ROOT'
    $env:EASYEDU_CCB_MODAL_RAIL_SOURCE_CATEGORY_ID = '0'; $loadedEnvironment += 'EASYEDU_CCB_MODAL_RAIL_SOURCE_CATEGORY_ID'

    Write-Phase 'playwright-discovery' 'started' 'Selecting exactly one modal action-rail test before fixture work.'
    $discoveryArgs = @($playwrightCli, 'test', $playwrightSpec.Replace('\', '/'), ('--config=' + $playwrightConfig.Replace('\', '/')), '--list')
    $discoveryOutput = Safe ((@(& node.exe @discoveryArgs 2>&1)) -join "`n")
    Set-Content -LiteralPath $discoveryFile -Value $discoveryOutput -Encoding UTF8
    if ($LASTEXITCODE -ne 0 -or $discoveryOutput -notmatch 'Total:\s+1\s+test\s+in\s+1\s+file') {
        throw 'Playwright discovery did not select exactly one CCB layer-modal action-rail test.'
    }
    Write-Phase 'playwright-discovery' 'complete' 'Exactly one layer-modal action-rail test selected.'
    if ($DiscoveryOnly) { $childExitCode = 0; return }

    Import-Module -Name $orchestrationModule -Force
    $lease = Acquire-ValidationLease
    Write-Phase 'lease-acquire' 'complete' 'CCB resource lease acquired.'

    Write-Phase 'fixture-setup' 'started' 'Creating disposable source with image layers for modal validation.'
    $setup = Invoke-Fixture 'setup'
    $manifest = [ordered]@{ runId = $runId; categoryid = [int]$setup.categoryid; sourcekey = [string]$setup.sourcekey }
    Set-Content -LiteralPath $manifestFile -Value ($manifest | ConvertTo-Json -Depth 20) -Encoding UTF8
    $env:EASYEDU_CCB_MODAL_RAIL_SOURCE_CATEGORY_ID = [string]$setup.categoryid
    Write-Phase 'fixture-setup' 'complete' "Disposable category $($setup.categoryid) contains image modal data."

    Write-Phase 'playwright-child' 'started' 'Running the one selected modal action-rail scenario.'
    $child = Start-Node @($playwrightCli, 'test', $playwrightSpec.Replace('\', '/'), ('--config=' + $playwrightConfig.Replace('\', '/')), '--reporter=line', '--workers=1', '--retries=0')
    $started = Get-Date
    while (-not $child.HasExited) {
        if (((Get-Date) - $started).TotalSeconds -ge $WatchdogSeconds) { $childTimedOut = $true; Stop-Child; break }
        Start-Sleep -Seconds 1
    }
    $child.WaitForExit(); $child.Refresh()
    $childExitCode = if ($childTimedOut) { 124 } else { $child.ExitCode }
    $stdout = $child.StandardOutputTask.GetAwaiter().GetResult()
    $stderr = $child.StandardErrorTask.GetAwaiter().GetResult()
    Set-Content -LiteralPath (Join-Path $runRoot 'playwright.stdout.txt') -Value (Safe $stdout) -Encoding UTF8
    Set-Content -LiteralPath (Join-Path $runRoot 'playwright.stderr.txt') -Value (Safe $stderr) -Encoding UTF8
    Write-Phase 'playwright-child' 'complete' "Modal action-rail scenario exit code $childExitCode."
    $child = $null
}
catch {
    if ($null -eq $childExitCode) { $childExitCode = 70 }
    Write-Phase 'supervisor' 'error' $_.Exception.Message
}
finally {
    Stop-Child
    try {
        if ($manifest -and (Test-Path -LiteralPath $manifestFile)) { $cleanupResult = Invoke-Fixture 'cleanup' $manifestFile }
    } catch { $cleanupError = Safe $_.Exception.Message }
    $profilesRemoved = $true
    foreach ($ownedProfile in $ownedProfiles) {
        try { $profilesRemoved = (Remove-OwnedProfile $ownedProfile) -and $profilesRemoved }
        catch { $profilesRemoved = $false; if (-not $cleanupError) { $cleanupError = Safe $_.Exception.Message } }
    }
    $cleanup = [ordered]@{
        complete = ($null -eq $cleanupError -and (($null -eq $manifest) -or ($cleanupResult -and $cleanupResult.categoryRemoved)) -and $profilesRemoved)
        categoryRemoved = if ($cleanupResult) { $cleanupResult.categoryRemoved } else { $null }
        remainingElements = if ($cleanupResult) { $cleanupResult.remainingElements } else { $null }
        profilesRemoved = $profilesRemoved; cleanupError = $cleanupError; completedAt = [DateTime]::UtcNow.ToString('o')
    }
    Set-Content -LiteralPath $cleanupFile -Value ($cleanup | ConvertTo-Json -Depth 20) -Encoding UTF8
    $status = if ($DiscoveryOnly -and $childExitCode -eq 0) { 'discovery-pass' } elseif ($childExitCode -eq 0 -and $cleanup.complete) { 'pass' } else { 'fail' }
    $result = [ordered]@{ runId = $runId; status = $status; childExitCode = $childExitCode; artifactDirectory = $runRoot; environmentCleared = $true }
    Set-Content -LiteralPath $runnerResultFile -Value ($result | ConvertTo-Json -Depth 20) -Encoding UTF8
    Set-Content -LiteralPath $summaryFile -Value (([ordered]@{ runId = $runId; status = $status; cleanup = $cleanup; artifactDirectory = $runRoot }) | ConvertTo-Json -Depth 20) -Encoding UTF8
    if (Test-Path -LiteralPath $artifactManifestScript -PathType Leaf) {
        $retentionStatus = if ($status -eq 'pass') { 'passed' } elseif ($DiscoveryOnly) { 'incomplete' } else { 'failed' }
        try { & $artifactManifestScript -RunRoot $runRoot -ApprovedRoot $artifactBase -ProjectNamespace 'ccb' -RunId $runId -Status $retentionStatus | Out-Null } catch { Write-Phase 'artifact-manifest' 'error' (Safe $_.Exception.Message) }
    }
    foreach ($name in ($loadedEnvironment | Select-Object -Unique)) { Remove-Item -LiteralPath ('Env:' + $name) -ErrorAction SilentlyContinue }
    if ($lease) { try { Release-EasyEduResourceLease -Resource 'moodle51-active-fixture-write' -RunId $runId -Force } catch { } }
}

if ($childExitCode -ne 0 -or -not (Test-Path -LiteralPath $cleanupFile)) { exit 1 }
exit 0
