$ErrorActionPreference = 'Stop'

$pluginRoot = Split-Path -Parent $PSScriptRoot
$animations = Get-Content -Raw -LiteralPath (Join-Path $pluginRoot 'scss\easyedu\components\_animations.scss')
$modals = Get-Content -Raw -LiteralPath (Join-Path $pluginRoot 'scss\easyedu\components\_modals.scss')
$buttons = Get-Content -Raw -LiteralPath (Join-Path $pluginRoot 'scss\easyedu\components\_buttons.scss')
$adapter = Get-Content -Raw -LiteralPath (Join-Path $pluginRoot 'scss\components\_easyedu-adapter.scss')
$layout = Get-Content -Raw -LiteralPath (Join-Path $pluginRoot 'scss\components\_admin-layout.scss')
$preview = Get-Content -Raw -LiteralPath (Join-Path $pluginRoot 'scss\components\_preview-editor.scss')

function Assert-Contract([bool]$Condition, [string]$Message) {
    if (-not $Condition) {
        throw $Message
    }
}

Assert-Contract ($animations -match '@mixin\s+busy-indicator-ring' -and
    $animations -match '@include\s+busy-indicator-ring') 'The canonical busy ring is not shared with bottom-end feedback.'
Assert-Contract ($modals -match '@include\s+animations\.busy-indicator-ring') 'Native modal loading does not consume the canonical busy ring.'
Assert-Contract ($modals -match '\.spinner-border') 'The native modal bridge does not suppress the nested Bootstrap spinner.'
Assert-Contract ($adapter -match 'native-modal-loading\("\.local-course-banner-builder-layer-modal-loading"\)') 'Layer modals do not consume native-modal-loading.'
Assert-Contract ($adapter -match '(?s)\.local-course-banner-builder-bulk-action-row.*?\.local-course-banner-builder-bulk-delete-button,.*?\.local-course-banner-builder-bulk-save-button.*?@include\s+easyedu\.action-button\(small\).*?height:\s*2\.15rem\s*!important') 'The three preview actions do not share the compact action contract.'
Assert-Contract ($buttons -match '(?s)&:hover,\s*&:focus-visible,\s*&:active,') 'The shared action state does not include pressed feedback.'
Assert-Contract ($layout -notmatch '(?s)\.local-course-banner-builder-bulk-delete-button,\s*\.local-course-banner-builder-bulk-save-button\s*\{\s*min-height:\s*2\.75rem') 'Legacy oversized action geometry is still active.'
Assert-Contract ($preview -notmatch '(?s)\.local-course-banner-builder-bulk-action-row \.btn,\s*\.local-course-banner-builder-bulk-save-button') 'Legacy preview action typography is still duplicated.'

Write-Output 'CCB modal loading and compact preview action contract passed.'
