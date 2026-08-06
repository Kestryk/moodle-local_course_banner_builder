[CmdletBinding()]
param(
    [Parameter(Mandatory)]
    [string]$Spec,
    [string]$Grep,
    [string[]]$PlaywrightArgument = @()
)

$ErrorActionPreference = 'Stop'
$scriptDir = Split-Path -Parent $PSCommandPath
$playwrightRoot = (Resolve-Path -LiteralPath $scriptDir).Path
$cli = Join-Path $playwrightRoot 'node_modules\@playwright\test\cli.js'
$config = Join-Path $playwrightRoot 'playwright.config.js'

foreach ($requiredPath in @($cli, $config)) {
    if (-not (Test-Path -LiteralPath $requiredPath)) {
        throw "Required Playwright file is missing: $requiredPath"
    }
}

$resolvedSpec = (Resolve-Path -LiteralPath (Join-Path $playwrightRoot $Spec) -ErrorAction Stop).Path
$specArgument = $resolvedSpec.Substring($playwrightRoot.Length).TrimStart('\', '/')
if ([string]::IsNullOrWhiteSpace($specArgument)) {
    throw "Spec must resolve to a file below the Playwright root: $Spec"
}

# Dot-sourcing is intentional: the loader must populate this wrapper process so
# the Node child inherits the variables, while the parent PowerShell stays clean.
. (Join-Path $scriptDir 'Use-CCBMoodle51Credentials.ps1')

try {
    $arguments = @(
        $cli,
        'test',
        $specArgument,
        ('--config=' + $config)
    )
    if ($Grep) { $arguments += ('--grep=' + $Grep) }
    if ($PlaywrightArgument) { $arguments += $PlaywrightArgument }

    # Keep absolute paths with spaces intact on Windows PowerShell/.NET.
    $startInfo = [Diagnostics.ProcessStartInfo]::new()
    $startInfo.FileName = 'node.exe'
    $startInfo.WorkingDirectory = $playwrightRoot
    $startInfo.UseShellExecute = $false
    $startInfo.CreateNoWindow = $true
    $startInfo.RedirectStandardOutput = $false
    $startInfo.RedirectStandardError = $false
    $startInfo.Arguments = (($arguments | ForEach-Object {
        '"' + ($_ -replace '"', '\\"') + '"'
    }) -join ' ')

    $process = [Diagnostics.Process]::new()
    $process.StartInfo = $startInfo
    if (-not $process.Start()) { throw 'Unable to start the Playwright Node process.' }
    $process.WaitForExit()
    exit $process.ExitCode
}
finally {
    foreach ($name in @(
        'EASYEDU_MOODLE_URL', 'EASYEDU_MOODLE_USERNAME', 'EASYEDU_MOODLE_PASSWORD',
        'CCB_MOODLE_URL', 'CCB_MOODLE_USERNAME', 'CCB_MOODLE_PASSWORD'
    )) {
        Remove-Item -LiteralPath ("Env:" + $name) -ErrorAction SilentlyContinue
    }
}
