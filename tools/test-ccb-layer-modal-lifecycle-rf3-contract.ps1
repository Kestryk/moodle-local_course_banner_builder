param(
    [string]$PluginRoot = (Split-Path -Parent $PSScriptRoot)
)

$ErrorActionPreference = 'Stop'

$source = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'amd/src/admin_manage.js')
$build = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'amd/build/admin_manage.min.js')
$checks = [ordered]@{
    'Cached responses retain the shared fast loading interval' =
        $source.Contains('localCourseBannerBuilderLayerModalMinimumLoadingTime = Motion.timing.fast') -and
        $source.Contains('localCourseBannerBuilderLayerModalMinimumLoadingTime - elapsed')
    'Reduced or disabled motion bypasses the loading hold' =
        $source.Contains('Motion.isEnabled(requestedModal)') -and
        $source.Contains('var remaining = shouldHoldLoader ? Math.max(')
    'The existing prefetch and request cache remain active' =
        $source.Contains('localCourseBannerBuilderPrefetchLayerModal') -and
        $source.Contains('localCourseBannerBuilderLayerModalRequestCache')
    'Fresh content consumes the shared Motion swap entrance' =
        $source -match '(?s)Motion\.swap\(body,.*?exit:\s*false,.*?resize:\s*false,.*?enterDuration:\s*Motion\.timing\.normal,.*?distance:\s*''0\.15rem'',.*?swapOpacity:\s*0\.28'
    'Reveal mutation remains guarded by the current token' =
        $source.Contains('modal.dataset.layerModalRevealId === revealId') -and
        $source.Contains('body.innerHTML = html')
    'Close and failure paths clear or cancel transient reveal state' =
        $source.Contains("removeAttribute('data-layer-modal-phase')") -and
        $source.Contains('Motion.cancel(body)') -and
        $source.Contains("removeAttribute('data-layer-modal-reveal-id')")
    'The superseded CSS reveal lifecycle is absent' =
        -not $source.Contains('is-content-revealed') -and
        -not $source.Contains('layerModalRevealPending')
    'Generated AMD contains the shared lifecycle' =
        $build.Contains('layerModalPhase') -and
        $build.Contains('.swap(') -and
        $build.Contains('.isEnabled(')
}

$failed = @($checks.GetEnumerator() | Where-Object { -not $_.Value })
$checks.GetEnumerator() | ForEach-Object {
    $status = if ($_.Value) { 'PASS' } else { 'FAIL' }
    Write-Host "$status $($_.Key)"
}

if ($failed.Count -gt 0) {
    throw "Layer-modal lifecycle RF3 contract failed: $($failed.Key -join ', ')"
}
