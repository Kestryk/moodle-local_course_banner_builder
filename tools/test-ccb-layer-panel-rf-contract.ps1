[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$pluginRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path
$actionScss = Get-Content -LiteralPath (Join-Path $pluginRoot 'scss\components\_action-contract.scss') -Raw
$controlScss = Get-Content -LiteralPath (Join-Path $pluginRoot 'scss\components\_admin-controls.scss') -Raw
$layoutScss = Get-Content -LiteralPath (Join-Path $pluginRoot 'scss\components\_admin-layout.scss') -Raw
$managerPhp = Get-Content -LiteralPath (Join-Path $pluginRoot 'classes\manager.php') -Raw
$css = Get-Content -LiteralPath (Join-Path $pluginRoot 'styles.css') -Raw

$checks = [ordered]@{
    'Layer info label and value own bounded columns' =
        ($actionScss -match 'grid-template-columns:\s*minmax\(0, 1fr\) minmax\(0, 1fr\);')
    'Layer info labels and values wrap instead of overlapping' =
        ($actionScss -match '(?s)\.local-course-banner-builder-border-summary-list dt,\s*\.local-course-banner-builder-border-summary-list dd\s*\{.*?overflow-wrap:\s*anywhere;')
    'Preview side actions have one invariant height' =
        (($controlScss -match 'height:\s*var\(--local-course-banner-builder-action-height, 2\.45rem\);') -and
        ($controlScss -match 'min-height:\s*var\(--local-course-banner-builder-action-height, 2\.45rem\);') -and
        ($controlScss -match 'max-height:\s*var\(--local-course-banner-builder-action-height, 2\.45rem\);'))
    'Preview side panel fills its grid track with a compact gap' =
        (($layoutScss -match 'gap:\s*clamp\(0\.55rem, 1vw, 0\.75rem\);') -and
        ($layoutScss -match '(?s)\.local-course-banner-builder-source-preview-layout\s*\{.*?align-items:\s*stretch;') -and
        ($controlScss -match '(?s)\.local-course-banner-builder-source-preview-controls\s*\{.*?align-self:\s*stretch;'))
    'Unlocked Image override cells have no generic semantic tint' =
        ($managerPhp -notmatch 'rgba\(201, 102, 26, 0\.12\)')
    'Official CSS contains the layer and panel contracts' =
        (($css -match 'grid-template-columns:\s*minmax\(0, 1fr\) minmax\(0, 1fr\)') -and
        ($css -match 'max-height:\s*var\(--local-course-banner-builder-action-height, 2\.45rem\)') -and
        ($css -match 'gap:\s*clamp\(0\.55rem, 1vw, 0\.75rem\)') -and
        ($css -match 'align-items:\s*stretch;') -and
        ($css -match 'align-self:\s*stretch;'))
}

$failed = @($checks.GetEnumerator() | Where-Object { -not $_.Value })
$checks.GetEnumerator() | ForEach-Object {
    '{0}: {1}' -f ($(if ($_.Value) { 'PASS' } else { 'FAIL' })), $_.Key
}

if ($failed.Count -gt 0) {
    throw "CCB Layer info / side-panel RF contract failed with $($failed.Count) failed check(s)."
}
