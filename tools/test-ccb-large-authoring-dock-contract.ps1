param(
    [string]$PluginRoot = (Split-Path -Parent $PSScriptRoot)
)

$ErrorActionPreference = 'Stop'

$source = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'amd/src/admin_manage.js')
$build = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'amd/build/admin_manage.min.js')
$scss = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'scss/components/_easyedu-adapter.scss')
$css = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'styles.css')

$checks = [ordered]@{
    'The large workspace gives its flexible majority track to the scene' =
        $scss.Contains('grid-template-columns: minmax(0, 1fr) minmax(12rem, clamp(12rem, 16vw, 16rem))') -and
        $scss.Contains('grid-template-rows: minmax(18rem, 1fr) auto auto auto')
    'The right dock fills available height and scrolls independently' =
        $scss -match '(?s)\[data-source-preview-large-workspace="1"\] \.local-course-banner-builder-source-preview-controls \{.*?height: 100%;.*?overflow-x: hidden;.*?overflow-y: auto;.*?overscroll-behavior: contain;'
    'Dock action height cannot follow the container height' =
        $scss -match '(?s)\[data-source-preview-large-workspace="1"\].*?\.local-course-banner-builder-source-preview-controls.*?\.local-course-banner-builder-source-preview-button \{.*?height: var\(--local-course-banner-builder-action-height, 2\.45rem\);.*?max-height: var\(--local-course-banner-builder-action-height, 2\.45rem\);'
    'Filmstrip controls are recalculated after mount and workspace resize' =
        ([regex]::Matches($source, 'localCourseBannerBuilderUpdateSourcePreviewFilmstripNav\(filmstrip\)')).Count -ge 2
    'The single live editor and published frame contracts remain present' =
        $source.Contains('parent.insertBefore(placeholder, sourcePanel)') -and
        $source.Contains('plane.appendChild(frame)') -and
        -not $source.Contains('sourcePanel.cloneNode(true)')
    'Fit and zoom remain owned by the published frame and plane' =
        $source.Contains('availableWidth / mount.frameWidth') -and
        $scss.Contains('transform: scale(var(--local-course-banner-builder-large-workspace-zoom))')
    'Generated assets contain the dock composition' =
        $build.Contains('localCourseBannerBuilderUpdateSourcePreviewFilmstripNav') -and
        $css.Contains('grid-template-columns: minmax(0, 1fr) minmax(12rem, clamp(12rem, 16vw, 16rem))') -and
        $css.Contains('overflow-y: auto')
}

$failed = @($checks.GetEnumerator() | Where-Object { -not $_.Value })
$checks.GetEnumerator() | ForEach-Object {
    $status = if ($_.Value) { 'PASS' } else { 'FAIL' }
    Write-Host "$status $($_.Key)"
}

if ($failed.Count -gt 0) {
    throw "Large authoring dock contract failed: $($failed.Key -join ', ')"
}
