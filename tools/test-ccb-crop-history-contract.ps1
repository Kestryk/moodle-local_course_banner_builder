[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$pluginRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path
$source = Get-Content -LiteralPath (Join-Path $pluginRoot 'amd\src\admin_manage.js') -Raw
$build = Get-Content -LiteralPath (Join-Path $pluginRoot 'amd\build\admin_manage.min.js') -Raw
$spec = Get-Content -LiteralPath (Join-Path $pluginRoot 'tools\playwright\ccb-crop-recrop-history.spec.js') -Raw
$fixture = Get-Content -LiteralPath (Join-Path $pluginRoot 'tools\playwright\ccb-crop-history-fixture.php') -Raw
$runner = Get-Content -LiteralPath (Join-Path $pluginRoot 'tools\playwright\Invoke-CCBCropHistoryValidation.ps1') -Raw

$checks = [ordered]@{
    'History captures every existing draft transformation and active selection' =
        ($source -match '(?s)function localCourseBannerBuilderBuildDraftTransformationHistorySnapshot\(form, fields\).*?localCourseBannerBuilderSaveActiveDraftPreviewState\(form\).*?activeDraftIndex.*?draftStates');
    'History restores only available non-deleted draft settings' =
        ($source -match '(?s)function localCourseBannerBuilderRestoreDraftTransformationHistory\(form, snapshot\).*?availableIndexes.*?settings\[index\] && settings\[index\]\.deleted.*?localCourseBannerBuilderRenderDraftUploadPreview\(form\)');
    'Fit leaves Crop fields outside its replacement patch' =
        ($source -match '(?s)function localCourseBannerBuilderApplyFitToLayerFormPreview\(form\).*?Fit changes outer placement only; it deliberately preserves Crop fields\.');
    'First modal Crop derives outer geometry from the accepted selection' =
        ($source -match '(?s)function localCourseBannerBuilderApplyCropEditor\(control, sourceMode\).*?if \(!cropSelectionState\) \{\s*cropSelectionState = localCourseBannerBuilderGetCropSelectionCustomState\(layer, false\);');
    'Modal Recrop measures the rendered editor selection in session coordinates' =
        ($source -match '(?s)function localCourseBannerBuilderGetCropSessionSelectionState\(layer\).*?GetCropSessionSourceState\(layer\).*?data-preview-crop-box="1".*?BuildPercentRectFromDomRect\(boxRect, frameRect\).*?source\.width.*?source\.height');
    'Every modal Recrop composes inside the already accepted geometry' =
        (($source -match '(?s)function localCourseBannerBuilderGetModalRecropSelectionCustomState\(layer, selectionState\).*?relativeWidth = currentCropState\.width / initialWidth.*?acceptedWidth \* relativeWidth.*?acceptedLeft \+ \(acceptedWidth \* relativeLeft\)') -and
        ($source -match '(?s)function localCourseBannerBuilderApplyCropEditor\(control, sourceMode\).*?GetCropSessionSelectionState\(layer\).*?sourceMode \? null :\s*localCourseBannerBuilderGetModalRecropSelectionCustomState\(layer, crop\)'));
    'Recrop outside the accepted Crop falls back to absolute editor geometry' =
        ($source -match '(?s)function localCourseBannerBuilderGetModalRecropSelectionCustomState\(layer, selectionState\).*?Reframing outside the accepted Crop.*?return null;');
    'Draft selection records a history boundary before switching' =
        ($source -match '(?s)function localCourseBannerBuilderSelectDraftPreviewLayer\(form, index\).*?localCourseBannerBuilderPushModalPreviewHistory\(form\).*?localCourseBannerBuilderCommitActiveDraftCropBeforeSwitch\(form\)');
    'Focused CROP-08 scenario requires exactly two pre-existing image selectors' =
        (($spec -match 'CROP-08 restores chronological') -and
        ($spec -match 'toHaveCount\(2\)') -and
        ($spec -match 'EASYEDU_CCB_CROP_HISTORY_MODAL_URL'));
    'Focused CROP-08 scenario does not add or delete Filemanager files' =
        (($spec -notmatch 'setInputFiles') -and ($spec -notmatch 'fp-upload') -and ($spec -notmatch 'delete'));
    'CROP-08 fixture owns exactly two existing image files and reports an edit route' =
        (($fixture -like '*CROP-08 fixture must create exactly two element images*') -and
        ($fixture -like "*'filecount' => count(`$files)*") -and
        ($fixture -like "*'modalPath' => '/local/course_banner_builder/admin_manage.php?sourcekey='*"));
    'CROP-08 runner discovers one test before credentials and fixture setup' =
        (($runner -like '*Selecting exactly one CROP-08 test before credentials or fixture work*') -and
        ($runner -like '*Playwright discovery did not select exactly one CROP-08 test*') -and
        ($runner -like "*`$fixture = Invoke-Fixture 'setup'*"));
    'Generated AMD contains the transaction implementation' =
        (($build.Length -gt 1000) -and ($build -match 'localCourseBannerBuilderBuildModalPreviewSnapshot'));
}

$failed = @($checks.GetEnumerator() | Where-Object { -not $_.Value })
$checks.GetEnumerator() | ForEach-Object {
    '{0}: {1}' -f ($(if ($_.Value) { 'PASS' } else { 'FAIL' })), $_.Key
}
if ($failed.Count -gt 0) {
    throw "Crop transformation-history contract failed with $($failed.Count) failed check(s)."
}
