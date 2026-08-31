[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$pluginRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path

$php = Get-Content -LiteralPath (Join-Path $pluginRoot 'admin_manage.php') -Raw
$manager = Get-Content -LiteralPath (Join-Path $pluginRoot 'classes\manager.php') -Raw
$source = Get-Content -LiteralPath (Join-Path $pluginRoot 'amd\src\admin_manage.js') -Raw
$build = Get-Content -LiteralPath (Join-Path $pluginRoot 'amd\build\admin_manage.min.js') -Raw
$css = Get-Content -LiteralPath (Join-Path $pluginRoot 'styles.css') -Raw
$template = Get-Content -LiteralPath (Join-Path $pluginRoot 'templates\admin_selected.mustache') -Raw
$sourceBoundaryTest = Get-Content -LiteralPath (Join-Path $pluginRoot 'tests\manager_source_deletion_test.php') -Raw

$checks = [ordered]@{
    'Delete-all route requires POST, sesskey and selected source' =
        $php -match '(?s)if \(\s*\$deletealllayersajax.*?REQUEST_METHOD.*?POST.*?confirm_sesskey\(\).*?\$selectedsource';
    'Delete-preview route requires POST, sesskey and selected source' =
        $php -match '(?s)if \(\s*\$deletepreviewlayerajax.*?REQUEST_METHOD.*?POST.*?confirm_sesskey\(\).*?\$selectedsource';
    'Delete-selected route requires POST, sesskey and selected source' =
        $php -match '(?s)if \(\s*\$deleteselectedlayersajax.*?REQUEST_METHOD.*?POST.*?confirm_sesskey\(\).*?\$selectedsource';
    'Single and selected deletion routes use the source-owned manager contract' =
        $php -match 'manager::delete_source_banner_element\(' -and
        $php -match 'manager::delete_source_banner_elements\(';
    'Manager validates every element against the resolved source before deletion' =
        $manager -match '(?s)function delete_source_banner_element.*?get_record_source_key\(\$record\).*?\$source->sourcekey.*?delete_banner_element' -and
        $manager -match '(?s)function delete_source_banner_elements.*?foreach \(\$elementids as \$elementid\).*?get_record_source_key\(\$record\).*?\$source->sourcekey.*?foreach \(\$elementids as \$elementid\).*?delete_banner_element';
    'Regression test rejects foreign and mixed-source ids without partial mutation' =
        $sourceBoundaryTest -match 'delete_source_banner_element\(\$sourcea, \$layerb, false\)' -and
        $sourceBoundaryTest -match 'delete_source_banner_elements\(\$sourcea, \[\$layera, \$layerb\]\)' -and
        ([regex]::Matches($sourceBoundaryTest, 'record_exists\(''local_course_banner_builder_elements''')).Count -ge 5;
    'Server responses include translated messages' =
        $php -match "'message'\s*=>\s*get_string";
    'Source uses Moodle notifications' =
        $source -match "'core/notification'" -and $source -match 'Notification\.addNotification';
    'Source exposes shared busy and focus lifecycle' =
        $source -match 'localCourseBannerBuilderSetAsyncActionBusy' -and
        $source -match 'localCourseBannerBuilderFocusAfterAsyncDelete';
    'Bulk deletion removes deleted ids from loaded layer modals' =
        $source -match 'localCourseBannerBuilderRemoveLayersFromLayerModals\(selectedLayerIds\)';
    'All under-preview mutations participate in the shared busy lock' =
        ([regex]::Matches($template, 'data-source-preview-async-control="1"')).Count -ge 3;
    'Source declares canonical AMD module name' =
        $source -match '@module\s+local_course_banner_builder/admin_manage';
    'Generated AMD preserves expected dependencies' =
        $build -match 'local_course_banner_builder/motion' -and $build -match 'core/notification';
    'Generated AMD includes Moodle notifications' =
        $build -match 'core/notification';
    'Generated AMD includes source-fragment refresh and bulk modal cleanup' =
        $build -match 'deleteselectedlayersajax' -and
        $build -match 'data-preview-layer-id';
    'Generated CSS includes the shared busy indicator' =
        $css -match '\.local-course-banner-builder-admin\.is-action-busy::before' -and
        $css -match 'data-easyedu-action-busy-label';
    'Generated CSS anchors the busy feedback at the EasyStud bottom end' =
        $css -match '(?s)\.local-course-banner-builder-admin\.is-action-busy::after\s*\{.*?bottom: 1\.25rem;.*?position: fixed;.*?right: 1\.25rem;' -and
        $css -match '(?s)\.local-course-banner-builder-admin\.is-action-busy::before\s*\{.*?bottom: 1\.45rem;.*?position: fixed;.*?right: 1\.45rem;';
    'Generated CSS preserves the centred layer empty state' =
        $css -match '(?s)\.local-course-banner-builder-admin--native \.local-course-banner-builder-empty-layer-list,.*?justify-content: center;.*?min-height: 4\.25rem;.*?padding-block: 1rem;';
}
$failed = @($checks.GetEnumerator() | Where-Object { -not $_.Value })
$checks.GetEnumerator() | ForEach-Object {
    '{0}: {1}' -f ($(if ($_.Value) { 'PASS' } else { 'FAIL' })), $_.Key
}

if ($failed.Count -gt 0) {
    throw "Async editor contract failed with $($failed.Count) failed check(s)."
}
