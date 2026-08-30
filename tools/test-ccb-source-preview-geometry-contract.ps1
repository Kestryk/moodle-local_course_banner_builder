[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$pluginRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path
$javascript = Get-Content -LiteralPath (Join-Path $pluginRoot 'amd\src\admin_manage.js') -Raw
$manager = Get-Content -LiteralPath (Join-Path $pluginRoot 'classes\manager.php') -Raw

$checks = [ordered]@{
    'Active Crop refreshes its visible selection after a proportion toggle' =
        ($javascript -match '(?s)previewCropEditLayout === .1.*?localCourseBannerBuilderRefreshCropEditor\(layer\)');
    'Selected-source exporter resolves the public banner format' =
        ($manager -match '(?s)function export_source_visual_editor_definition\(\\stdClass \$source\).*?\$bannerformat = self::is_site_source\(\$source\)');
    'Selected-source exporter passes that format aspect to direct image layers' =
        ($manager -match 'export_modal_preview_image_layer\(\$record, \$fitmode, false, false, \$banneraspect\)');
}

$failed = @($checks.GetEnumerator() | Where-Object { -not $_.Value })
$checks.GetEnumerator() | ForEach-Object { '{0}: {1}' -f ($(if ($_.Value) {'PASS'} else {'FAIL'})), $_.Key }
if ($failed.Count -gt 0) { throw "Source-preview geometry contract failed with $($failed.Count) check(s)." }
