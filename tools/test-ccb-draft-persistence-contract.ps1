[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$pluginRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path
$javascript = Get-Content -LiteralPath (Join-Path $pluginRoot 'amd\src\admin_manage.js') -Raw
$manager = Get-Content -LiteralPath (Join-Path $pluginRoot 'classes\manager.php') -Raw

$checks = [ordered]@{
    'Saved draft state carries its filename' =
        ($javascript -match "draftfilename: layer\.getAttribute\('data-preview-draft-filename'\)") -and
        ($javascript -match "currentLayer\.setAttribute\('data-preview-draft-filename', String\(activeFile\.name");
    'Visual draft layer exposes its filename before state is saved' =
        ($javascript -match "layer\.setAttribute\('data-preview-draft-filename', String\(file\.name");
    'Server resolves state by filename before numeric fallback' =
        ($manager -match 'function get_multi_draft_settings_for_file') -and
        $manager.Contains("(string)(`$setting['draftfilename'] ?? '') === `$filename") -and
        ($manager -match 'return is_array\(\$settings\[\$fallbackindex\] \?\? null\)');
    'Single and multiple uploads share stored-file resolution' =
        ([regex]::Matches($manager, 'get_multi_draft_settings_for_file\(').Count -ge 4);
}

$failed = @($checks.GetEnumerator() | Where-Object { -not $_.Value })
$checks.GetEnumerator() | ForEach-Object { '{0}: {1}' -f ($(if ($_.Value) {'PASS'} else {'FAIL'})), $_.Key }
if ($failed.Count -gt 0) { throw "Draft persistence contract failed with $($failed.Count) check(s)." }
