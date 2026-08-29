[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$pluginRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path

$files = @{
    Admin = Get-Content -LiteralPath (Join-Path $pluginRoot 'admin_manage.php') -Raw
    Manager = Get-Content -LiteralPath (Join-Path $pluginRoot 'classes\manager.php') -Raw
    Selected = Get-Content -LiteralPath (Join-Path $pluginRoot 'templates\admin_selected.mustache') -Raw
    Sources = Get-Content -LiteralPath (Join-Path $pluginRoot 'templates\admin_manage.mustache') -Raw
    Adapter = Get-Content -LiteralPath (Join-Path $pluginRoot 'scss\components\_easyedu-adapter.scss') -Raw
    Buttons = Get-Content -LiteralPath (Join-Path $pluginRoot 'scss\easyedu\components\_buttons.scss') -Raw
    Modals = Get-Content -LiteralPath (Join-Path $pluginRoot 'scss\easyedu\components\_modals.scss') -Raw
    Layout = Get-Content -LiteralPath (Join-Path $pluginRoot 'scss\components\_admin-layout.scss') -Raw
}

$checks = [ordered]@{
    'Selected composition mode opens source settings modal' =
        $files.Selected.Contains('local-course-banner-builder-source-settings-trigger') -and
        $files.Selected.Contains('data-bs-target="#local-course-banner-builder-source-settings-modal"')
    'Configured composition mode selects source and opens settings' =
        $files.Sources.Contains('{{sourcesettingsurl}}') -and
        $files.Manager.Contains("'opensourcesettings' => 1") -and
        $files.Admin.Contains('$opensourcesettings = optional_param')
    'Parent modal Save has a floppy icon' =
        $files.Sources.Contains('local-course-banner-builder-parent-source-save') -and
        $files.Sources.Contains('fa fa-save me-2')
    'Published Kit Save and pencil primitives are consumed' =
        $files.Adapter.Contains('@include easyedu.save-action-button') -and
        $files.Adapter.Contains('@include easyedu.edit-pencil-action')
    'Every CCB close family uses the published close primitive' =
        $files.Adapter.Contains('@include easyedu.modal-close-button(small)') -and
        $files.Adapter.Contains('.modal.local-course-banner-builder-confirm-action-modal')
    'Layer previews use the neutral published checkerboard' =
        $files.Layout.Contains('@include easyedu.preview-checkerboard') -and
        $files.Modals.Contains('@mixin preview-checkerboard') -and
        $files.Modals.Contains('var(--easyedu-preview-checker-dark)')
    'No Crop or Recrop lifecycle source is part of this contract' =
        -not $files.Admin.Contains('EED-CCB-2026-0043')
}

$failed = $checks.GetEnumerator() | Where-Object { -not $_.Value }
if ($failed) {
    $failed | ForEach-Object { Write-Error "FAILED: $($_.Key)" }
    exit 1
}

$checks.Keys | ForEach-Object { Write-Output "PASS: $_" }
