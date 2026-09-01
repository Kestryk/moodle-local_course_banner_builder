[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$pluginRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path
$utf8 = [System.Text.UTF8Encoding]::new($false, $true)

function Read-ProjectFile([string]$RelativePath) {
    $path = Join-Path $pluginRoot $RelativePath
    return $utf8.GetString([System.IO.File]::ReadAllBytes($path))
}

function Assert-Contract([bool]$Condition, [string]$Message) {
    if (-not $Condition) {
        throw "EED-CCB-2026-0047 contract failed: $Message"
    }
    Write-Host "PASS: $Message"
}

$source = Read-ProjectFile 'amd\src\coursecards.js'
$build = Read-ProjectFile 'amd\build\coursecards.min.js'
$hooks = Read-ProjectFile 'classes\hook_callbacks.php'
$manager = Read-ProjectFile 'classes\manager.php'
$cardRoute = Read-ProjectFile 'card.php'
$scss = Read-ProjectFile 'scss\components\_native-banner-core.scss'
$css = Read-ProjectFile 'styles.css'

Assert-Contract (
    $hooks -match "js_call_amd\('local_course_banner_builder/coursecards',\s*'init'" -and
    $source -match 'document\.querySelectorAll\(.\[data-course-id\], \[data-courseid\].\)'
) 'the footer hook initializes the progressive enhancer for native Moodle course cards'

Assert-Contract (
    $source -match "\.dashboard-card-img" -and
    $source -match "\.dashboard-list-img" -and
    $source -match "\.coursebox" -and
    $source -match "\.courseimage"
) 'the enhancer covers wide Dashboard and My courses targets plus legacy courseboxes'

Assert-Contract (
    $source -match "url \+ '&variant=square'" -and
    $source -match 'ratio >= 0\.85 && ratio <= 1\.15' -and
    $cardRoute -match "\$variant === 'square'"
) 'square targets select the dedicated square card route without changing wide targets'

Assert-Contract (
    $manager -match 'protected const CARD_CANVAS_WIDTH = 1200;' -and
    $manager -match 'protected const CARD_CANVAS_HEIGHT = 540;' -and
    $manager -match 'protected const CARD_SQUARE_CANVAS_SIZE = 960;' -and
    $manager -match '(?s)build_course_card_square_image\(.+?self::CARD_SQUARE_CANVAS_SIZE,.+?self::CARD_SQUARE_CANVAS_SIZE'
) 'the backend retains separate dense wide and square generated canvases'

Assert-Contract (
    $manager -match '(?s)get_course_card_image_url\(.+?course_custom_overview_images_enabled\(\).+?course_has_custom_overview_image\(.+?return null;' -and
    $manager -match '(?s)sync_course_overview_image\(.+?course_has_custom_overview_image\(.+?purge_course_caches\(\);\s*return;'
) 'a teacher-managed Moodle overview image keeps priority over generated CCB cards'

Assert-Contract (
    $source -match '(?s)const replaceBackground = function.+?if \(!isManagedBannerUrl\(computedUrl\)\) \{\s*return;' -and
    $source -match '(?s)const replaceImageSource = function.+?!isManagedBannerUrl\(target\.src\).+?return;' -and
    $source -match '(?s)applyWhenLoadable.+?image\.onload = function \(\) \{\s*callback\(url\);' -and
    $source -notmatch 'image\.onerror\s*=.+?(style\.|src\s*=)' -and
    $cardRoute -match '(?s)if \(!\$url\) \{\s*send_file_not_found\(\);\s*\}'
) 'replacement is limited to plugin-managed images and leaves Moodle fallback content intact on failure'

Assert-Contract (
    $source -match "'IntersectionObserver' in window" -and
    $source -match "rootMargin: margin \+ 'px 0px'" -and
    $source -match "image\.loading = 'lazy'" -and
    $source -match "target\.loading = 'lazy'" -and
    $source -match "decoding = 'async'"
) 'near-viewport observation and native lazy asynchronous image decoding are preserved'

Assert-Contract (
    $scss -match '(?s)\.dashboard-card-img\.local-course-banner-builder-course-card-thumb,.+?background-size:\s*contain !important;' -and
    $scss -match '(?s)img\.local-course-banner-builder-course-card-thumb.+?object-fit:\s*contain !important;' -and
    $scss -match '(?s)\.coursebox \.local-course-banner-builder-course-card-thumb,.+?aspect-ratio:\s*1 / 1;' -and
    $css -match '(?s)\.dashboard-card-img\.local-course-banner-builder-course-card-thumb,.+?background-size:\s*contain !important;' -and
    $css -match '(?s)\.coursebox \.local-course-banner-builder-course-card-thumb,.+?aspect-ratio:\s*1/1;'
) 'source and generated CSS retain contained wide cards and bounded square courseboxes'

Assert-Contract (
    $manager -match '(?s)sync_course_overview_image\(.+?delete_managed_course_card_images\(.+?build_course_card_image\(.+?build_course_card_square_image\(' -and
    $manager -match 'delete_area_files\(\$contextid,\s*.local_course_banner_builder.,\s*self::CARD_FILEAREA,\s*0\)' -and
    $manager -match 'MANAGED_CARD_PREFIX .+?\$revision .+?.png' -and
    $manager -match 'MANAGED_CARD_SQUARE_PREFIX .+?\$revision .+?.png'
) 'synchronisation retracts stale generated files before publishing revisioned wide and square cards'

Assert-Contract (
    $build -match 'IntersectionObserver' -and
    $build -match 'variant=square' -and
    $build -match 'course_banner_builder_auto_' -and
    $build -match 'local-course-banner-builder-course-card-thumb' -and
    $build -match 'local-course-banner-builder-course-card-root'
) 'the official AMD build contains the audited course-card lifecycle'

Write-Host 'EED-CCB-2026-0047 course-card thumbnail contract passed.'
