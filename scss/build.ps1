param(
    [string]$Output = "../styles.css",
    [string]$SassVersion = "1.89.2"
)

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $MyInvocation.MyCommand.Path

Push-Location $root
try {
    & npx.cmd --yes "sass@$SassVersion" "styles.scss" $Output --no-source-map
    if ($LASTEXITCODE -ne 0) {
        throw "Sass $SassVersion build failed with exit code $LASTEXITCODE."
    }
} finally {
    Pop-Location
}
