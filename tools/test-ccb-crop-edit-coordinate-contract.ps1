[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$pluginRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path
$source = Get-Content -LiteralPath (Join-Path $pluginRoot 'amd\src\admin_manage.js') -Raw
$build = Get-Content -LiteralPath (Join-Path $pluginRoot 'amd\build\admin_manage.min.js') -Raw

$checks = [ordered]@{
    'Standalone rendering recognises the active source-coordinate Crop canvas' =
        ($source -match "cropEditing && layer\.dataset\.previewCropEditLayout === '1'");
    'Active Crop refresh does not replace the source canvas with placement geometry' =
        ($source -match '(?s)if \(cropEditing && layer\.dataset\.previewCropEditLayout.*?localCourseBannerBuilderUpdateCropSelectionFrame\(.*?localCourseBannerBuilderRefreshCropEditor\(layer\);.*?return;.*?var effectiveDimensions');
    'The Crop invariant is present in the official AMD build' =
        (($build.Length -gt 1000) -and
        ($build -match 'previewCropEditLayout') -and
        ($build -match 'localCourseBannerBuilderRefreshCropEditor'));
}

$failed = @($checks.GetEnumerator() | Where-Object { -not $_.Value })
$checks.GetEnumerator() | ForEach-Object {
    '{0}: {1}' -f ($(if ($_.Value) { 'PASS' } else { 'FAIL' })), $_.Key
}

if ($failed.Count -gt 0) {
    throw "CCB Crop edit coordinate contract failed with $($failed.Count) failed check(s)."
}
