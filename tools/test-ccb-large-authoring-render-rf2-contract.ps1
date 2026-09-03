param(
    [string]$PluginRoot = (Split-Path -Parent $PSScriptRoot)
)

$ErrorActionPreference = 'Stop'

$source = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'amd/src/admin_manage.js')
$build = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'amd/build/admin_manage.min.js')

$checks = [ordered]@{
    'Desktop banner geometry uses the authoritative format ratios' =
        $source.Contains('var desktopRatios = {') -and
        $source.Contains('fullwidthtopcompact: 8') -and
        $source.Contains('fullwidthtopinset: 6.1') -and
        $source.Contains('var stableDesktopWidth = 1600') -and
        $source.Contains('var stableDesktopHeight = stableDesktopWidth / desktopRatio')
    'Desktop and mobile preview modes keep distinct stable frame geometry' =
        $source.Contains('function localCourseBannerBuilderSyncLargeWorkspaceFrameMode(mount)') -and
        $source.Contains('mount.mobileFrameWidth : mount.desktopFrameWidth') -and
        $source.Contains('mount.mobileFrameHeight : mount.desktopFrameHeight') -and
        $source.Contains('localCourseBannerBuilderSyncLargeWorkspaceFrameMode(mount)')
    'Published geometry is materialised instead of relying only on inherited variables' =
        $source.Contains("mount.frame.style.left = mount.frameOriginX + 'px'") -and
        $source.Contains("mount.frame.style.width = mount.frameWidth + 'px'") -and
        $source.Contains("mount.plane.style.width = mount.planeWidth + 'px'")
    'Transient hidden layout cannot overwrite canonical frame geometry' =
        $source.Contains('var frameWidth = Math.max(1, mount.frameWidth || mount.desktopFrameWidth || 1)') -and
        $source.Contains('frameRect.width < minimumMeasuredWidth') -and
        $source.Contains('return false;') -and
        -not $source.Contains('mount.frameWidth = frameWidth;')
    'Fit waits for usable modal geometry and retries after show' =
        $source.Contains('viewportWidth < 64 || viewportHeight < 64') -and
        $source.Contains('function localCourseBannerBuilderScheduleLargeWorkspaceFit(mount)') -and
        $source.Contains("modal.addEventListener('shown.bs.modal', mount.fitAfterShow, {once: true})") -and
        $source.Contains('mount.resizeObserver.observe(mount.viewport)') -and
        $source.Contains('mount.resizeObserver.observe(mount.panel)')
    'Fit and 100 percent recenter the canonical published frame' =
        $source.Contains('function localCourseBannerBuilderCenterLargeWorkspaceFrame(mount)') -and
        ([regex]::Matches($source, 'localCourseBannerBuilderCenterLargeWorkspaceFrame\(mount\);')).Count -ge 2
    'A scaled plane smaller than the viewport remains centred and visible' =
        $source.Contains('Math.floor((stageWidth - scaledPlaneWidth) / 2)') -and
        $source.Contains('Math.floor((stageHeight - scaledPlaneHeight) / 2)') -and
        $source.Contains('(mount.planeOffsetX || 0)') -and
        $source.Contains('(mount.planeOffsetY || 0)')
    'The original frame style is restored exactly on close' =
        $source.Contains("frameInlineStyle: frame ? frame.getAttribute('style') : null") -and
        $source.Contains("mount.frame.setAttribute('style', mount.frameInlineStyle)")
    'Focus return no longer waits after the modal has finished closing' =
        $source.Contains("opener.focus({preventScroll: true})") -and
        $source -match '(?s)opener\.focus\(\{preventScroll: true\}\).*?\}, 0\);'
    'Generated AMD contains the render repair' =
        $build.Contains('fullwidthtopcompact') -and
        $build.Contains('shown.bs.modal') -and
        $build.Contains('planeOffsetX') -and
        $build.Contains('frameInlineStyle')
}

$failed = @($checks.GetEnumerator() | Where-Object { -not $_.Value })
$checks.GetEnumerator() | ForEach-Object {
    $status = if ($_.Value) { 'PASS' } else { 'FAIL' }
    Write-Host "$status $($_.Key)"
}

if ($failed.Count -gt 0) {
    throw "Large authoring RF2 render contract failed: $($failed.Key -join ', ')"
}
