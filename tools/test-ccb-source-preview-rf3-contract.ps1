[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$pluginRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path

$source = Get-Content -LiteralPath (Join-Path $pluginRoot 'amd\src\admin_manage.js') -Raw
$build = Get-Content -LiteralPath (Join-Path $pluginRoot 'amd\build\admin_manage.min.js') -Raw
$adminLayout = Get-Content -LiteralPath (Join-Path $pluginRoot 'scss\components\_admin-layout.scss') -Raw
$adminControls = Get-Content -LiteralPath (Join-Path $pluginRoot 'scss\components\_admin-controls.scss') -Raw
$adapter = Get-Content -LiteralPath (Join-Path $pluginRoot 'scss\components\_easyedu-adapter.scss') -Raw
$actions = Get-Content -LiteralPath (Join-Path $pluginRoot 'scss\components\_action-contract.scss') -Raw
$css = Get-Content -LiteralPath (Join-Path $pluginRoot 'styles.css') -Raw

$checks = [ordered]@{
    'Chain children keep the shared cancellable Motion disclosure lifecycle' =
        (($source -match '(?s)function localCourseBannerBuilderSyncSourceChainRowVisibility\(animate\).*?Motion\.resize\(tableShell, apply, \{duration: Motion\.timing\.slow\}\)') -and
        ($source -match '(?s)function localCourseBannerBuilderInitSourceChainDisclosure\(\).*?localCourseBannerBuilderSyncSourceChainRowVisibility\(false\)'));
    'Layer infos disclosure disables its chevron transition for reduced motion' =
        ($actions -match '(?s)@media \(prefers-reduced-motion: reduce\)\s*\{\s*\.local-course-banner-builder-layer-details-accordion > summary::after\s*\{\s*transition: none;');
    'Inherited border and overlay labels use readable Kit text tokens' =
        (($adminLayout -match '(?s)\.local-course-banner-builder-chain-layer-label\s*\{.*?color: var\(--easyedu-text.*?font-weight: var\(--easyedu-font-weight-semibold\)') -and
        ($adminLayout -notmatch 'color: #64748b'));
    'Layer infos and overrides uses the Kit semibold control weight' =
        ($actions -match '(?s)\.local-course-banner-builder-layer-details-accordion\s*\{.*?> summary\s*\{.*?font-weight: var\(--easyedu-font-weight-semibold\)');
    'Layer miniatures use the canonical checkerboard primitive' =
        (($adminLayout -match '(?s)\.local-course-banner-builder-admin-layer-visual.*?\.local-course-banner-builder-admin-layer-overlay-frame\s*\{.*?@include easyedu\.preview-checkerboard;') -and
        ($adminLayout -notmatch 'background-position: 0 0, 0 0\.35rem'));
    'Collapse all keeps the bounded toolbar rhythm' =
        (($adminControls -match '(?s)\.local-course-banner-builder-configured-source-tools\s*\{.*?margin: 0\.85rem 0 0\.65rem;') -and
        ($adapter -match '(?s)\.local-course-banner-builder-configured-source-tools\s*\{.*?margin: 0\.85rem 0 0\.65rem;'));
    'Preview modal uses the opaque Kit context and native loading surfaces' =
        (($adapter -match '(?s)\.local-course-banner-builder-source-chain-preview-modal\s*\{.*?@include easyedu\.native-modal-loading\("\.loading-icon"\).*?\.local-course-banner-builder-source-chain-preview-modal-content\s*\{.*?@include easyedu\.context-modal-surface;.*?@include easyedu\.context-modal-variant\(primary\);') -and
        ($adapter -match 'local-course-banner-builder-source-chain-preview-feedback--error'));
    'Preview feedback is a stable Kit-styled focus target' =
        (($source -match 'local-course-banner-builder-source-chain-preview-feedback--error') -and
        ($source -match 'body\.setAttribute\(''tabindex'', ''-1''\);\s*body\.focus\(\);'));
    'Edit source remains in the footer with one Kit action gap' =
        (($source -match '(?s)if \(editUrl && footer\).*?footer\.appendChild\(actionBar\);') -and
        ($source -match 'editIcon\.className = ''icon fa fa-pen fa-fw'';') -and
        ($source -notmatch 'editIcon\.className = ''fa fa-pen me-2'';'));
    'Every modal close path uses the same source-preview cleanup' =
        (($source -match '(?s)function localCourseBannerBuilderFinishSourceChainPreviewClose\(modal\).*?sourceChainPreviewRequest = ''''.*?sourceChainPreviewFocusReturned') -and
        ($source -match 'modal\.addEventListener\(''hidden\.bs\.modal'', finishClose\)') -and
        ($source -match '(?s)function localCourseBannerBuilderForceHideModal\(modal\).*?localCourseBannerBuilderFinishSourceChainPreviewClose\(modal\)'));
    'Modal Desktop and Mobile controls remain transient to the modal instance' =
        (($source -match '(?s)function localCourseBannerBuilderSetSourcePreviewMode\(root, mode, animate\).*?var isModalPreview = !!root\.closest\(''.local-course-banner-builder-source-chain-preview-modal''\).*?if \(sourcekey && !isModalPreview\)') -and
        ($source -match '(?s)function localCourseBannerBuilderInitSourcePreviewMode\(root\).*?var isModalPreview = !!root\.closest\(''.local-course-banner-builder-source-chain-preview-modal''\).*?!isModalPreview && sourcekey'));
    'Generated AMD and CSS contain the RF3 source-preview contracts' =
        (($build -match 'sourceChainPreviewFocusReturned') -and
        ($build -match 'local-course-banner-builder-source-chain-preview-feedback--error') -and
        ($css -match 'local-course-banner-builder-source-chain-preview-feedback--error') -and
        ($css -match 'prefers-reduced-motion: reduce'));
}

$failed = @($checks.GetEnumerator() | Where-Object { -not $_.Value })
$checks.GetEnumerator() | ForEach-Object {
    '{0}: {1}' -f ($(if ($_.Value) { 'PASS' } else { 'FAIL' })), $_.Key
}

if ($failed.Count -gt 0) {
    throw "CCB source-preview RF3 contract failed with $($failed.Count) failed check(s)."
}
