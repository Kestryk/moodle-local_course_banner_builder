$ErrorActionPreference = 'Stop'

$pluginRoot = Split-Path -Parent $PSScriptRoot
$animations = Get-Content -Raw -LiteralPath (Join-Path $pluginRoot 'scss\easyedu\components\_animations.scss')
$modals = Get-Content -Raw -LiteralPath (Join-Path $pluginRoot 'scss\easyedu\components\_modals.scss')
$buttons = Get-Content -Raw -LiteralPath (Join-Path $pluginRoot 'scss\easyedu\components\_buttons.scss')
$adapter = Get-Content -Raw -LiteralPath (Join-Path $pluginRoot 'scss\components\_easyedu-adapter.scss')
$layout = Get-Content -Raw -LiteralPath (Join-Path $pluginRoot 'scss\components\_admin-layout.scss')
$preview = Get-Content -Raw -LiteralPath (Join-Path $pluginRoot 'scss\components\_preview-editor.scss')
$modalPreviewActions = Get-Content -Raw -LiteralPath (Join-Path $pluginRoot 'scss\components\_modal-preview-actions.scss')
$javascript = Get-Content -Raw -LiteralPath (Join-Path $pluginRoot 'amd\src\admin_manage.js')
$build = Get-Content -Raw -LiteralPath (Join-Path $pluginRoot 'amd\build\admin_manage.min.js')
$template = Get-Content -Raw -LiteralPath (Join-Path $pluginRoot 'templates\admin_selected.mustache')
$php = Get-Content -Raw -LiteralPath (Join-Path $pluginRoot 'admin_manage.php')

function Assert-Contract([bool]$Condition, [string]$Message) {
    if (-not $Condition) {
        throw $Message
    }
}

Assert-Contract ($animations -match '@mixin\s+busy-indicator-ring' -and
    $animations -match '@include\s+busy-indicator-ring') 'The canonical busy ring is not shared with bottom-end feedback.'
Assert-Contract ($modals -match '@include\s+animations\.busy-indicator-ring') 'Native modal loading does not consume the canonical busy ring.'
Assert-Contract ($modals -match '\$ring-selector:\s*null' -and
    $modals -match '(?s)@if\s+\$ring-selector.*?#\{\$ring-selector\}.*?@include\s+animations\.busy-indicator-ring') 'Native modal loading cannot target a real nested ring.'
Assert-Contract ($adapter -match '(?s)native-modal-loading\(\s*"\.local-course-banner-builder-layer-modal-loading",\s*var\(--easyedu-primary\),\s*"\.spinner-border"\s*\)') 'Layer modals do not target their real Bootstrap spinner with native-modal-loading.'
Assert-Contract ($modalPreviewActions -notmatch '(?s)\.local-course-banner-builder-layer-modal-loading\s*\{.*?flex:\s*1\s+1\s+auto') 'The loading status still stretches the animated ring.'
Assert-Contract ($javascript -match '(?s)function localCourseBannerBuilderShowLayerModalLoading.*?spinner\.className\s*=\s*''spinner-border text-primary''.*?modal\.classList\.add\(''is-loading''\).*?modal\.setAttribute\(''aria-busy'',\s*''true''\)' -and
    $javascript -match '(?s)localCourseBannerBuilderSafelyPrepareDynamicLayerModal\(targetmodal\).*?targetmodal\.classList\.remove\(''is-loading''\).*?targetmodal\.setAttribute\(''aria-busy'',\s*''false''\)') 'Layer modal loading does not use the complete busy lifecycle.'
Assert-Contract ($template -match 'data-action="local-course-banner-builder-delete-source-layer"' -and
    $template -match 'data-source-layer-id="\{\{id\}\}"' -and
    $template -match 'data-source-key="\{\{sourcekey\}\}"') 'Table-row Delete is not connected to the source-owned async action.'
Assert-Contract ($javascript -match '(?s)function localCourseBannerBuilderDeleteSourceLayer.*?localCourseBannerBuilderDeleteOwnedSourceLayer' -and
    $javascript -match '(?s)function localCourseBannerBuilderDeleteOwnedSourceLayer.*?formData\.append\(''sourcekey'',\s*sourceKey\).*?formData\.append\(''deletepreviewlayerajax'',\s*layerId\).*?method:\s*''POST''') 'Table-row Delete does not reuse the no-reload source-owned request.'
Assert-Contract ($php -match '(?s)\$deletepreviewlayerajax.*?REQUEST_METHOD.*?POST.*?confirm_sesskey\(\).*?\$selectedsource.*?manager::delete_source_banner_element\(\s*\$selectedsource,\s*\$deletepreviewlayerajax') 'The table-row AJAX route lost its method, sesskey or source-ownership boundary.'
Assert-Contract ($adapter -match '(?s)\.local-course-banner-builder-bulk-action-row.*?\.local-course-banner-builder-bulk-action-button.*?@include\s+easyedu\.action-button\(small\).*?font-size:\s*0\.78rem\s*!important.*?line-height:\s*1\.2\s*!important.*?height:\s*2\.15rem\s*!important') 'The three preview actions do not share one specificity-safe text and geometry contract.'
Assert-Contract (([regex]::Matches($template, 'local-course-banner-builder-bulk-action-button')).Count -eq 3) 'The shared preview-action class is not present exactly three times.'
Assert-Contract ($adapter -match '(?s)\[data-action="local-course-banner-builder-delete-source-layer"\]\s*\{\s*cursor:\s*pointer\s*!important' -and
    $adapter -match '(?s)\.local-course-banner-builder-bulk-delete-button:hover,.*?background:\s*var\(--easyedu-primary-soft\)\s*!important') 'Delete pointer or Save-like light hover is not explicitly protected.'
Assert-Contract ($buttons -match '(?s)&:hover,\s*&:focus-visible,\s*&:active,') 'The shared action state does not include pressed feedback.'
Assert-Contract ($layout -notmatch '(?s)\.local-course-banner-builder-bulk-delete-button,\s*\.local-course-banner-builder-bulk-save-button\s*\{\s*min-height:\s*2\.75rem') 'Legacy oversized action geometry is still active.'
Assert-Contract ($preview -notmatch '(?s)\.local-course-banner-builder-bulk-action-row \.btn,\s*\.local-course-banner-builder-bulk-save-button') 'Legacy preview action typography is still duplicated.'
Assert-Contract ($build -match 'local-course-banner-builder-delete-source-layer' -and
    $build -match 'deletepreviewlayerajax' -and
    $build -match 'spinner-border text-primary' -and
    $build -match 'is-loading') 'Generated AMD does not contain the RF1 action and modal-loading lifecycle.'

Write-Output 'CCB modal loading and compact preview action contract passed.'
