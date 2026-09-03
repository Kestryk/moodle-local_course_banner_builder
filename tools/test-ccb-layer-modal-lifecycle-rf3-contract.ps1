param(
    [string]$PluginRoot = (Split-Path -Parent $PSScriptRoot)
)

$ErrorActionPreference = 'Stop'

$source = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'amd/src/admin_manage.js')
$build = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'amd/build/admin_manage.min.js')
$scss = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'scss/components/_modal-preview-actions.scss')
$css = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'styles.css')

$checks = [ordered]@{
    'Cached responses retain one bounded visible loading interval' =
        $source.Contains('localCourseBannerBuilderLayerModalMinimumLoadingTime = 180') -and
        $source.Contains('localCourseBannerBuilderLayerModalMinimumLoadingTime - elapsed')
    'The existing prefetch and request cache remain active' =
        $source.Contains('localCourseBannerBuilderPrefetchLayerModal') -and
        $source.Contains('localCourseBannerBuilderLayerModalRequestCache')
    'Fresh content enters a pending reveal state before insertion' =
        $source -match "targetmodal\.dataset\.layerModalRevealPending = '1';\s*targetbody\.classList\.remove\('is-content-revealed'\);\s*targetbody\.innerHTML"
    'Reveal waits for a painted frame and current reveal token' =
        $source.Contains('modal.dataset.layerModalRevealId !== revealId') -and
        $source.Contains("body.classList.add('is-content-revealed')")
    'Close and failure paths remove transient reveal state' =
        $source.Contains("removeAttribute('data-layer-modal-reveal-pending')") -and
        $source.Contains("removeAttribute('data-layer-modal-reveal-id')")
    'Pending content uses the shared reveal start geometry' =
        $scss -match '(?s)data-layer-modal-reveal-pending="1".*?opacity:\s*0\.38;.*?transform:\s*translateY\(-0\.04rem\);'
    'Reduced motion bypasses pending movement and opacity' =
        $scss -match '(?s)prefers-reduced-motion:\s*reduce.*?data-layer-modal-reveal-pending="1".*?opacity:\s*1;.*?transform:\s*none;'
    'Generated assets contain the complete lifecycle' =
        $build.Contains('layerModalRevealPending') -and
        $css.Contains('[data-layer-modal-reveal-pending="1"]')
}

$failed = @($checks.GetEnumerator() | Where-Object { -not $_.Value })
$checks.GetEnumerator() | ForEach-Object {
    $status = if ($_.Value) { 'PASS' } else { 'FAIL' }
    Write-Host "$status $($_.Key)"
}

if ($failed.Count -gt 0) {
    throw "Layer-modal lifecycle RF3 contract failed: $($failed.Key -join ', ')"
}
