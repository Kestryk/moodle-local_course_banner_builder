[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$pluginRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path
$source = Get-Content -LiteralPath (Join-Path $pluginRoot 'amd\src\admin_manage.js') -Raw
$build = Get-Content -LiteralPath (Join-Path $pluginRoot 'amd\build\admin_manage.min.js') -Raw
$scss = Get-Content -LiteralPath (Join-Path $pluginRoot 'scss\components\_admin-controls.scss') -Raw

$outlineFunctionCount = [regex]::Matches(
    $source,
    'function localCourseBannerBuilderSyncPreviewSelectionOutline\('
).Count

$checks = [ordered]@{
    'One shared selection-outline primitive owns the frame indicator' =
        $outlineFunctionCount -eq 1;
    'Source preview and Add/Edit modal previews use the shared primitive' =
        (($source -match '(?s)function localCourseBannerBuilderSyncSourcePreviewSelectionOutline\(root\).*?localCourseBannerBuilderSyncPreviewSelectionOutline\(frame, layer\)') -and
        ($source -match '(?s)function localCourseBannerBuilderSyncModalPreviewSelectionOutline\(scope\).*?localCourseBannerBuilderSyncPreviewSelectionOutline\(frame, layer\)'));
    'Overflow is detected from the rendered selected Crop bounds' =
        (($source -match 'var right = left \+ width;') -and
        ($source -match 'var bottom = top \+ height;') -and
        ($source -match '(?s)var clampedEdges = \{.*?left: left < 0.*?top: top < 0.*?right: right > frameRect\.width.*?bottom: bottom > frameRect\.height'));
    'Overflowing selection edges clamp independently to the preview frame' =
        (($source -match '(?s)var clampedEdges = \{.*?left: left < 0.*?top: top < 0.*?right: right > frameRect\.width.*?bottom: bottom > frameRect\.height') -and
        ($source -match "data-preview-selection-clamp-' \+ edge") -and
        ($source -notmatch "outline\.style\.width = frameRect\.width \+ 'px'"));
    'The generated AMD artifact contains the magnetic edge indicators' =
        (($build.Length -gt 1000) -and ($build -match 'data-preview-selection-clamp-'));
    'Selection outline keeps its border inside the frame box' =
        ($scss -match '(?s)\.local-course-banner-builder-preview-selection-outline\s*\{.*?box-sizing:\s*border-box;.*?border:\s*3px');
}

$failed = @($checks.GetEnumerator() | Where-Object { -not $_.Value })
$checks.GetEnumerator() | ForEach-Object {
    '{0}: {1}' -f ($(if ($_.Value) { 'PASS' } else { 'FAIL' })), $_.Key
}

if ($failed.Count -gt 0) {
    throw "CCB selection-frame contract failed with $($failed.Count) failed check(s)."
}
