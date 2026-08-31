[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$pluginRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path

$php = Get-Content -LiteralPath (Join-Path $pluginRoot 'admin_manage.php') -Raw
$template = Get-Content -LiteralPath (Join-Path $pluginRoot 'templates\admin_manage.mustache') -Raw
$source = Get-Content -LiteralPath (Join-Path $pluginRoot 'amd\src\admin_manage.js') -Raw
$build = Get-Content -LiteralPath (Join-Path $pluginRoot 'amd\build\admin_manage.min.js') -Raw
$architecture = Get-Content -LiteralPath (Join-Path $pluginRoot 'docs\architecture\transient-async-notifications.md') -Raw

$checks = [ordered]@{
    'Allowlisted preview-save route remains POST/sesskey/source guarded' =
        $php -match '(?s)if \(\s*\$updatepreviewlayersajax.*?REQUEST_METHOD.*?POST.*?confirm_sesskey\(\).*?\$selectedsource'
    'Allowlisted delete routes remain POST/sesskey/source guarded' =
        $php -match '(?s)if \(\s*\$deletepreviewlayerajax.*?REQUEST_METHOD.*?POST.*?confirm_sesskey\(\).*?\$selectedsource' -and
        $php -match '(?s)if \(\s*\$deletealllayersajax.*?REQUEST_METHOD.*?POST.*?confirm_sesskey\(\).*?\$selectedsource' -and
        $php -match '(?s)if \(\s*\$deleteselectedlayersajax.*?REQUEST_METHOD.*?POST.*?confirm_sesskey\(\).*?\$selectedsource'
    'Parent-source AJAX route returns JSON and a translated message' =
        $php -match '(?s)if \(\$updatesourceparentfield.*?\$isxmlhttprequest.*?''message''\s*=>\s*get_string\('
    'CCB uses the temporary Moodle toast module' =
        $source -match "'core/toast'" -and $source -match 'Toast\.add\('
    'Toast uses EasyStud-compatible temporary timing and error mapping' =
        $source -match 'delay: 4000' -and $source -match "type === 'error' \? 'danger'"
    'Parent-source errors remain inline and actionable' =
        $source -match 'localCourseBannerBuilderShowParentSourceChangeError' -and
        $source -match 'data-parent-source-change-submit' -and
        $template -match 'role="alert"' -and $template -match 'aria-live="assertive"'
    'Existing no-reload lifecycle remains intact' =
        $source -match 'localCourseBannerBuilderSetAsyncActionBusy' -and
        $source -match 'localCourseBannerBuilderFocusAfterAsyncDelete' -and
        $source -match 'localCourseBannerBuilderReplaceSelectedSourceContentFromDeleteResponse'
    'Generated AMD has canonical wrapper and no ESM declarations' =
        $build -match '(?s)\*/\s*define\(' -and $build -notmatch '(?m)^\s*(import|export)\s'
    'Route exclusions are documented' =
        $architecture -match 'intentionally excludes' -and
        $architecture -match 'create,\s*edit,\s*delete, source settings'
}

$failed = @($checks.GetEnumerator() | Where-Object { -not $_.Value })
$checks.GetEnumerator() | ForEach-Object {
    '{0}: {1}' -f ($(if ($_.Value) { 'PASS' } else { 'FAIL' })), $_.Key
}

if ($failed.Count -gt 0) {
    throw "Transient CCB notification contract failed with $($failed.Count) failed check(s)."
}
