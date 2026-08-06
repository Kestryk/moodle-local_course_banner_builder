[CmdletBinding()]
param(
    [string]$PlatformRoot = 'C:\dev\easyedu-platform',
    [ValidateRange(60, 1800)][int]$WatchdogSeconds = 900,
    [ValidateSet('2FA1', '2FB1')][string]$Batch = '2FB1',
    [switch]$Simulation,
    [switch]$DiscoveryOnly
)

$ErrorActionPreference = 'Stop'
$scriptDir = Split-Path -Parent $PSCommandPath
$repository = (Resolve-Path (Join-Path $scriptDir '..\..')).Path
$runner = Join-Path $scriptDir 'run-ccb-2fb1.ps1'
$module = Join-Path $PlatformRoot 'tools\orchestration\EasyEduOrchestration.psm1'
if (-not (Test-Path -LiteralPath $module)) { throw "Orchestration module is missing: $module" }

Import-Module $module -Force -DisableNameChecking
$runId = ('ccb-{0}-{1}-{2}' -f $Batch.ToLower(), [DateTime]::UtcNow.ToString('yyyyMMddTHHmmssZ'), $PID)
$lease = $null
try {
    $lease = Acquire-EasyEduResourceLease `
        -Resource 'moodle51-active-fixture-write' `
        -ProjectNamespace 'course-banner-builder' `
        -RunId $runId `
        -Repository $repository `
        -Purpose "CCB Batch $Batch supervised Playwright validation" `
        -LeaseSeconds ([Math]::Max(1800, $WatchdogSeconds + 300)) `
        -OwnerPid $PID

    $runnerParameters = @{WatchdogSeconds = $WatchdogSeconds; Batch = $Batch}
    if ($Simulation) { $runnerParameters.Simulation = $true }
    if ($DiscoveryOnly) { $runnerParameters.DiscoveryOnly = $true }
    & $runner @runnerParameters
    $exitCode = $LASTEXITCODE
    Write-Output "CCB_RUN_EXIT_CODE=$exitCode"
    exit $exitCode
} finally {
    if ($lease) {
        Release-EasyEduResourceLease -Resource 'moodle51-active-fixture-write' -RunId $runId -Force
        Write-Output 'CCB_LEASE_RELEASED=true'
    }
}
