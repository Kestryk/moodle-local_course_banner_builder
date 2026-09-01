param(
    [string]$PluginRoot = (Split-Path -Parent $PSScriptRoot)
)

$ErrorActionPreference = 'Stop'

$source = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'amd/src/admin_manage.js')
$build = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'amd/build/admin_manage.min.js')
$php = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'admin_manage.php')
$scss = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'scss/components/_easyedu-adapter.scss')
$en = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'lang/en/local_course_banner_builder.php')
$fr = Get-Content -Raw -LiteralPath (Join-Path $PluginRoot 'lang/fr/local_course_banner_builder.php')

$checks = [ordered]@{
    'Launcher names the large editor' =
        $php.Contains("get_string('openlargebannereditor', 'local_course_banner_builder')") -and
        $php.Contains("get_string('largebannereditortitle', 'local_course_banner_builder')")
    'One live editor is mounted without cloning' =
        $source.Contains('parent.insertBefore(placeholder, sourcePanel)') -and
        $source.Contains('body.appendChild(sourcePanel)') -and
        -not $source.Contains('sourcePanel.cloneNode(true)')
    'The exact editor position is restored' =
        $source.Contains('mount.placeholder.parentNode.replaceChild(mount.panel, mount.placeholder)') -and
        $source.Contains('localCourseBannerBuilderRestoreLargeSourcePreviewMount(modal)')
    'Desktop authoring threshold is explicit' =
        $source.Contains('document.documentElement.clientWidth < 1024') -and
        $source.Contains("localCourseBannerBuilderGetJsString(`n            'largeeditorrequiresdesktop'")
    'No automatic persistence route was added' =
        -not $source.Contains('largeWorkspacePayload') -and
        -not $source.Contains('largeWorkspaceSave')
    'Authoring shell retains live pointer controls' =
        $scss.Contains('source-chain-preview-modal--authoring') -and
        $scss.Contains('data-source-preview-large-workspace="1"')
    'English and French strings are present' =
        $en.Contains("`$string['openlargebannereditor']") -and
        $en.Contains("`$string['largeeditorrequiresdesktop']") -and
        $fr.Contains("`$string['openlargebannereditor']") -and
        $fr.Contains("`$string['largeeditorrequiresdesktop']")
    'Generated AMD contains the live mount contract' =
        ($build.Length -gt 1000) -and
        $build.Contains('data-source-preview-large-workspace') -and
        $build.Contains('largeeditorrequiresdesktop')
}

$failed = @($checks.GetEnumerator() | Where-Object { -not $_.Value })
$checks.GetEnumerator() | ForEach-Object {
    $status = if ($_.Value) { 'PASS' } else { 'FAIL' }
    Write-Host "$status $($_.Key)"
}

if ($failed.Count -gt 0) {
    throw "Large authoring shell contract failed: $($failed.Key -join ', ')"
}
