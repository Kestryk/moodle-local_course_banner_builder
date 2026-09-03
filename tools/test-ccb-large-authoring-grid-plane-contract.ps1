param(
    [string]$PluginRoot = (Split-Path -Parent $PSScriptRoot)
)

$ErrorActionPreference = 'Stop'

$source = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'amd/src/admin_manage.js')
$build = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'amd/build/admin_manage.min.js')
$scss = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'scss/components/_easyedu-adapter.scss')
$css = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'styles.css')
$php = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'admin_manage.php')
$en = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'lang/en/local_course_banner_builder.php')
$fr = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'lang/fr/local_course_banner_builder.php')

$checks = [ordered]@{
    'One live published frame moves through an exact placeholder' =
        $source.Contains("frame.parentNode.insertBefore(mount.framePlaceholder, frame)") -and
        $source.Contains("mount.framePlaceholder.parentNode.replaceChild(mount.frame, mount.framePlaceholder)") -and
        -not $source.Contains('frame.cloneNode(')
    'Only the logical plane receives the viewport transform' =
        $scss.Contains('.local-course-banner-builder-large-workspace-plane') -and
        $scss.Contains('transform: scale(var(--local-course-banner-builder-large-workspace-zoom))') -and
        -not ($scss -match '\.local-course-banner-builder-source-preview-panel\[data-source-preview-large-workspace="1"\]\s*\{[^}]*transform:\s*scale')
    'The checkerboard is CSS-only and the published area is explicit' =
        $scss.Contains('background-size: 5rem 5rem, 5rem 5rem, 1rem 1rem, 1rem 1rem') -and
        $source.Contains("publishedLabel.textContent = localCourseBannerBuilderGetJsString('largeeditorpublishedarea'") -and
        $source.Contains("frame.setAttribute('data-large-workspace-published-frame', '1')")
    'Fit targets the published frame and centres it in the viewport' =
        $source.Contains('availableWidth / mount.frameWidth') -and
        $source.Contains('availableHeight / mount.frameHeight') -and
        $source.Contains('mount.frameOriginX + (mount.frameWidth / 2)') -and
        $source.Contains('mount.frameOriginY + (mount.frameHeight / 2)')
    'The plane follows existing layers within persisted offset bounds' =
        $source.Contains('mount.frame.querySelectorAll(''[data-source-preview-layer="1"]'')') -and
        $source.Contains('Math.max(-10 * frameWidth, minX - frameWidth)') -and
        $source.Contains('Math.min(11 * frameWidth, maxX + frameWidth)') -and
        $source.Contains('Math.max(-10 * frameHeight, minY - frameHeight)') -and
        $source.Contains('Math.min(11 * frameHeight, maxY + frameHeight)')
    'A plain pointer drag is limited to the proven empty plane' =
        $source.Contains('event.target === mount.plane || event.target === mount.stage') -and
        $source.Contains('(!mount.spacePressed && !isEmptyPlane)')
    'The classic preview is outside the new selector boundary' =
        $scss.Contains('&.local-course-banner-builder-source-chain-preview-modal--authoring') -and
        -not $scss.Contains('.local-course-banner-builder-source-preview-panel:not([data-source-preview-large-workspace="1"])')
    'Published-area text is loaded through UTF-8 language strings' =
        $php.Contains("'largeeditorpublishedarea'") -and
        $en.Contains("`$string['largeeditorpublishedarea'] = 'Published area';") -and
        $fr.Contains("`$string['largeeditorpublishedarea'] = 'Zone publiée';")
    'Generated assets contain the grid-plane contract' =
        $build.Contains('local-course-banner-builder-large-workspace-plane') -and
        $build.Contains('largeeditorpublishedarea') -and
        $css.Contains('.local-course-banner-builder-source-chain-preview-modal--authoring .local-course-banner-builder-large-workspace-plane') -and
        $css.Contains('[data-large-workspace-published-frame="1"]')
}

$failed = @($checks.GetEnumerator() | Where-Object { -not $_.Value })
$checks.GetEnumerator() | ForEach-Object {
    $status = if ($_.Value) { 'PASS' } else { 'FAIL' }
    Write-Host "$status $($_.Key)"
}

if ($failed.Count -gt 0) {
    throw "Large authoring grid-plane contract failed: $($failed.Key -join ', ')"
}
