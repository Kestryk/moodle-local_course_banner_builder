[CmdletBinding()]
param(
    [string]$PluginRoot = (Split-Path -Parent $PSScriptRoot)
)

$ErrorActionPreference = 'Stop'

$source = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'amd/src/admin_manage.js')
$build = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'amd/build/admin_manage.min.js')
$php = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'admin_manage.php')
$template = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'templates/admin_manage.mustache')
$selected = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'templates/admin_selected.mustache')
$adapter = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'scss/components/_easyedu-adapter.scss')
$actions = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'scss/components/_action-contract.scss')
$css = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'styles.css')

$checks = [ordered]@{
    'Manual zoom input is bounded and empty input preserves current zoom' =
        $source.Contains("zoomInput.type = 'number'") -and
        $source.Contains('zoomInput.min = String(localCourseBannerBuilderLargeWorkspaceMinZoom)') -and
        $source.Contains('zoomInput.max = String(localCourseBannerBuilderLargeWorkspaceMaxZoom)') -and
        $source.Contains("value === '' ? mount.zoom : Number(value)")
    'Zoom endpoint buttons expose truthful disabled states and remain visible' =
        $source.Contains('mount.zoomOutButton.disabled = zoom <= localCourseBannerBuilderLargeWorkspaceMinZoom') -and
        $source.Contains('mount.zoomInButton.disabled = zoom >= localCourseBannerBuilderLargeWorkspaceMaxZoom') -and
        $source.Contains("mount.zoomInButton = localCourseBannerBuilderCreateLargeWorkspaceButton('in'")
    'Pan remains Space-owned and cannot intercept an ordinary canvas click' =
        $source.Contains('!mount.spacePressed || event.button !== 0') -and
        -not $source.Contains('directBackground')
    'First large-workspace frame synchronises selection after Fit' =
        $source -match '(?s)localCourseBannerBuilderFitLargeWorkspace\(mount\);\s*localCourseBannerBuilderSyncSourcePreviewSelectionOutline\(mount\.panel\);'
    'Parent Save refreshes configured sources and selected preview' =
        $php.Contains("`$selectedcontext['sourcevisualeditorhtml']") -and
        $source.Contains("localCourseBannerBuilderReplaceConfiguredSourcesTable(data.tablehtml || '')") -and
        $source.Contains('localCourseBannerBuilderReplaceSelectedSourceContentFromDeleteResponse')
    'Portalled Parent Save uses shared bottom-end busy feedback' =
        $source.Contains('localCourseBannerBuilderSetAsyncActionBusy(null, true, adminRoot)') -and
        $source.Contains('localCourseBannerBuilderSetAsyncActionBusy(null, false, adminRoot)')
    'Parent footer actions consume compact shared geometry after generic modal rules' =
        $template.Contains('local-course-banner-builder-parent-source-footer') -and
        $adapter -match '(?s)\.modal\[id\^="local-course-banner-builder-"\].*?\.local-course-banner-builder-parent-source-save\s*\{\s*@include easyedu\.save-action-button\(small\);.*?\.local-course-banner-builder-parent-source-footer\s*>\s*\.btn\s*\{\s*@include easyedu\.action-row-button\(small\);'
    'Both preview action families share compact typography and light states' =
        ([regex]::Matches($php, "local-course-banner-builder-source-preview-primary-action'")).Count -eq 3 -and
        ([regex]::Matches($selected, 'local-course-banner-builder-bulk-action-button')).Count -eq 3 -and
        $adapter -match '(?s)\.local-course-banner-builder-source-preview-primary-action.*?font-size:\s*0\.78rem\s*!important;.*?height:\s*2\.15rem\s*!important;.*?line-height:\s*1\.2\s*!important;.*?\.btn-danger:not\(:disabled\):hover'
    'Row Edit and Delete retain pointer cursors' =
        $actions -match '(?s)\.local-course-banner-builder-layer-actions-cell.*?:is\(button:not\(:disabled\), a\[href\]\)\s*\{\s*cursor:\s*pointer;' -and
        $adapter -match '(?s)\[data-action="local-course-banner-builder-delete-source-layer"\]\s*\{\s*cursor:\s*pointer\s*!important;'
    'Layer modal content reveals after loading with reduced-motion bypass' =
        $source.Contains('function localCourseBannerBuilderRevealLayerModalContent(modal)') -and
        $source.Contains("body.classList.add('is-content-revealed')") -and
        $adapter.Contains('@include easyedu.content-reveal(0.24s)') -and
        $adapter.Contains('@media (prefers-reduced-motion: reduce)') -and
        $css.Contains('.local-course-banner-builder-layer-modal-body.is-content-revealed')
    'Image selection outline synchronises on both post-load layout frames' =
        $source -match '(?s)function localCourseBannerBuilderRefreshLoadedLayerModal\(modal\).*?requestAnimationFrame.*?localCourseBannerBuilderSyncModalPreviewSelectionOutline\(form\).*?requestAnimationFrame.*?localCourseBannerBuilderSyncModalPreviewSelectionOutline\(form\)'
    'Generated assets contain Wave 15 hooks' =
        $build.Contains('is-content-revealed') -and
        $build.Contains('local-course-banner-builder-source-preview-primary-action') -and
        $css.Contains('local-course-banner-builder-parent-source-footer')
}

$failed = @($checks.GetEnumerator() | Where-Object { -not $_.Value })
$checks.GetEnumerator() | ForEach-Object {
    '{0}: {1}' -f ($(if ($_.Value) { 'PASS' } else { 'FAIL' })), $_.Key
}

if ($failed.Count -gt 0) {
    throw "CCB Wave 15 correction contract failed with $($failed.Count) check(s)."
}
