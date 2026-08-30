[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$pluginRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path
$template = Get-Content -LiteralPath (Join-Path $pluginRoot 'templates\admin_manage.mustache') -Raw
$styles = Get-Content -LiteralPath (Join-Path $pluginRoot 'scss\components\_easyedu-adapter.scss') -Raw
$spec = Get-Content -LiteralPath (Join-Path $pluginRoot 'tools\playwright\ccb-parent-source-modal.spec.js') -Raw

$checks = [ordered]@{
    'Parent modal uses the shared close-control class' = $template -match 'class="close local-course-banner-builder-modal__close"';
    'Parent menu remains an in-modal flow disclosure' = $styles -match '(?s)\[data-source-dropdown="parent-change"\].+?position: static !important;';
    'Parent modal body owns vertical scrolling' = $styles -match '#local-course-banner-builder-change-source-parent-modal[\s\S]*\.modal-body \{[\s\S]*overflow-y: auto;';
    'Close control uses the shared Kit primitive' = $styles -match '\.local-course-banner-builder-modal__close \{[\s\S]*@include easyedu\.modal-close-button\(small\);';
    'Scenario proves the menu remains within the modal surface' = $spec -match '(?s)dropdownGeometry\[1\]\.y \+ dropdownGeometry\[1\]\.height\).*?modalBox\.y \+ modalBox\.height';
}

$failed = @($checks.GetEnumerator() | Where-Object { -not $_.Value })
$checks.GetEnumerator() | ForEach-Object { '{0}: {1}' -f ($(if ($_.Value) {'PASS'} else {'FAIL'})), $_.Key }
if ($failed.Count -gt 0) {
    throw "Parent-source modal contract failed with $($failed.Count) check(s)."
}
