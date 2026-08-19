[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$pluginRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path

$php = Get-Content -LiteralPath (Join-Path $pluginRoot 'admin_manage.php') -Raw
$source = Get-Content -LiteralPath (Join-Path $pluginRoot 'amd\src\admin_manage.js') -Raw
$build = Get-Content -LiteralPath (Join-Path $pluginRoot 'amd\build\admin_manage.min.js') -Raw
$english = Get-Content -LiteralPath (Join-Path $pluginRoot 'lang\en\local_course_banner_builder.php') -Raw

$checks = [ordered]@{
    'Async preview-save route requires POST, sesskey and selected source' =
        $php -match '(?s)if \(\s*\$updatepreviewlayersajax.*?REQUEST_METHOD.*?POST.*?confirm_sesskey\(\).*?\$selectedsource';
    'Async preview-save route returns the server-rendered selected-source fragment' =
        $php -match '(?s)\$updatepreviewlayersajax.*?render_from_template\(\x27local_course_banner_builder/admin_selected\x27.*?\x27message\x27\s*=>\s*get_string\(\x27changessaved\x27\)';
    'Classic preview-save route remains available' =
        $php -match '(?s)if \(\$updatepreviewlayers && confirm_sesskey\(\) && \$selectedsource\).*?redirect';
    'Save form is intercepted without a page reload' =
        $source -match "form\.id === 'local-course-banner-builder-source-preview-save-form'" -and
        $source -match 'localCourseBannerBuilderSaveSourcePreviewChanges\(form\)';
    'Save request sends only the JSON action flag' =
        $source -match "formData\.delete\('updatepreviewlayers'\)" -and
        $source -match "formData\.append\('updatepreviewlayersajax', '1'\)";
    'Save refreshes from the authoritative server fragment and announces Moodle feedback' =
        $source -match 'localCourseBannerBuilderReplaceSelectedSourceContentFromDeleteResponse\(data\)' -and
        $source -match 'localCourseBannerBuilderNotifyAsyncAction\(';
    'Save and deletion controls share one busy lock' =
        $source -match 'data-source-preview-async-control' -and
        $source -match 'localCourseBannerBuilderIsAsyncActionBusy\(button\)';
    'Shared confirmation modal remains used by selected and all deletion' =
        $source -match '(?s)function localCourseBannerBuilderDeleteSelectedPreviewLayer.*?localCourseBannerBuilderConfirmAction' -and
        $source -match '(?s)function localCourseBannerBuilderDeleteAllLayers.*?localCourseBannerBuilderConfirmAction';
    'A translated preview-save error string is declared' =
        $english -match '\$string\[''unabletosavepreviewchanges''\]';
    'Generated AMD contains the async preview-save handler' =
        $build -match 'updatepreviewlayersajax' -and $build -match 'data-source-preview-async-control';
}

$failed = @($checks.GetEnumerator() | Where-Object { -not $_.Value })
$checks.GetEnumerator() | ForEach-Object {
    '{0}: {1}' -f ($(if ($_.Value) { 'PASS' } else { 'FAIL' })), $_.Key
}

if ($failed.Count -gt 0) {
    throw "General preview async contract failed with $($failed.Count) failed check(s)."
}
