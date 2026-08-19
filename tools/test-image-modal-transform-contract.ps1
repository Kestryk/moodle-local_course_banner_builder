[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$pluginRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path

$source = Get-Content -LiteralPath (Join-Path $pluginRoot 'amd\src\admin_manage.js') -Raw
$build = Get-Content -LiteralPath (Join-Path $pluginRoot 'amd\build\admin_manage.min.js') -Raw
$form = Get-Content -LiteralPath (Join-Path $pluginRoot 'classes\form\manage_banner_form.php') -Raw

$checks = [ordered]@{
    'New drafts start at original image geometry' =
        ($source -match "(?s)function localCourseBannerBuilderGetDefaultDraftPreviewState\(file\).*?fitmodeoverride:\s*'original'");
    'Fit applies proportional preview geometry and saves the draft' =
        ($source -match "(?s)function localCourseBannerBuilderApplyFitToLayerFormPreview\(form\).*?fitOverride\.value\s*=\s*'cover'.*?anchorInput\.value\s*=\s*'center'.*?widthInput\.value\s*=\s*'100'.*?heightInput\.value\s*=\s*'100'.*?keepAspectInput\.checked\s*=\s*true.*?localCourseBannerBuilderSaveActiveDraftPreviewState\(form\)");
    'Fit action has a local modal event route through history' =
        ($source -match '(?s)var fitLayerPreviewButton = localCourseBannerBuilderCreatePreviewIconButton\(.*?local-course-banner-builder-fit-layer-preview-image.*?fitLayerPreviewButton\.addEventListener\(''click''.*?event\.stopPropagation\(\).*?localCourseBannerBuilderPushModalPreviewHistoryFromControl\(fitLayerPreviewButton\).*?localCourseBannerBuilderFitSelectedLayerPreviewImageToFrame\(fitLayerPreviewButton\).*?modalPreviewFitBound');
    'Preview exposes corner and edge resize handles' =
        (($form -match "data-preview-resize-mode'\s*=>\s*'corner'") -and
        ($form -match "data-preview-resize-mode'\s*=>\s*'edge'"));
    'Active draft visual twin is excluded from snap and guides' =
        (($source -match '(?s)function localCourseBannerBuilderIsDraftSelectionVisualTwin\(activeLayer, targetLayer\).*?data-preview-draft-selection-overlay.*?data-preview-draft-visual-layer') -and
        ($source -match '(?s)function localCourseBannerBuilderGetPreviewGuideTargets\(.*?!localCourseBannerBuilderIsDraftSelectionVisualTwin\(activeLayer, node\)'));
    'Aspect-locked vertical and corner resize use their moved axis' =
        ($source -match "(?s)function localCourseBannerBuilderConstrainPreviewResizeAspect\(state, widthPercent, heightPercent, deltaX, deltaY\).*?verticalEdge.*?cornerFollowsHeight.*?useHeight.*?localCourseBannerBuilderClampPreviewSize");
    'Fit commits and renders the active draft once' =
        ($source -match '(?s)function localCourseBannerBuilderApplyFitToLayerFormPreview\(form\).*?localCourseBannerBuilderSaveActiveDraftPreviewState\(form\).*?activeDraftIndex.*?localCourseBannerBuilderRenderDraftUploadPreview\(form\)');
    'Generic preview sync avoids redundant pre-render draft serialization' =
        -not ($source -match '(?s)function localCourseBannerBuilderSyncLayerBannerPreview\(scope\).*?previewUserChanged === ''1''.*?localCourseBannerBuilderSaveActiveDraftPreviewState\(layerScope\).*?localCourseBannerBuilderSyncDraftUploadPreview\(layerScope\)');
    'Live pointer frames synchronize sliders once and defer chrome measurement' =
        (($source -match '(?s)function localCourseBannerBuilderSetPreviewFieldValue\(field, value\).*?if \(isPreviewInteraction\) \{\s*return;') -and
        ($source -match '(?s)function localCourseBannerBuilderRunPreviewInteractionFieldBatch\(state, callback\).*?localCourseBannerBuilderSyncPercentSliderValues\(form\)') -and
        ($source -match '(?s)function localCourseBannerBuilderUpdatePreviewAspectLockButton\(layer\).*?liveInteraction.*?if \(!liveInteraction.*?localCourseBannerBuilderInitPopovers.*?if \(liveInteraction\) \{\s*return;\s*\}.*?requestAnimationFrame'));
    'Complex modal previews cap peer snapping work' =
        ($source -match '(?s)function localCourseBannerBuilderApplyPreviewDrag\(state, event\).*?localCourseBannerBuilderFindPreviewSnap\(.*?\{limitPeers: true\}');
    'Side controls retain width height and aspect inputs' =
        (($form -match "'customwidthpercent'") -and
        ($form -match "'customheightpercent'") -and
        ($form -match "'customsizekeepaspect'"));
    'Side controls enter custom placement from the visible geometry' =
        (($source -match '(?s)function localCourseBannerBuilderPrepareModalCustomSizeInput\(scope\).*?localCourseBannerBuilderEnsurePreviewCustomMode\(form, layer, frame\)') -and
        ($source -match '(?s)function localCourseBannerBuilderSyncCustomSizeFields\(scope\).*?hasEditableImage.*?widthInput\.disabled\s*=\s*!hasEditableImage') -and
        ($source -match '(?s)function localCourseBannerBuilderBindPercentSliders\(scope\).*?localCourseBannerBuilderPrepareModalCustomSizeInput\(layerForm\)'));
    'Draft geometry is committed at Fit and interaction boundaries' =
        (($source -match '(?s)function localCourseBannerBuilderApplyFitToLayerFormPreview\(form\).*?localCourseBannerBuilderSaveActiveDraftPreviewState\(form\).*?localCourseBannerBuilderRenderDraftUploadPreview\(form\)') -and
        ($source -match '(?s)function localCourseBannerBuilderStopPreviewInteraction\(\).*?localCourseBannerBuilderSaveActiveDraftPreviewState\(form\).*?localCourseBannerBuilderSyncLayerBannerPreview\(form\)') -and
        ($source -match '(?s)function localCourseBannerBuilderStopModalResizeInteraction\(\).*?localCourseBannerBuilderSaveActiveDraftPreviewState\(state\.form\).*?localCourseBannerBuilderSyncLayerBannerPreview\(state\.form\)'));
    'Generated AMD contains the original-size draft default' =
        ($build -match 'fitmodeoverride:"original"');
}

$failed = @($checks.GetEnumerator() | Where-Object { -not $_.Value })
$checks.GetEnumerator() | ForEach-Object {
    '{0}: {1}' -f ($(if ($_.Value) { 'PASS' } else { 'FAIL' })), $_.Key
}

if ($failed.Count -gt 0) {
    throw "Image modal transform contract failed with $($failed.Count) failed check(s)."
}
