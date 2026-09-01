[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$pluginRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path
$utf8Strict = [System.Text.UTF8Encoding]::new($false, $true)

function Read-Utf8Strict([string]$RelativePath) {
    $path = Join-Path $pluginRoot $RelativePath
    $bytes = [System.IO.File]::ReadAllBytes($path)
    return $utf8Strict.GetString($bytes)
}

function Assert-Contract([bool]$Condition, [string]$Message) {
    if (-not $Condition) {
        throw "EED-CCB-2026-0076 contract failed: $Message"
    }
    Write-Host "PASS: $Message"
}

$english = Read-Utf8Strict 'lang\en\local_course_banner_builder.php'
$french = Read-Utf8Strict 'lang\fr\local_course_banner_builder.php'
$adminManage = Read-Utf8Strict 'admin_manage.php'
$apercu = 'Aper' + [char]0x00E7 + 'u'
$creation = 'Cr' + [char]0x00E9 + 'ation'

$expectedStrings = [ordered]@{
    "sourcepreviewmodedesktop'] = 'Desktop preview'" = $english
    "sourcepreviewmodemobile'] = 'Mobile preview'" = $english
    "sourcepreviewmodedesktop'] = '$apercu ordinateur'" = $french
    "sourcepreviewmodemobile'] = '$apercu mobile'" = $french
}

foreach ($entry in $expectedStrings.GetEnumerator()) {
    $occurrences = ([regex]::Matches($entry.Value, [regex]::Escape($entry.Key))).Count
    Assert-Contract ($occurrences -eq 1) "the localised entry '$($entry.Key)' exists exactly once"
}

Assert-Contract (
    $adminManage -match "get_string\('sourcepreviewmodedesktop',\s*'local_course_banner_builder'\)" -and
    $adminManage -match "get_string\('sourcepreviewmodemobile',\s*'local_course_banner_builder'\)"
) 'the PHP renderer obtains both captions through Moodle language strings'

Assert-Contract (
    $adminManage -match "'data-source-preview-mode-value'\s*=>\s*'desktop'" -and
    $adminManage -match "'data-source-preview-mode-value'\s*=>\s*'mobile'"
) 'the established desktop and mobile mode values are unchanged'

$codeFiles = @(
    Get-ChildItem -LiteralPath $pluginRoot -File -Filter '*.php'
    Get-ChildItem -LiteralPath (Join-Path $pluginRoot 'classes') -File -Recurse -Filter '*.php'
    Get-ChildItem -LiteralPath (Join-Path $pluginRoot 'templates') -File -Recurse -Filter '*.mustache'
    Get-ChildItem -LiteralPath (Join-Path $pluginRoot 'amd\src') -File -Recurse -Filter '*.js'
    Get-ChildItem -LiteralPath (Join-Path $pluginRoot 'amd\build') -File -Recurse -Filter '*.js'
)

$obsoleteLabels = @(
    'Desktop authoring',
    'Mobile public simulation',
    "$creation sur ordinateur",
    'Simulation mobile publique'
)
$hardCodedCurrentLabels = @(
    'Desktop preview',
    'Mobile preview',
    "$apercu ordinateur",
    "$apercu mobile"
)

$obsoleteHits = @()
$currentLiteralHits = @()
foreach ($file in $codeFiles) {
    $relativePath = $file.FullName.Substring($pluginRoot.Length).TrimStart('\')
    $content = $utf8Strict.GetString([System.IO.File]::ReadAllBytes($file.FullName))
    foreach ($label in $obsoleteLabels) {
        if ($content.IndexOf($label, [System.StringComparison]::Ordinal) -ge 0) {
            $obsoleteHits += "$relativePath => $label"
        }
    }
    foreach ($label in $hardCodedCurrentLabels) {
        if ($content.IndexOf($label, [System.StringComparison]::Ordinal) -ge 0) {
            $currentLiteralHits += "$relativePath => $label"
        }
    }
}

Assert-Contract ($obsoleteHits.Count -eq 0) (
    'obsolete captions are absent from PHP, Mustache, AMD source and AMD build' +
    $(if ($obsoleteHits.Count -gt 0) { ': ' + ($obsoleteHits -join '; ') } else { '' })
)
Assert-Contract ($currentLiteralHits.Count -eq 0) (
    'current captions are not hard-coded into PHP, Mustache or AMD assets' +
    $(if ($currentLiteralHits.Count -gt 0) { ': ' + ($currentLiteralHits -join '; ') } else { '' })
)

Write-Host 'EED-CCB-2026-0076 source-preview label contract passed.'
