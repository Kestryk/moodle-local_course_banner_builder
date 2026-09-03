param(
    [string]$PluginRoot = (Split-Path -Parent $PSScriptRoot)
)

$ErrorActionPreference = 'Stop'

$source = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'amd/src/admin_manage.js')
$build = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'amd/build/admin_manage.min.js')
$php = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'admin_manage.php')
$scss = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'scss/components/_easyedu-adapter.scss')
$css = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'styles.css')
$en = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'lang/en/local_course_banner_builder.php')
$fr = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'lang/fr/local_course_banner_builder.php')

$checks = [ordered]@{
    'Zoom is bounded from 25 to 400 percent' =
        $source.Contains('localCourseBannerBuilderLargeWorkspaceMinZoom = 25') -and
        $source.Contains('localCourseBannerBuilderLargeWorkspaceMaxZoom = 400')
    'The large workspace opens in Fit mode' =
        $source.Contains('localCourseBannerBuilderFitLargeWorkspace(mount)') -and
        $source.Contains('localCourseBannerBuilderSetLargeWorkspaceZoom(mount, fitZoom, true)')
    'Fit and 100 percent remain view-only controls' =
        $source.Contains("localCourseBannerBuilderCreateLargeWorkspaceButton('fit'") -and
        $source.Contains("localCourseBannerBuilderCreateLargeWorkspaceButton('actual'") -and
        -not $source.Contains('largeWorkspaceSave') -and
        -not $source.Contains('largeWorkspacePayload')
    'Space drag pans the viewport before Crop sees the pointer' =
        $source.Contains('if (localCourseBannerBuilderStartLargeWorkspacePan(e))') -and
        $source.Contains('mount.viewport.scrollLeft = mount.pan.startScrollLeft - deltaX') -and
        $source.Contains('mount.viewport.scrollTop = mount.pan.startScrollTop - deltaY')
    'Closing removes transient viewport listeners and state' =
        $source.Contains('mount.cleanupViewport()') -and
        $source.Contains("mount.panel.removeAttribute('data-large-workspace-zoom')") -and
        $source.Contains("document.removeEventListener('keydown', onKeyDown, true)")
    'Viewport styling is limited to the authoring modal' =
        $scss.Contains('&.local-course-banner-builder-source-chain-preview-modal--authoring') -and
        $scss.Contains('.local-course-banner-builder-large-workspace-viewport') -and
        $scss.Contains('.local-course-banner-builder-large-workspace-plane') -and
        $scss.Contains('transform: scale(var(--local-course-banner-builder-large-workspace-zoom))') -and
        -not ($scss -match '\.local-course-banner-builder-source-preview-panel\[data-source-preview-large-workspace="1"\]\s*\{[^}]*transform:\s*scale')
    'Every view control is localised in English and French' =
        $php.Contains("'largeeditorviewcontrols'") -and
        $en.Contains("`$string['largeeditorpanhint']") -and
        $en.Contains("`$string['largeeditorzoomfit']") -and
        $fr.Contains("`$string['largeeditorpanhint']") -and
        $fr.Contains("`$string['largeeditorzoomfit']")
    'Generated AMD and CSS contain the viewport contract' =
        $build.Contains('local-course-banner-builder-large-workspace-viewport') -and
        $build.Contains('largeeditorzoomfit') -and
        $css.Contains('.local-course-banner-builder-source-chain-preview-modal--authoring .local-course-banner-builder-large-workspace-viewport')
}

$failed = @($checks.GetEnumerator() | Where-Object { -not $_.Value })
$checks.GetEnumerator() | ForEach-Object {
    $status = if ($_.Value) { 'PASS' } else { 'FAIL' }
    Write-Host "$status $($_.Key)"
}

if ($failed.Count -gt 0) {
    throw "Large authoring viewport contract failed: $($failed.Key -join ', ')"
}
