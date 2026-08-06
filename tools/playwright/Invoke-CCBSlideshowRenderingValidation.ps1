[CmdletBinding()]
param(
    [switch]$DiscoveryOnly,
    [ValidateRange(120, 1800)]
    [int]$WatchdogSeconds = 900,
    [ValidateRange(0, 900)]
    [int]$WaitForLeaseSeconds = 600
)

$ErrorActionPreference = 'Stop'
$scriptDir = Split-Path -Parent $PSCommandPath
$pluginRoot = (Resolve-Path -LiteralPath (Join-Path $scriptDir '..\..')).Path
$platformRoot = $env:EASYEDU_PLATFORM_ROOT
$moodleRoot = $env:EASYEDU_MOODLE_ROOT
$credentialLoader = $env:EASYEDU_CCB_CREDENTIAL_LOADER
$playwrightRoot = if ($env:EASYEDU_CCB_PLAYWRIGHT_ROOT) { $env:EASYEDU_CCB_PLAYWRIGHT_ROOT } else { $scriptDir }
$playwrightCli = Join-Path $playwrightRoot 'node_modules\@playwright\test\cli.js'
$nodeModules = Join-Path $playwrightRoot 'node_modules'
$playwrightSpec = Join-Path $scriptDir 'ccb-slideshow-rendering.spec.js'
$playwrightSpecArgument = Split-Path -Leaf $playwrightSpec
$fixtureHelper = Join-Path $scriptDir 'ccb-slideshow-rendering-fixture.php'
$orchestrationModule = if ($platformRoot) { Join-Path $platformRoot 'tools\orchestration\EasyEduOrchestration.psm1' } else { '' }
$artifactBase = if ($env:EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT) { $env:EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT } else {
    Join-Path $env:LOCALAPPDATA 'EasyEdu\artifacts'
}
$artifactBase = [IO.Path]::GetFullPath($artifactBase)
$runId = 'ccb-slideshow-rendering-{0}-{1}' -f [DateTime]::UtcNow.ToString('yyyyMMddTHHmmssfffZ'), $PID
$runRoot = Join-Path $artifactBase ('ccb\slideshow\supervised\' + $runId)
$playwrightConfig = Join-Path $runRoot 'playwright.config.cjs'
$profile = Join-Path $runRoot 'profile'
$manifestFile = Join-Path $runRoot 'restoration-manifest.json'
$cleanupFile = Join-Path $runRoot 'cleanup.json'
$summaryFile = Join-Path $runRoot 'artifact-summary.json'
$discoveryFile = Join-Path $runRoot 'playwright.discovery.txt'
$child = $null
$lease = $null
$manifest = $null
$childExitCode = 70
$loadedEnvironment = @()
$runError = $null
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

function Invoke-Fixture([string]$Command, [string]$Argument = '') {
    $moodlePhp = Join-Path $moodleRoot '..\php\php.exe'
    $arguments = @('-f', $fixtureHelper, '--', $Command)
    if ($Argument) { $arguments += $Argument }
    $previousErrorAction = $ErrorActionPreference
    try {
        # Moodle's backup subsystem can emit diagnostics to stderr while the
        # fixture command still succeeds. Preserve that output as evidence and
        # use the native exit code, not PowerShell's stderr promotion, as the
        # cleanup success authority.
        $ErrorActionPreference = 'Continue'
        $lines = @(& $moodlePhp @arguments 2>&1)
        $exitCode = $LASTEXITCODE
    } finally {
        $ErrorActionPreference = $previousErrorAction
    }
    if ($exitCode -ne 0) { throw (Safe ($lines -join "`n")) }
    $json = $lines | Where-Object { $_ -match '^\s*\{' } | Select-Object -Last 1
    if (-not $json) { throw "Slideshow fixture returned no JSON for $Command." }
    return ($json | ConvertFrom-Json)
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
    if (-not $process.Start()) { throw 'Unable to start the Playwright Node process.' }
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

function Acquire-ValidationLease {
    $deadline = (Get-Date).AddSeconds($WaitForLeaseSeconds)
    do {
        try {
            return Acquire-EasyEduResourceLease -Resource 'moodle51-active-fixture-write' `
                -ProjectNamespace 'ccb' -RunId $runId -Repository $pluginRoot `
                -Purpose 'CCB Slideshow fixture preflight' `
                -LeaseSeconds ([Math]::Max(180, $WatchdogSeconds + 120))
        } catch {
            $message = $_.Exception.Message
            if ($WaitForLeaseSeconds -le 0 -or $message -notmatch 'already leased' -or (Get-Date) -ge $deadline) {
                throw
            }
            Start-Sleep -Seconds 3
        }
    } while ((Get-Date) -lt $deadline)
    throw 'Timed out waiting for the Moodle 5.1 fixture lease.'
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

New-Item -ItemType Directory -Path $runRoot -Force | Out-Null
$javascriptScriptDir = $scriptDir -replace '\\', '\\\\'
$javascriptOutputDir = (Join-Path $runRoot 'playwright-output') -replace '\\', '\\\\'
$javascriptPlaywrightPackage = (Join-Path $nodeModules '@playwright\test') -replace '\\', '\\\\'
Set-Content -LiteralPath $playwrightConfig -Encoding UTF8 -Value @"
const {defineConfig} = require('$javascriptPlaywrightPackage');
module.exports = defineConfig({
    testDir: '$javascriptScriptDir',
    outputDir: '$javascriptOutputDir',
    timeout: 90000,
    use: {screenshot: 'off', trace: 'off', video: 'off'},
});
"@
try {
    foreach ($required in @($playwrightCli, $playwrightSpec, $fixtureHelper)) {
        if (-not $required -or -not (Test-Path -LiteralPath $required)) { throw "Required validation file is missing: $required" }
    }
    if ([IO.Path]::GetFullPath($runRoot).StartsWith([IO.Path]::GetFullPath($pluginRoot) + [IO.Path]::DirectorySeparatorChar, [StringComparison]::OrdinalIgnoreCase)) {
        throw 'Slideshow artifacts must remain outside the repository.'
    }
    $env:NODE_PATH = $nodeModules
    $discoveryArgs = @($playwrightCli, 'test', $playwrightSpecArgument, ('--config=' + $playwrightConfig), '--list')
    $discovery = Safe ((@(& node.exe @discoveryArgs 2>&1)) -join "`n")
    Set-Content -LiteralPath $discoveryFile -Value $discovery -Encoding UTF8
    if ($LASTEXITCODE -ne 0 -or $discovery -notmatch 'Total:\s+1\s+test\s+in\s+1\s+file') {
        throw 'Playwright discovery did not select exactly one Slideshow test.'
    }
    if ($DiscoveryOnly) {
        $childExitCode = 0
    } else {
        foreach ($required in @($platformRoot, $moodleRoot, $credentialLoader, $orchestrationModule)) {
            if (-not $required -or -not (Test-Path -LiteralPath $required)) { throw 'Runtime configuration is incomplete for Slideshow validation.' }
        }
        Import-Module -Name $orchestrationModule -Force
        $lease = Acquire-ValidationLease
        . $credentialLoader | Out-Null
        $loadedEnvironment += @('EASYEDU_MOODLE_URL', 'EASYEDU_MOODLE_USERNAME', 'EASYEDU_MOODLE_PASSWORD', 'CCB_MOODLE_URL', 'CCB_MOODLE_USERNAME', 'CCB_MOODLE_PASSWORD')
        $setup = Invoke-Fixture 'setup'
        $manifest = [ordered]@{ courseid = [int]$setup.courseid; snapshot = @($setup.snapshot) }
        Set-Content -LiteralPath $manifestFile -Value ($manifest | ConvertTo-Json -Depth 20) -Encoding UTF8
        $env:EASYEDU_CCB_SLIDESHOW_COURSE_ID = [string]$setup.courseid; $loadedEnvironment += 'EASYEDU_CCB_SLIDESHOW_COURSE_ID'
        $env:EASYEDU_CCB_SLIDESHOW_PROFILE = $profile; $loadedEnvironment += 'EASYEDU_CCB_SLIDESHOW_PROFILE'
        $env:EASYEDU_CCB_SLIDESHOW_ARTIFACT_ROOT = $runRoot; $loadedEnvironment += 'EASYEDU_CCB_SLIDESHOW_ARTIFACT_ROOT'
        $child = Start-Node @($playwrightCli, 'test', $playwrightSpecArgument, ('--config=' + $playwrightConfig), '--reporter=line', '--workers=1', '--retries=0')
        $started = Get-Date
        while (-not $child.Process.HasExited) {
            if (((Get-Date) - $started).TotalSeconds -ge $WatchdogSeconds) { Stop-Child; $childExitCode = 124; break }
            Start-Sleep -Seconds 1
        }
        $child.Process.WaitForExit(); $child.Process.Refresh()
        if ($childExitCode -ne 124) { $childExitCode = $child.Process.ExitCode }
        Set-Content -LiteralPath (Join-Path $runRoot 'playwright.stdout.txt') -Value (Safe $child.StandardOutput.GetAwaiter().GetResult()) -Encoding UTF8
        Set-Content -LiteralPath (Join-Path $runRoot 'playwright.stderr.txt') -Value (Safe $child.StandardError.GetAwaiter().GetResult()) -Encoding UTF8
    }
} catch {
    $runError = Safe $_.Exception.Message
} finally {
    Stop-Child
    try {
        if ($manifest -and (Test-Path -LiteralPath $manifestFile)) { $cleanupResult = Invoke-Fixture 'cleanup' $manifestFile }
    } catch { $cleanupError = Safe $_.Exception.Message }
    try { $profilesRemoved = Remove-OwnedProfile } catch { $profilesRemoved = $false; $cleanupError = Safe $_.Exception.Message }
    $cleanup = [ordered]@{
        complete = ($null -eq $cleanupError -and (($null -eq $manifest) -or ($cleanupResult.courseRemoved -and $cleanupResult.slideshowConfigRestored)) -and $profilesRemoved)
        courseRemoved = if ($cleanupResult) { $cleanupResult.courseRemoved } else { $null }
        slideshowConfigRestored = if ($cleanupResult) { $cleanupResult.slideshowConfigRestored } else { $null }
        profilesRemoved = $profilesRemoved; cleanupError = $cleanupError
    }
    Set-Content -LiteralPath $cleanupFile -Value ($cleanup | ConvertTo-Json -Depth 20) -Encoding UTF8
    $status = if ($DiscoveryOnly -and $childExitCode -eq 0) { 'discovery-pass' } elseif ($childExitCode -eq 0 -and $cleanup.complete) { 'pass' } else { 'fail' }
    Set-Content -LiteralPath $summaryFile -Value (([ordered]@{ runId = $runId; status = $status; cleanup = $cleanup; error = $runError; artifactDirectory = $runRoot } | ConvertTo-Json -Depth 20)) -Encoding UTF8
    foreach ($name in ($loadedEnvironment | Select-Object -Unique)) { Remove-Item -LiteralPath ('Env:' + $name) -ErrorAction SilentlyContinue }
    if ($nodePathWasSet) { $env:NODE_PATH = $originalNodePath } else { Remove-Item Env:NODE_PATH -ErrorAction SilentlyContinue }
    if ($lease) { try { Release-EasyEduResourceLease -Resource 'moodle51-active-fixture-write' -RunId $runId -Force } catch { } }
}

if ($childExitCode -ne 0 -or -not (Test-Path -LiteralPath $cleanupFile)) { exit 1 }
exit 0
