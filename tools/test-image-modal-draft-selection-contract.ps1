[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$pluginRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path

$source = Get-Content -LiteralPath (Join-Path $pluginRoot 'amd\src\admin_manage.js') -Raw
$build = Get-Content -LiteralPath (Join-Path $pluginRoot 'amd\build\admin_manage.min.js') -Raw
$admin = Get-Content -LiteralPath (Join-Path $pluginRoot 'admin_manage.php') -Raw
$english = Get-Content -LiteralPath (Join-Path $pluginRoot 'lang\en\local_course_banner_builder.php') -Raw
$french = Get-Content -LiteralPath (Join-Path $pluginRoot 'lang\fr\local_course_banner_builder.php') -Raw

$checks = [ordered]@{
    'Localized Select image string is available to the AMD module' =
        (($admin -match "'selectimagedraft'") -and
        ($english -match [regex]::Escape('$string[''selectimagedraft''] = ''Select image {$a}'';')) -and
        ($french.Contains('$string[''selectimagedraft'']')));
    'Every draft thumbnail receives a native named button with selected state' =
        ($source -match '(?s)function localCourseBannerBuilderSyncDraftPreviewSelectors\(form, files\).*?document\.createElement\(''button''\).*?control\.type\s*=\s*''button''.*?data-draft-preview-select.*?aria-label.*?aria-pressed');
    'Thumbnail button and existing preview clicks share one activation route' =
        (($source -match '(?s)var draftSelectionControl.*?localCourseBannerBuilderActivateDraftPreviewSelection\(draftSelectionControl\)') -and
        ($source -match '(?s)var clickedDraftLayer.*?localCourseBannerBuilderActivateDraftPreviewSelection\(clickedDraftLayer\)') -and
        ($source -match '(?s)function localCourseBannerBuilderHandleLayerModalPreviewPointerDown\(form, event\).*?localCourseBannerBuilderActivateDraftPreviewSelection\(currentLayer\)'));
    'A to B selection retains the existing crop commit transaction' =
        (($source -match '(?s)function localCourseBannerBuilderActivateDraftPreviewSelection\(target\).*?localCourseBannerBuilderSelectDraftPreviewLayer\(form, draftIndex\)') -and
        ($source -match '(?s)function localCourseBannerBuilderSelectDraftPreviewLayer\(form, index\).*?localCourseBannerBuilderCommitActiveDraftCropBeforeSwitch\(form\).*?form\.dataset\.activeDraftIndex\s*=\s*String\(index\)'));
    'Generated AMD contains the accessible draft selector route' =
        (($build -match 'data-draft-preview-select') -and
        ($build -match 'localCourseBannerBuilderActivateDraftPreviewSelection') -and
        ($build -match 'selectimagedraft'));
}

$failed = @($checks.GetEnumerator() | Where-Object { -not $_.Value })
$checks.GetEnumerator() | ForEach-Object {
    '{0}: {1}' -f ($(if ($_.Value) { 'PASS' } else { 'FAIL' })), $_.Key
}

if ($failed.Count -gt 0) {
    throw "Image modal draft-selection contract failed with $($failed.Count) failed check(s)."
}
