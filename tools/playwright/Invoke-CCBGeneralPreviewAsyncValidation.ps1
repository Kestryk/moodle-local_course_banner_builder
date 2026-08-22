[CmdletBinding()]
param(
    [switch]$DiscoveryOnly,
    [ValidateRange(120, 1800)]
    [int]$WatchdogSeconds = 900,
    [ValidateRange(0, 900)]
    [int]$WaitForLeaseSeconds = 900
)

$ErrorActionPreference = 'Stop'
$scriptDir = Split-Path -Parent $PSCommandPath
$pluginRoot = (Resolve-Path -LiteralPath (Join-Path $scriptDir '..\..')).Path
$orchestrationRoot = Join-Path $env:LOCALAPPDATA 'EasyEdu\orchestration'
$profilesPath = Join-Path $orchestrationRoot 'profiles\runtime-preview-profiles.json'
$runtimeProfile = if (Test-Path -LiteralPath $profilesPath) {
    ((Get-Content -LiteralPath $profilesPath -Raw | ConvertFrom-Json).profiles | Where-Object { $_.name -eq 'ccb-moodle51' } |
        Select-Object -First 1)
}
if (!$runtimeProfile) { throw 'The ccb-moodle51 runtime preview profile is unavailable.' }
$moodlePhp = (Resolve-Path -LiteralPath $runtimeProfile.phpExecutable).Path
$runtimePluginRoot = (Resolve-Path -LiteralPath $runtimeProfile.runtimeRepository).Path
$runtimePlaywrightRoot = Join-Path $runtimePluginRoot 'tools\playwright'
$playwrightCli = Join-Path $runtimePlaywrightRoot 'node_modules\@playwright\test\cli.js'
$playwrightConfig = Join-Path $scriptDir 'playwright.general-preview-async.config.js'
$playwrightSpec = Join-Path $scriptDir 'ccb-general-preview-async.spec.js'
$fixtureHelper = Join-Path $runtimePluginRoot 'tools\playwright\ccb-layer-object-row-fixture.php'
$credentialLoader = Join-Path $scriptDir 'Use-CCBMoodle51Credentials.ps1'
$orchestrationModule = 'C:\dev\easyedu-platform\tools\orchestration\EasyEduOrchestration.psm1'
$artifactManifestScript = Join-Path (Split-Path -Parent $orchestrationModule) 'Register-EasyEduArtifactManifest.ps1'
$artifactBase = if ($env:EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT) { $env:EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT } else {
    Join-Path $env:LOCALAPPDATA 'EasyEdu\artifacts'
}
$artifactBase = [IO.Path]::GetFullPath($artifactBase)
$runId = 'ccb-general-preview-async-{0}-{1}' -f [DateTime]::UtcNow.ToString('yyyyMMddTHHmmssfffZ'), $PID
$runRoot = Join-Path $artifactBase ('ccb\general-preview-async\supervised\' + $runId)
$manifestFile = Join-Path $runRoot 'restoration-manifest.json'
$cleanupFile = Join-Path $runRoot 'cleanup.json'
$resultFile = Join-Path $runRoot 'runner-result.json'
$discoveryFile = Join-Path $runRoot 'playwright.discovery.txt'
$child = $null; $lease = $null; $manifest = $null; $childExitCode = $null; $cleanupError = $null; $failureReason = ''
$loadedEnvironment = @()

function Protect-Output([string]$Value) {
    if ($null -eq $Value) { return '' }
    $safe = $Value -replace '(?i)([?&](?:sesskey|password|token|session(?:id)?|auth(?:orization)?)=)[^&#\s"'']*', '$1[redacted]'
    foreach ($name in @('EASYEDU_MOODLE_PASSWORD', 'CCB_MOODLE_PASSWORD')) {
        $secret = [Environment]::GetEnvironmentVariable($name)
        if ($secret) { $safe = $safe.Replace($secret, '[redacted]') }
    }
    return $safe
}

function Invoke-Fixture([string]$Command, [string]$Argument = '') {
    $arguments = @('-f', $fixtureHelper, '--', $Command)
    if ($Argument) { $arguments += $Argument }
    $lines = @(& $moodlePhp @arguments 2>&1)
    if ($LASTEXITCODE -ne 0) { throw (Protect-Output ($lines -join "`n")) }
    $json = $lines | Where-Object { $_ -match '^\s*\{' } | Select-Object -Last 1
    if (!$json) { throw "General preview fixture returned no JSON for $Command." }
    return ($json | ConvertFrom-Json)
}

function Start-Node([string[]]$Arguments) {
    $info = [Diagnostics.ProcessStartInfo]::new()
    $info.FileName = 'node.exe'; $info.WorkingDirectory = $scriptDir; $info.UseShellExecute = $false
    $info.CreateNoWindow = $true; $info.RedirectStandardOutput = $true; $info.RedirectStandardError = $true
    $info.Arguments = (($Arguments | ForEach-Object { '"' + ($_ -replace '"', '\\"') + '"' }) -join ' ')
    $process = [Diagnostics.Process]::new(); $process.StartInfo = $info
    if (!$process.Start()) { throw 'Unable to start the general preview Playwright process.' }
    return $process
}

New-Item -ItemType Directory -Path $runRoot -Force | Out-Null
try {
    foreach ($required in @($moodlePhp, $playwrightCli, $playwrightConfig, $playwrightSpec, $fixtureHelper, $credentialLoader, $orchestrationModule)) {
        if (!(Test-Path -LiteralPath $required)) { throw "Required validation file is missing: $required" }
    }
    if ([IO.Path]::GetFullPath($runRoot).StartsWith([IO.Path]::GetFullPath($pluginRoot) + [IO.Path]::DirectorySeparatorChar, [StringComparison]::OrdinalIgnoreCase)) {
        throw 'General preview artifact root resolves inside the CCB repository.'
    }
    $env:EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT = $runRoot; $loadedEnvironment += 'EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT'
    . $credentialLoader | Out-Null
    $loadedEnvironment += @('EASYEDU_MOODLE_URL', 'EASYEDU_MOODLE_USERNAME', 'EASYEDU_MOODLE_PASSWORD', 'CCB_MOODLE_URL', 'CCB_MOODLE_USERNAME', 'CCB_MOODLE_PASSWORD')
    $env:EASYEDU_CCB_GENERAL_PREVIEW_ARTIFACT_ROOT = $runRoot; $loadedEnvironment += 'EASYEDU_CCB_GENERAL_PREVIEW_ARTIFACT_ROOT'
    $env:EASYEDU_CCB_GENERAL_PREVIEW_CATEGORY_ID = '0'; $loadedEnvironment += 'EASYEDU_CCB_GENERAL_PREVIEW_CATEGORY_ID'
    $env:NODE_PATH = (Join-Path $runtimePlaywrightRoot 'node_modules'); $loadedEnvironment += 'NODE_PATH'

    $listArgs = @($playwrightCli, 'test', $playwrightSpec.Replace('\', '/'), ('--config=' + $playwrightConfig.Replace('\', '/')), '--list')
    $discovery = Protect-Output ((@(& node.exe @listArgs 2>&1)) -join "`n")
    Set-Content -LiteralPath $discoveryFile -Value $discovery -Encoding UTF8
    if ($LASTEXITCODE -ne 0 -or $discovery -notmatch 'Total:\s+1\s+test\s+in\s+1\s+file') {
        throw 'Playwright discovery did not select exactly one general preview scenario.'
    }
    if ($DiscoveryOnly) { $childExitCode = 0; return }

    Import-Module -Name $orchestrationModule -Force
    $leaseParameters = @{
        Resource = 'moodle51-active-fixture-write'
        ProjectNamespace = 'ccb'
        RunId = $runId
        Repository = $pluginRoot
        Purpose = 'CCB general preview asynchronous-action validation fixture'
        LeaseSeconds = [Math]::Max(240, $WatchdogSeconds + 120)
    }
    if ($WaitForLeaseSeconds -gt 0) {
        $lease = Wait-EasyEduResourceLease @leaseParameters -MaxWaitSeconds $WaitForLeaseSeconds
    } else {
        $lease = Acquire-EasyEduResourceLease @leaseParameters
    }
    $setup = Invoke-Fixture 'setup'
    $manifest = [ordered]@{ categoryid = [int]$setup.categoryid; sourcekey = [string]$setup.sourcekey }
    Set-Content -LiteralPath $manifestFile -Value ($manifest | ConvertTo-Json) -Encoding UTF8
    $env:EASYEDU_CCB_GENERAL_PREVIEW_CATEGORY_ID = [string]$setup.categoryid

    $child = Start-Node @($playwrightCli, 'test', $playwrightSpec.Replace('\', '/'), ('--config=' + $playwrightConfig.Replace('\', '/')), '--reporter=line', '--workers=1', '--retries=0')
    $started = Get-Date
    while (!$child.HasExited -and ((Get-Date) - $started).TotalSeconds -lt $WatchdogSeconds) { Start-Sleep -Seconds 1 }
    if (!$child.HasExited) { & taskkill.exe /PID $child.Id /T /F 2>$null | Out-Null; $childExitCode = 124 } else { $childExitCode = $child.ExitCode }
    $child.WaitForExit(); $child.Refresh()
    Set-Content -LiteralPath (Join-Path $runRoot 'playwright.stdout.txt') -Value (Protect-Output $child.StandardOutput.ReadToEnd()) -Encoding UTF8
    Set-Content -LiteralPath (Join-Path $runRoot 'playwright.stderr.txt') -Value (Protect-Output $child.StandardError.ReadToEnd()) -Encoding UTF8
} catch {
    if ($null -eq $childExitCode) { $childExitCode = 70 }
    $failureReason = Protect-Output (($_ | Out-String).Trim())
    if ([string]::IsNullOrWhiteSpace($failureReason)) { $failureReason = 'General preview supervisor stopped without an error message.' }
    $cleanupError = $failureReason
} finally {
    if ($child -and !$child.HasExited) { & taskkill.exe /PID $child.Id /T /F 2>$null | Out-Null }
    $cleanupResult = $null
    try { if ($manifest -and (Test-Path -LiteralPath $manifestFile)) { $cleanupResult = Invoke-Fixture 'cleanup' $manifestFile } } catch { $cleanupError = Protect-Output $_.Exception.Message }
    $cleanup = [ordered]@{ complete = ($null -eq $cleanupError -and (($null -eq $manifest) -or ($cleanupResult -and $cleanupResult.categoryRemoved))); categoryRemoved = if ($cleanupResult) { $cleanupResult.categoryRemoved } else { $null }; cleanupError = $cleanupError }
    Set-Content -LiteralPath $cleanupFile -Value ($cleanup | ConvertTo-Json) -Encoding UTF8
    foreach ($name in ($loadedEnvironment | Select-Object -Unique)) { Remove-Item -LiteralPath ('Env:' + $name) -ErrorAction SilentlyContinue }
    if ($lease) { Release-EasyEduResourceLease -Resource 'moodle51-active-fixture-write' -RunId $runId -Force }
    $status = if ($DiscoveryOnly -and $childExitCode -eq 0) { 'discovery-pass' } elseif ($childExitCode -eq 0 -and $cleanup.complete) { 'pass' } else { 'fail' }
    Set-Content -LiteralPath $resultFile -Value (([ordered]@{ runId = $runId; status = $status; childExitCode = $childExitCode; error = $failureReason; artifactDirectory = $runRoot; cleanup = $cleanup } | ConvertTo-Json -Depth 8)) -Encoding UTF8
    if (Test-Path -LiteralPath $artifactManifestScript) { & $artifactManifestScript -RunRoot $runRoot -ApprovedRoot $artifactBase -ProjectNamespace ccb -RunId $runId -Status $(if ($status -eq 'pass') {'passed'} else {'failed'}) | Out-Null }
}
if ($childExitCode -ne 0 -or -not (Test-Path -LiteralPath $cleanupFile)) { exit 1 }
