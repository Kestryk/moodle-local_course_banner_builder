[CmdletBinding()]
param(
    [switch]$DiscoveryOnly,
    [ValidateRange(120, 1800)]
    [int]$WatchdogSeconds = 600,
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
$playwrightSpec = Join-Path $scriptDir 'ccb-parent-source-modal.spec.js'
$fixtureHelper = Join-Path $scriptDir 'ccb-parent-source-modal-fixture.php'
$credentialLoader = Join-Path $scriptDir 'Use-CCBMoodle51Credentials.ps1'
$orchestrationModule = 'C:\dev\easyedu-platform\tools\orchestration\EasyEduOrchestration.psm1'
$artifactBase = if ($env:EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT) {
    $env:EASYEDU_PLAYWRIGHT_ARTIFACTS_ROOT
} else {
    Join-Path $env:LOCALAPPDATA 'EasyEdu\artifacts'
}
$runId = 'ccb-parent-source-modal-{0}-{1}' -f [DateTime]::UtcNow.ToString('yyyyMMddTHHmmssfffZ'), $PID
$runRoot = Join-Path ([IO.Path]::GetFullPath($artifactBase)) ('ccb\parent-source-modal\supervised\' + $runId)
$profile = Join-Path $runRoot 'profile'
$manifestFile = Join-Path $runRoot 'fixture-manifest.json'
$cleanupFile = Join-Path $runRoot 'cleanup.json'
$runnerResultFile = Join-Path $runRoot 'runner-result.json'
$child = $null
$lease = $null
$setup = $null
$exitCode = 70
$loadedEnvironment = @()

function Invoke-Fixture([string]$Command, [string]$Argument = '') {
    $arguments = @('-f', $fixtureHelper, '--', $Command)
    if ($Argument) { $arguments += $Argument }
    $lines = @(& $moodlePhp @arguments 2>&1)
    if ($LASTEXITCODE -ne 0) { throw ($lines -join "`n") }
    $json = $lines | Where-Object { $_ -match '^\s*\{' } | Select-Object -Last 1
    if (!$json) { throw "Parent-source fixture returned no JSON for $Command." }
    return ($json | ConvertFrom-Json)
}

function Acquire-ValidationLease {
    $deadline = (Get-Date).AddSeconds($WaitForLeaseSeconds)
    do {
        try {
            return Acquire-EasyEduResourceLease -Resource 'moodle51-active-fixture-write' `
                -ProjectNamespace 'ccb' -RunId $runId -Repository $pluginRoot `
                -Purpose 'CCB parent-source modal validation fixture' `
                -LeaseSeconds ([Math]::Max(180, $WatchdogSeconds + 120))
        } catch {
            if ($WaitForLeaseSeconds -le 0 -or $_.Exception.Message -notmatch 'already leased' -or (Get-Date) -ge $deadline) {
                throw
            }
            Start-Sleep -Seconds 3
        }
    } while ((Get-Date) -lt $deadline)
    throw 'Timed out waiting for the shared Moodle fixture lease.'
}

New-Item -ItemType Directory -Path $runRoot -Force | Out-Null

try {
    foreach ($required in @($moodlePhp, $playwrightCli, $playwrightConfig, $playwrightSpec, $fixtureHelper,
            $credentialLoader, $orchestrationModule)) {
        if (-not (Test-Path -LiteralPath $required)) { throw "Required validation file is missing: $required" }
    }
    if ([IO.Path]::GetFullPath($runRoot).StartsWith(
            [IO.Path]::GetFullPath($pluginRoot) + [IO.Path]::DirectorySeparatorChar,
            [StringComparison]::OrdinalIgnoreCase)) {
        throw 'Parent-source artifact root resolves inside the CCB repository.'
    }

    $env:EASYEDU_CCB_PARENT_SOURCE_CHILD_KEY = 'discovery-child'
    $env:EASYEDU_CCB_PARENT_SOURCE_VALID_KEY = 'discovery-valid'
    $env:EASYEDU_CCB_PARENT_SOURCE_DESCENDANT_KEY = 'discovery-descendant'
    $env:EASYEDU_CCB_PARENT_SOURCE_ARTIFACT_ROOT = $runRoot
    $loadedEnvironment += @(
        'EASYEDU_CCB_PARENT_SOURCE_CHILD_KEY',
        'EASYEDU_CCB_PARENT_SOURCE_VALID_KEY',
        'EASYEDU_CCB_PARENT_SOURCE_DESCENDANT_KEY',
        'EASYEDU_CCB_PARENT_SOURCE_ARTIFACT_ROOT'
    )

    $discovery = @(& node.exe $playwrightCli test $playwrightSpec.Replace('\', '/') `
        ('--config=' + $playwrightConfig.Replace('\', '/')) --list 2>&1) -join "`n"
    Set-Content -LiteralPath (Join-Path $runRoot 'playwright.discovery.txt') -Value $discovery -Encoding UTF8
    if ($LASTEXITCODE -ne 0 -or $discovery -notmatch 'Total:\s+1\s+test\s+in\s+1\s+file') {
        throw 'Playwright discovery did not select exactly one parent-source modal test.'
    }
    if ($DiscoveryOnly) { $exitCode = 0; return }

    Import-Module -Name $orchestrationModule -Force
    $lease = Acquire-ValidationLease
    $setup = Invoke-Fixture 'setup'
    $setup | ConvertTo-Json -Depth 10 | Set-Content -LiteralPath $manifestFile -Encoding UTF8

    . $credentialLoader | Out-Null
    $loadedEnvironment += @(
        'EASYEDU_MOODLE_URL', 'EASYEDU_MOODLE_USERNAME', 'EASYEDU_MOODLE_PASSWORD',
        'CCB_MOODLE_URL', 'CCB_MOODLE_USERNAME', 'CCB_MOODLE_PASSWORD'
    )
    $env:EASYEDU_CCB_PARENT_SOURCE_CHILD_KEY = [string]$setup.childKey
    $env:EASYEDU_CCB_PARENT_SOURCE_VALID_KEY = [string]$setup.validKey
    $env:EASYEDU_CCB_PARENT_SOURCE_DESCENDANT_KEY = [string]$setup.descendantKey

    $startInfo = [Diagnostics.ProcessStartInfo]::new()
    $startInfo.FileName = 'node.exe'
    $startInfo.WorkingDirectory = $scriptDir
    $startInfo.UseShellExecute = $false
    $startInfo.CreateNoWindow = $true
    $startInfo.RedirectStandardOutput = $true
    $startInfo.RedirectStandardError = $true
    $arguments = @(
        $playwrightCli, 'test', $playwrightSpec.Replace('\', '/'),
        ('--config=' + $playwrightConfig.Replace('\', '/')), '--reporter=line', '--workers=1', '--retries=0'
    )
    $startInfo.Arguments = (($arguments | ForEach-Object { '"' + ($_ -replace '"', '\"') + '"' }) -join ' ')
    $child = [Diagnostics.Process]::new()
    $child.StartInfo = $startInfo
    if (-not $child.Start()) { throw 'Unable to start the Playwright Node process.' }
    if (-not $child.WaitForExit($WatchdogSeconds * 1000)) {
        & taskkill.exe /PID $child.Id /T /F 2>$null | Out-Null
        $exitCode = 124
    } else {
        $exitCode = $child.ExitCode
    }
    Set-Content -LiteralPath (Join-Path $runRoot 'playwright.stdout.txt') -Value $child.StandardOutput.ReadToEnd() -Encoding UTF8
    Set-Content -LiteralPath (Join-Path $runRoot 'playwright.stderr.txt') -Value $child.StandardError.ReadToEnd() -Encoding UTF8
} catch {
    Set-Content -LiteralPath (Join-Path $runRoot 'supervisor-error.txt') -Value $_.Exception.Message -Encoding UTF8
} finally {
    if ($child -and -not $child.HasExited) {
        try { & taskkill.exe /PID $child.Id /T /F 2>$null | Out-Null } catch { }
    }
    $cleanupResult = $null
    $cleanupError = $null
    if ($setup -and (Test-Path -LiteralPath $manifestFile)) {
        try { $cleanupResult = Invoke-Fixture 'cleanup' $manifestFile } catch { $cleanupError = $_.Exception.Message }
    }
    if (Test-Path -LiteralPath $profile) {
        Remove-Item -LiteralPath $profile -Recurse -Force
    }
    if ($lease) {
        try { Release-EasyEduResourceLease -Resource 'moodle51-active-fixture-write' -RunId $runId -Force } catch { }
    }
    foreach ($name in ($loadedEnvironment | Select-Object -Unique)) {
        Remove-Item -LiteralPath ('Env:' + $name) -ErrorAction SilentlyContinue
    }
    $cleanup = [ordered]@{
        complete = ($null -eq $cleanupError -and (($null -eq $setup) -or ($cleanupResult -and $cleanupResult.categoriesRemoved)))
        categoriesRemoved = if ($cleanupResult) { $cleanupResult.categoriesRemoved } else { $null }
        remainingCategories = if ($cleanupResult) { $cleanupResult.remainingCategories } else { $null }
        cleanupError = $cleanupError
    }
    $cleanup | ConvertTo-Json -Depth 10 | Set-Content -LiteralPath $cleanupFile -Encoding UTF8
    [ordered]@{
        runId = $runId
        status = if ($exitCode -eq 0 -and $cleanup.complete) { 'pass' } else { 'fail' }
        childExitCode = $exitCode
        artifactDirectory = $runRoot
    } | ConvertTo-Json -Depth 10 | Set-Content -LiteralPath $runnerResultFile -Encoding UTF8
}

if ($exitCode -ne 0) { exit 1 }
