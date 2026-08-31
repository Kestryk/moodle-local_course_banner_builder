[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$pluginRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path
$source = Get-Content -LiteralPath (Join-Path $pluginRoot 'amd\src\admin_manage.js') -Raw
$build = Get-Content -LiteralPath (Join-Path $pluginRoot 'amd\build\admin_manage.min.js') -Raw
$map = Get-Content -LiteralPath (Join-Path $pluginRoot 'amd\build\admin_manage.min.js.map') -Raw | ConvertFrom-Json
$mapsource = $map.sourcesContent -join "`n"

$effectiveCropAspectCalls = [regex]::Matches(
    $source,
    'localCourseBannerBuilderGetEffectivePreviewImageDimensions\(\s*naturalWidth,\s*naturalHeight,\s*cropState,\s*false\s*\)'
).Count

$checks = [ordered]@{
    'Modal selection shell resolves Keep proportions from cropped dimensions' =
        ($source -match '(?s)function localCourseBannerBuilderSyncCurrentImagePreview\(scope\).*?var effectiveDimensions = localCourseBannerBuilderGetEffectivePreviewImageDimensions\(\s*naturalWidth,\s*naturalHeight,\s*cropState,\s*false\s*\)');
    'Independent draft visual resolves the same cropped dimensions' =
        ($source -match '(?s)function localCourseBannerBuilderSyncStandalonePreviewLayer\(previewRoot, layer\).*?var effectiveDimensions = localCourseBannerBuilderGetEffectivePreviewImageDimensions\(\s*naturalWidth,\s*naturalHeight,\s*cropState,\s*false\s*\)');
    'Exactly both placed render paths use the effective cropped aspect' = $effectiveCropAspectCalls -eq 2;
    'Active Crop canvas still returns before placed geometry is calculated' =
        ($source -match '(?s)function localCourseBannerBuilderSyncCurrentImagePreview\(scope\).*?previewCropEditLayout === .1.*?localCourseBannerBuilderRefreshCropEditor\(layer\);.*?return;.*?var effectiveDimensions');
    'Closed Crop selection still follows the placed outer layer' =
        ($source -match 'var showFullSelection = enabled && !layer\.querySelector\(''\[data-preview-crop-editor="1"\]''\)');
    'Official AMD build contains the shared cropped-aspect renderer' =
        (($build.Length -gt 1000) -and
        ($build -match 'define\(') -and
        ([regex]::Matches($mapsource, 'localCourseBannerBuilderGetEffectivePreviewImageDimensions').Count -ge 3) -and
        ($mapsource -match 'previewCropEditLayout'));
}

$failed = @($checks.GetEnumerator() | Where-Object { -not $_.Value })
$checks.GetEnumerator() | ForEach-Object {
    '{0}: {1}' -f ($(if ($_.Value) { 'PASS' } else { 'FAIL' })), $_.Key
}

if ($failed.Count -gt 0) {
    throw "CCB Crop selection-shell RF20 contract failed with $($failed.Count) failed check(s)."
}
