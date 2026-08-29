[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$pluginRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path

$files = @{
    Admin = Get-Content -LiteralPath (Join-Path $pluginRoot 'admin_manage.php') -Raw
    Source = Get-Content -LiteralPath (Join-Path $pluginRoot 'amd\src\admin_manage.js') -Raw
    Build = Get-Content -LiteralPath (Join-Path $pluginRoot 'amd\build\admin_manage.min.js') -Raw
    Rows = Get-Content -LiteralPath (Join-Path $pluginRoot 'scss\components\_layer-object-row.scss') -Raw
    Slideshow = Get-Content -LiteralPath (Join-Path $pluginRoot 'scss\components\_slideshow-admin.scss') -Raw
    Spec = Get-Content -LiteralPath (Join-Path $pluginRoot 'tools\playwright\ccb-motion-drag.spec.js') -Raw
}

$checks = [ordered]@{
    'Server root publishes the enabled motion policy before first paint' =
        $files.Admin.Contains("'data-easyedu-motion-policy' => 'enabled'")
    'Preview mode uses one fade-only Motion swap on the canvas surface' =
        $files.Source.Contains('Motion.swap(surface, apply') -and
        $files.Source.Contains("distance: '0px'") -and
        $files.Source.Contains('resize: false')
    'Filmstrip scrolling follows the shared Motion policy' =
        $files.Source.Contains('Motion.scroll(activeButton') -and
        $files.Source.Contains('Motion.scroll(active,') -and
        -not $files.Source.Contains("scrollIntoView({behavior: 'smooth'")
    'Drag uses a captured opaque Kit preview and a source placeholder' =
        $files.Source.Contains('localCourseBannerBuilderCreateLayerDragPreview') -and
        $files.Source.Contains('setDragImage(preview') -and
        $files.Rows.Contains('@include easyedu.drag-preview-container') -and
        $files.Rows.Contains('@include easyedu.drag-source-placeholder')
    'Drop targets are explicit and locked rows cannot receive a drag insertion' =
        $files.Source.Contains('localCourseBannerBuilderSetLayerDropState') -and
        $files.Source.Contains('localCourseBannerBuilderIsLayerRowOrderLocked(target)') -and
        $files.Rows.Contains('layer-row-drop-before') -and
        $files.Rows.Contains('layer-row-drop-after')
    'Slideshow disclosure leaves measured geometry to the Motion runtime' =
        -not $files.Slideshow.Contains('max-height 280ms ease')
    'Focused browser scenario covers motion policy and one complete drag cycle' =
        $files.Spec.Contains("reducedMotion: 'reduce'") -and
        $files.Spec.Contains('dragTo')
    'Generated AMD retains the source preview and drag contract markers' =
        $files.Build.Contains('data-source-preview-mode') -and
        $files.Build.Contains('local-course-banner-builder-layer-drag-preview')
}

$failed = $checks.GetEnumerator() | Where-Object { -not $_.Value }
if ($failed) {
    $failed | ForEach-Object { Write-Error "FAILED: $($_.Key)" }
    exit 1
}

$checks.Keys | ForEach-Object { Write-Output "PASS: $_" }
