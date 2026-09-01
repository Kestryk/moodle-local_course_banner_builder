[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$pluginRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path
$template = Get-Content -LiteralPath (Join-Path $pluginRoot 'templates\admin_manage.mustache') -Raw
$styles = Get-Content -LiteralPath (Join-Path $pluginRoot 'scss\components\_easyedu-adapter.scss') -Raw
$compiled = Get-Content -LiteralPath (Join-Path $pluginRoot 'styles.css') -Raw
$spec = Get-Content -LiteralPath (Join-Path $pluginRoot 'tools\playwright\ccb-parent-source-modal.spec.js') -Raw

$genericFooterIndex = $compiled.LastIndexOf('.modal[id^=local-course-banner-builder-] .modal-footer > .btn')
$parentSaveIndex = $compiled.LastIndexOf(
    '.modal[id^=local-course-banner-builder-] .local-course-banner-builder-parent-source-save'
)

$checks = [ordered]@{
    'Parent modal uses only the shared close-control class' = $template -match 'class="local-course-banner-builder-modal__close"';
    'Parent modal uses the canonical close icon instead of a raw x' =
        $template -match 'class="fa fa-times"' -and $template -notmatch '&times;';
    'Parent menu remains an in-modal flow disclosure' = $styles -match '(?s)\[data-source-dropdown="parent-change"\].+?position: static !important;';
    'Parent modal body owns vertical scrolling' = $styles -match '#local-course-banner-builder-change-source-parent-modal[\s\S]*\.modal-body \{[\s\S]*overflow-y: auto;';
    'Close control uses the shared Kit primitive' = $styles -match '\.local-course-banner-builder-modal__close \{[\s\S]*@include easyedu\.modal-close-button\(small\);';
    'Portalled Parent Save consumes the existing shared Kit primitive' =
        $styles -match '(?s)\.modal\[id\^="local-course-banner-builder-"\].+?\.local-course-banner-builder-parent-source-save\s*\{\s*@include easyedu\.save-action-button;';
    'Compiled Parent Save cascade follows generic modal-footer geometry' =
        $genericFooterIndex -ge 0 -and $parentSaveIndex -gt $genericFooterIndex;
    'Parent Save is no longer trapped below the administration root' =
        $styles -notmatch '(?s)\.local-course-banner-builder-compact-save-button,\s*\.local-course-banner-builder-parent-source-save,';
    'Scenario proves the menu remains within the modal surface' = $spec -match '(?s)dropdownGeometry\[1\]\.y \+ dropdownGeometry\[1\]\.height\).*?modalBox\.y \+ modalBox\.height';
    'Scenario proves shared close centring and Save hover states' =
        $spec -match 'closeGeometry\.iconCenterOffset' -and
        $spec -match 'saveHover\.backgroundColor.*saveHover\.referenceBackground';
}

$failed = @($checks.GetEnumerator() | Where-Object { -not $_.Value })
$checks.GetEnumerator() | ForEach-Object { '{0}: {1}' -f ($(if ($_.Value) {'PASS'} else {'FAIL'})), $_.Key }
if ($failed.Count -gt 0) {
    throw "Parent-source modal contract failed with $($failed.Count) check(s)."
}
