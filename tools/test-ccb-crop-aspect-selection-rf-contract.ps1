[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$pluginRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path
$source = Get-Content -LiteralPath (Join-Path $pluginRoot 'amd\src\admin_manage.js') -Raw
$build = Get-Content -LiteralPath (Join-Path $pluginRoot 'amd\build\admin_manage.min.js') -Raw
$scss = Get-Content -LiteralPath (Join-Path $pluginRoot 'scss\components\_admin-controls.scss') -Raw
$css = Get-Content -LiteralPath (Join-Path $pluginRoot 'styles.css') -Raw

$checks = [ordered]@{
    'Keep proportion contains the aspect box inside the accepted Crop rectangle' =
        (($source -match '(?s)function localCourseBannerBuilderGetClosestAspectPreviewBox\(.*?heightForWidth <= heightPercent.*?width: widthPercent,\s*height: heightForWidth.*?width: widthForHeight,\s*height: heightPercent') -and
        ($source -notmatch 'keepWidthDelta = Math\.abs\(heightForWidth - heightPercent\)'));
    'Four preview edges own independent magnetic clamp states' =
        (($source -match '(?s)var clampedEdges = \{\s*left: left < 0,\s*top: top < 0,\s*right: right > frameRect\.width,\s*bottom: bottom > frameRect\.height') -and
        ($source -match "data-preview-selection-clamp-' \+ edge"));
    'Selection stroke uses one uniform inset on every preview edge' =
        (($source -match 'var strokeInset = 3;') -and
        ($source -match 'outlineRight - outlineLeft') -and
        ($source -match 'outlineBottom - outlineTop'));
    'Overflow no longer replaces one touched edge with a complete-frame outline' =
        (($source -notmatch "outline\.style\.width = frameRect\.width \+ 'px'") -and
        ($source -notmatch "outline\.style\.height = frameRect\.height \+ 'px'"));
    'Layer-local Crop selection border remains inside its image box' =
        ($scss -match '(?s)\.local-course-banner-builder-source-preview-layer--selected::after\s*\{.*?box-sizing:\s*border-box;');
    'Official generated artifacts include RF implementation' =
        (($build.Length -gt 1000) -and
        ($build -match 'data-preview-selection-clamp-') -and
        ($css -match '(?s)\.local-course-banner-builder-source-preview-layer--selected::after\s*\{.*?box-sizing:\s*border-box'));
}

$failed = @($checks.GetEnumerator() | Where-Object { -not $_.Value })
$checks.GetEnumerator() | ForEach-Object {
    '{0}: {1}' -f ($(if ($_.Value) { 'PASS' } else { 'FAIL' })), $_.Key
}

if ($failed.Count -gt 0) {
    throw "CCB Crop/aspect and magnetic-selection RF contract failed with $($failed.Count) failed check(s)."
}
