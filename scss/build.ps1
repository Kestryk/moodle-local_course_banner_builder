param(
    [string]$Output = "../styles.css"
)

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $MyInvocation.MyCommand.Path

Push-Location $root
try {
    sass "styles.scss" $Output --no-source-map
} finally {
    Pop-Location
}
