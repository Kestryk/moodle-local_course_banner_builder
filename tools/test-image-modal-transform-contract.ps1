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
    'Preview exposes directly bound corner and edge resize handles' =
        (($form -match "data-preview-resize-mode'\s*=>\s*'corner'") -and
        ($form -match "data-preview-resize-mode'\s*=>\s*'edge'") -and
        (([regex]::Matches($form, "onpointerdown'\s*=>\s*'return window\.localCourseBannerBuilderHandleModalResizeHandlePointerDown\(event, this\);'")).Count -ge 2));
    'Side controls retain width height and aspect inputs' =
        (($form -match "'customwidthpercent'") -and
        ($form -match "'customheightpercent'") -and
        ($form -match "'customsizekeepaspect'"));
    'Side controls enter custom placement from the visible geometry' =
        (($source -match '(?s)function localCourseBannerBuilderPrepareModalCustomSizeInput\(scope\).*?localCourseBannerBuilderEnsurePreviewCustomMode\(form, layer, frame\)') -and
        ($source -match '(?s)function localCourseBannerBuilderSyncCustomSizeFields\(scope\).*?hasEditableImage.*?widthInput\.disabled\s*=\s*!hasEditableImage') -and
        ($source -match '(?s)function localCourseBannerBuilderBindPercentSliders\(scope\).*?localCourseBannerBuilderPrepareModalCustomSizeInput\(layerForm\)'));
    'Draft geometry is committed before the visual draft layer rerenders' =
        ($source -match "(?s)function localCourseBannerBuilderSyncLayerBannerPreview\(scope\).*?previewUserChanged === '1'.*?localCourseBannerBuilderSaveActiveDraftPreviewState\(layerScope\).*?localCourseBannerBuilderSyncDraftUploadPreview\(layerScope\)");
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
