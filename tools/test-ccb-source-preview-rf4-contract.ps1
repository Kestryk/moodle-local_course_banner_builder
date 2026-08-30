param()

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)

function Read-ProjectFile([string]$RelativePath) {
    return Get-Content -LiteralPath (Join-Path $root $RelativePath) -Raw
}

function Assert-Contract([bool]$Condition, [string]$Message) {
    if (-not $Condition) {
        throw "RF4 contract failed: $Message"
    }
    Write-Host "PASS: $Message"
}

$source = Read-ProjectFile 'amd/src/admin_manage.js'
$built = Read-ProjectFile 'amd/build/admin_manage.min.js'
$scss = Read-ProjectFile 'scss/components/_easyedu-adapter.scss'
$controls = Read-ProjectFile 'scss/components/_admin-controls.scss'
$actions = Read-ProjectFile 'scss/components/_action-contract.scss'
$template = Read-ProjectFile 'templates/admin_manage.mustache'
$form = Read-ProjectFile 'classes/form/manage_banner_form.php'
$english = Read-ProjectFile 'lang/en/local_course_banner_builder.php'
$french = Read-ProjectFile 'lang/fr/local_course_banner_builder.php'
$css = Read-ProjectFile 'styles.css'

Assert-Contract ($source -match 'Motion\.resize\(tableShell, apply' -and
    $source -match 'change\.row\.hidden = !change\.visible') `
    'configured-source rows slide through a table-safe shared Motion resize'
Assert-Contract ($source -match "details\.local-course-banner-builder-layer-details-accordion" -and
    $source -match 'local-course-banner-builder-layer-details-accordion-content') `
    'the live Layer and Overrides disclosure is owned by the shared accordion lifecycle'
Assert-Contract ($controls -match 'configured-source-tools[\s\S]*padding-inline:\s*1rem' -and
    $css -match 'configured-source-tools[^{]*\{[^}]*padding-inline:\s*1rem') `
    'Collapse all has bounded inline toolbar spacing in source and generated CSS'
Assert-Contract ($english -match "chainborderexistinglabel'\]\s*=\s*'Inherited border'" -and
    $english -match "chainoverlayexistinglabel'\]\s*=\s*'Inherited overlay'" -and
    $french -match "chainborderexistinglabel'\]\s*=\s*'Bordure héritée'" -and
    $french -match "chainoverlayexistinglabel'\]\s*=\s*'Overlay hérité'") `
    'inherited labels are concise and localised in English and French'
Assert-Contract ($actions -match 'layer-details-accordion[\s\S]*justify-content:\s*center' -and
    $css -match 'layer-details-accordion\s*>\s*summary[^{]*\{[^}]*justify-content:\s*center') `
    'Layer and Overrides summary content is centred in source and generated CSS'
Assert-Contract ($scss -match '\.local-course-banner-builder-source-chain-preview-modal,' -and
    $scss -match 'source-chain-preview-modal-content[\s\S]*background:\s*linear-gradient' -and
    $css -match 'source-chain-preview-modal-content[^{]*\{[^}]*background:\s*linear-gradient') `
    'the body-appended live Source Preview receives Kit tokens and an opaque generated surface'
Assert-Contract ($source -match 'source-chain-preview-modal-header' -and
    $source -match 'source-chain-preview-modal-footer' -and
    $source -match '<span aria-hidden="true">&times;</span>') `
    'the opened Source Preview owns a shared identity header, body, footer and canonical close'
Assert-Contract ($source -match "opener\.classList\.add\('is-focus-returned'\)" -and
    $css -match 'show-source-chain-preview[^}]*\.is-focus-returned') `
    'every close path restores a visibly styled focus target'
Assert-Contract ($source -match 'var isModalPreview = !!root\.closest' -and
    $source -match 'sourcekey && !isModalPreview' -and
    $source -match 'Motion\.swap\(surface, apply' -and
    $source -match 'swapOpacity:\s*0\.28') `
    'modal Desktop and Mobile modes remain transient and use a visible Motion swap'
Assert-Contract ($template -match 'data-preview-title=' -and $template -match 'data-close-label=' -and
    $template -match 'togglesourcechildren, local_course_banner_builder') `
    'runtime labels are localised without the invalid core toggle lookup'
Assert-Contract ($form -match '\$currentisoverlaylayer,\s*\$issitebanneradmin\s*\)' -and
    $form -match 'bool \$issitebanneradmin = false') `
    'site-admin context is passed explicitly into advanced form rendering'
Assert-Contract ($built -match 'local-course-banner-builder-table-shell--sources' -and
    $built -match 'is-focus-returned' -and $built -match 'swapOpacity:.28') `
    'official generated AMD contains the RF4 runtime contract'

Write-Host 'EED-CCB-2026-0050-RF4 static contract passed.'
