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
    'Modal image controls resolve the scoped id before the canonical field name' =
        ($source -match "(?s)function localCourseBannerBuilderGetLayerFormControl\(form, id, name\).*?form\.querySelector\('#' \+ id\).*?return byId \|\| \(name \? form\.querySelector");
    'Fit applies proportional preview geometry through the modal transaction' =
        ($source -match "(?s)function localCourseBannerBuilderApplyFitToLayerFormPreview\(form\).*?fitmodeoverride:\s*'cover'.*?positionanchor:\s*'center'.*?customwidthpercent:\s*100.*?customheightpercent:\s*100.*?customsizekeepaspect:\s*true.*?localCourseBannerBuilderCommitModalImagePreviewState\(");
    'Fit preserves the active image Crop state' =
        ($source -match '(?s)function localCourseBannerBuilderApplyFitToLayerFormPreview\(form\).*?Fit changes outer placement only; it deliberately preserves Crop fields\..*?customsizekeepaspect:\s*true,.*?offsettoppercent:');
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
    'Fit and Fill share one modal image-state transaction' =
        (($source -match '(?s)function localCourseBannerBuilderApplyFitToLayerFormPreview\(form\).*?localCourseBannerBuilderCommitModalImagePreviewState\(') -and
        ($source -match '(?s)function localCourseBannerBuilderApplyFillBannerToLayerFormPreview\(form\).*?localCourseBannerBuilderCommitModalImagePreviewState\('));
    'The transaction writes fields then draft JSON then mirrors' =
        ($source -match '(?s)function localCourseBannerBuilderCommitModalImagePreviewState\(form, patch, options\).*?localCourseBannerBuilderApplyLayerFormPreviewState\(form, nextState, \{deferDom: true\}\).*?localCourseBannerBuilderWriteActiveDraftPreviewState\(form, nextState\).*?localCourseBannerBuilderSyncCurrentLayerDataFromForm\(form\).*?localCourseBannerBuilderSyncStandalonePreviewLayer');
    'Draft selection restores generated Crop fields through the shared resolver' =
        ($source -match "(?s)function localCourseBannerBuilderApplyLayerFormPreviewState\(form, state, options\).*?localCourseBannerBuilderGetCropInput\(form, 'imagecropenabled'\).*?localCourseBannerBuilderGetCropInput\(form, 'imagecropheightpercent'\)");
    'Crop pointer completion is isolated from generic preview commits' =
        (($source -match '(?s)function localCourseBannerBuilderStopCropPointerInteraction\(event\).*?localCourseBannerBuilderCropInteraction.*?event\.stopImmediatePropagation\(\).*?localCourseBannerBuilderStopCropInteraction\(\)') -and
        ($source -match "document\.addEventListener\('pointerup', localCourseBannerBuilderStopCropPointerInteraction, true\)") -and
        ($source -match "document\.addEventListener\('pointercancel', localCourseBannerBuilderStopCropPointerInteraction, true\)"));
    'Cancelling Recrop discards only its duplicate history snapshot' =
        (($source -match '(?s)function localCourseBannerBuilderCancelCropEditor\(control, sourceMode\).*?localCourseBannerBuilderDiscardModalPreviewHistorySnapshot\(form\)') -and
        ($source -match '(?s)function localCourseBannerBuilderDiscardModalPreviewHistorySnapshot\(form\).*?undoStack\[undoStack\.length - 1\] === currentSnapshot.*?undoStack\.pop\(\)'));
    'Draft modal history atomically records all existing image transformations' =
        (($source -match '(?s)function localCourseBannerBuilderBuildDraftTransformationHistorySnapshot\(form, fields\).*?localCourseBannerBuilderSaveActiveDraftPreviewState\(form\).*?draftStates.*?kind:\s*''draft-transformations''') -and
        ($source -match '(?s)function localCourseBannerBuilderRestoreDraftTransformationHistory\(form, snapshot\).*?availableIndexes.*?localCourseBannerBuilderRenderDraftUploadPreview\(form\)'));
    'Draft selection itself is undoable without restoring file lifecycle' =
        (($source -match '(?s)function localCourseBannerBuilderSelectDraftPreviewLayer\(form, index\).*?localCourseBannerBuilderPushModalPreviewHistory\(form\).*?localCourseBannerBuilderCommitActiveDraftCropBeforeSwitch\(form\)') -and
        ($source -match '(?s)function localCourseBannerBuilderRestoreDraftTransformationHistory\(form, snapshot\).*?Never recreate a deleted file or remove a file that was added after'));
    'Create draft handles use the dedicated resize state' =
        ($source -match '(?s)function localCourseBannerBuilderHandleLayerModalPreviewPointerDown\(form, event\).*?if \(resizeHandle\).*?data-preview-draft-selection-overlay.*?localCourseBannerBuilderStartModalResizeInteraction\(event, resizeHandle\).*?return true;.*?localCourseBannerBuilderStartPreviewInteraction');
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
    'Pointer frames stage draft JSON and serialize once at interaction end' =
        (($source -match '(?s)function localCourseBannerBuilderRunPreviewInteractionFieldBatch\(state, callback\).*?deferDraftSerialization:\s*true.*?live:\s*true') -and
        ($source -match '(?s)function localCourseBannerBuilderStopPreviewInteraction\(\).*?_localCourseBannerBuilderPendingDraftPreviewState') -and
        ($source -match '(?s)function localCourseBannerBuilderStopModalResizeInteraction\(\).*?_localCourseBannerBuilderPendingDraftPreviewState'));
    'File-manager refreshes are coalesced per modal form' =
        (($source -match '(?s)function localCourseBannerBuilderScheduleDraftPreviewRefresh\(form, delays\).*?form\._localCourseBannerBuilderDraftPreviewRefreshQueue.*?window\.clearTimeout\(queue\.timer\).*?window\.setTimeout') -and
        ($source -match '(?s)new MutationObserver\(function \(\) \{.*?localCourseBannerBuilderScheduleDraftPreviewRefresh\('));
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
