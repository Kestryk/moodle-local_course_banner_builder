[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$pluginRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path
$source = Get-Content -LiteralPath (Join-Path $pluginRoot 'amd\src\admin_manage.js') -Raw
$build = Get-Content -LiteralPath (Join-Path $pluginRoot 'amd\build\admin_manage.min.js') -Raw
$map = Get-Content -LiteralPath (Join-Path $pluginRoot 'amd\build\admin_manage.min.js.map') -Raw | ConvertFrom-Json
$effectiveCropAspectCalls = [regex]::Matches(
    $source,
    'localCourseBannerBuilderGetEffectivePreviewImageDimensions\(\s*naturalWidth,\s*naturalHeight,\s*cropState,\s*false\s*\)'
).Count

$checks = [ordered]@{
    'Crop remains an inner-image transform' =
        ($source -match '(?s)function localCourseBannerBuilderApplyCropToImageStyles.*?10000 / crop\.width.*?transform: translate');
    'Modal selection shell and standalone visual share the effective cropped aspect' = $effectiveCropAspectCalls -eq 2;
    'No placed renderer reuses the complete-source aspect after Crop' =
        ($source -notmatch 'localCourseBannerBuilderGetEffectivePreviewImageDimensions\(\s*naturalWidth,\s*naturalHeight,\s*cropState,\s*true\s*\)');
    'Standalone preview shares the persisted Crop aspect for locked placement' =
        ($source -match '(?s)function localCourseBannerBuilderSyncStandalonePreviewLayer\(previewRoot, layer\).*?localCourseBannerBuilderGetEffectivePreviewImageDimensions\(\s*naturalWidth,\s*naturalHeight,\s*cropState,\s*false\s*\)');
    'Crop styles still enlarge and translate the inner image' = ($source -match 'width: .+10000 / crop\.width') -and ($source -match 'transform: translate');
    'Generated AMD is present and contains Crop rendering' = ($build.Length -gt 1000) -and ($build -match 'data-preview-crop-width');
    'Generated source map is valid and embeds source content' = ($map.version -eq 3) -and $map.sourcesContent -and ($map.sources.Count -gt 0);
}

$failed = @($checks.GetEnumerator() | Where-Object { -not $_.Value })
$checks.GetEnumerator() | ForEach-Object { '{0}: {1}' -f ($(if ($_.Value) {'PASS'} else {'FAIL'})), $_.Key }
if ($failed.Count -gt 0) { throw "Crop placement contract failed with $($failed.Count) check(s)." }
