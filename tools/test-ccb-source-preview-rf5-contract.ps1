[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$pluginRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path
$source = Get-Content -LiteralPath (Join-Path $pluginRoot 'amd\src\admin_manage.js') -Raw
$build = Get-Content -LiteralPath (Join-Path $pluginRoot 'amd\build\admin_manage.min.js') -Raw
$actionScss = Get-Content -LiteralPath (Join-Path $pluginRoot 'scss\components\_action-contract.scss') -Raw
$adapterScss = Get-Content -LiteralPath (Join-Path $pluginRoot 'scss\components\_easyedu-adapter.scss') -Raw
$css = Get-Content -LiteralPath (Join-Path $pluginRoot 'styles.css') -Raw

$checks = [ordered]@{
    'Accordion opening is measured from its hidden state by Motion.expand' =
        (($source -match '(?s)if \(opening\) \{\s*details\.setAttribute\(.+?Motion\.expand\(content\)') -and
        ($source -notmatch '(?s)if \(opening\) \{\s*details\.setAttribute\(.+?content\.hidden = false;\s*Motion\.expand\(content\)'));
    'Layer disclosure uses a sober existing caption role' =
        ($actionScss -match '(?s)\.local-course-banner-builder-layer-details-accordion\s*\{.*?> summary\s*\{\s*@include easyedu\.type-caption;.*?font-weight:\s*var\(--easyedu-font-weight-medium\)');
    'Help control owns a separate grid column and deliberate gap' =
        (($actionScss -match 'grid-template-columns:\s*minmax\(0, 1fr\) 1\.35rem;') -and
        ($actionScss -match 'gap:\s*0\.45rem;') -and
        ($actionScss -match '(?s)\.local-course-banner-builder-layer-details-help\s*\{.*?position:\s*static;'));
    'Border-box disclosure content collapses without a padding jump' =
        ($actionScss -match '(?s)\.local-course-banner-builder-layer-details-accordion-content\s*\{\s*box-sizing:\s*border-box;');
    'Source Preview identity removes inherited Moodle icon margins' =
        ($adapterScss -match '(?s)\.local-course-banner-builder-source-chain-preview-modal-identity\s*\{.*?\.icon,\s*\.fa\s*\{.*?margin:\s*0;');
    'Source Preview footer action defines the shared icon-text gap variables' =
        ($actionScss -match '(?s)\.local-course-banner-builder-slideshow-admin-preview--large,\s*\.local-course-banner-builder-source-chain-preview-actions\s*\{\s*--local-course-banner-builder-action-gap:\s*0\.5rem;');
    'Official AMD and CSS artifacts contain RF5' =
        (($build.Length -gt 1000) -and
        ($build -match 'local_course_banner_builder/motion') -and
        ($build -match 'local-course-banner-builder-layer-details-accordion-content') -and
        ($css -match '(?s)\.local-course-banner-builder-layer-details-disclosure-shell\s*\{.*?grid-template-columns:\s*minmax\(0, 1fr\) 1\.35rem') -and
        ($css -match '(?s)\.local-course-banner-builder-source-chain-preview-modal-identity .*?\.icon.*?margin:\s*0'));
}

$failed = @($checks.GetEnumerator() | Where-Object { -not $_.Value })
$checks.GetEnumerator() | ForEach-Object {
    '{0}: {1}' -f ($(if ($_.Value) { 'PASS' } else { 'FAIL' })), $_.Key
}

if ($failed.Count -gt 0) {
    throw "CCB Sources/Preview RF5 contract failed with $($failed.Count) failed check(s)."
}
