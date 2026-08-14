[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$pluginRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path

$php = Get-Content -LiteralPath (Join-Path $pluginRoot 'admin_manage.php') -Raw
$source = Get-Content -LiteralPath (Join-Path $pluginRoot 'amd\src\admin_manage.js') -Raw
$build = Get-Content -LiteralPath (Join-Path $pluginRoot 'amd\build\admin_manage.min.js') -Raw
$css = Get-Content -LiteralPath (Join-Path $pluginRoot 'styles.css') -Raw

$checks = [ordered]@{
    'Delete-all route requires POST, sesskey and selected source' =
        $php -match '(?s)if \(\s*\$deletealllayersajax.*?REQUEST_METHOD.*?POST.*?confirm_sesskey\(\).*?\$selectedsource';
    'Delete-preview route requires POST, sesskey and selected source' =
        $php -match '(?s)if \(\s*\$deletepreviewlayerajax.*?REQUEST_METHOD.*?POST.*?confirm_sesskey\(\).*?\$selectedsource';
    'Delete-selected route requires POST, sesskey and selected source' =
        $php -match '(?s)if \(\s*\$deleteselectedlayersajax.*?REQUEST_METHOD.*?POST.*?confirm_sesskey\(\).*?\$selectedsource';
    'Server responses include translated messages' =
        $php -match "'message'\s*=>\s*get_string";
    'Source uses Moodle notifications' =
        $source -match "'core/notification'" -and $source -match 'Notification\.addNotification';
    'Source exposes shared busy and focus lifecycle' =
        $source -match 'localCourseBannerBuilderSetAsyncActionBusy' -and
        $source -match 'localCourseBannerBuilderFocusAfterAsyncDelete';
    'Source declares canonical AMD module name' =
        $source -match '@module\s+local_course_banner_builder/admin_manage';
    'Generated AMD preserves expected dependencies' =
        $build -match 'local_course_banner_builder/motion' -and $build -match 'core/notification';
    'Generated AMD includes Moodle notifications' =
        $build -match 'core/notification';
    'Generated CSS includes the shared busy indicator' =
        $css -match '\.local-course-banner-builder-admin\.is-action-busy::before' -and
        $css -match 'data-easyedu-action-busy-label';
}
$failed = @($checks.GetEnumerator() | Where-Object { -not $_.Value })
$checks.GetEnumerator() | ForEach-Object {
    '{0}: {1}' -f ($(if ($_.Value) { 'PASS' } else { 'FAIL' })), $_.Key
}

if ($failed.Count -gt 0) {
    throw "Async editor contract failed with $($failed.Count) failed check(s)."
}
