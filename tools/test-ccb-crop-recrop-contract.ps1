[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$pluginRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path
$spec = Get-Content -LiteralPath (Join-Path $pluginRoot 'tools\playwright\ccb-crop-recrop.spec.js') -Raw
$runner = Get-Content -LiteralPath (Join-Path $pluginRoot 'tools\playwright\Invoke-CCBCropRecropValidation.ps1') -Raw
$fixture = Get-Content -LiteralPath (Join-Path $pluginRoot 'tools\playwright\ccb-image-modal-transform-fixture.php') -Raw

$checks = [ordered]@{
    'One named EED-CCB-2026-0043-QA1 scenario exists' = $spec -match "test\('EED-CCB-2026-0043-QA1 Crop and Recrop preserve image placement across widths'";
    'Exactly one Playwright test is declared' = ([regex]::Matches($spec, '(?m)^test\(').Count -eq 1);
    'Desktop and narrow widths are explicitly covered' = $spec -match 'for \(const width of \[1440, 760\]\)';
    'Initial Crop and Recrop Cancel are covered' = (($spec -match 'afterInitialCrop') -and ($spec -match 'afterCancel') -and ($spec -match 'cancel-preview-crop'));
    'Undo and Redo are covered' = (($spec -match 'undo-modal-preview-change') -and ($spec -match 'redo-modal-preview-change'));
    'Draft/image switching uses the user-facing selector' = (($spec -match 'afterDraftSwitch') -and ($spec -match 'data-draft-preview-select') -and ($spec -match 'data-active-draft-index'));
    'Crop gestures use proportional non-saturating movement' = (($spec -match 'changeCrop\(page, form, -0\.12\)') -and ($spec -match 'changeCrop\(page, form, 0\.05\)'));
    'Gesture targets the stable active southeast handle and proves pointer ownership' = (($spec -match 'waitForStableBox') -and ($spec -match 'preview-image-layer--crop-editing') -and ($spec -match 'data-preview-crop-handle="se"') -and ($spec -match 'elementFromPoint'));
    'Crop payload change is observed before Apply' = (($spec -match 'Active Crop payload must change before Apply') -and ($spec -match '\.not\.toBe\(liveCropBefore\)'));
    'Placement and geometry are asserted independently from Crop fields' = (($spec -match 'assertPlacement') -and ($spec -match 'expect\(.*\.crop\)'));
    'Crop fields resolve Moodle id or generated name and are bound to draft state' = (($spec -match 'const cropValue = name') -and ($spec -match "'#id_' \+ name") -and ($spec -match '\[name=') -and ($spec -match 'assertCropBinding'));
    'Early and state-change captures are retained' = (($spec -match 'crop-recrop-.*-before\.png') -and ($spec -match 'after-initial') -and ($spec -match 'after-cancel'));
    'Second upload preserves a distinct draft through Moodle Rename confirmation' = (($spec -match 'const completeUpload') -and ($spec -match 'Rename to') -and ($spec -notmatch 'Overwrite'));
    'Supervisor discovers exactly one test before credentials or fixture work' = (($runner -match 'playwright-discovery') -and ($runner -match 'Total:\\s\+1\\s\+test') -and ($runner -match 'if \(\$DiscoveryOnly\)'));
    'Supervisor uses the safe category and draft cleanup fixture' = (($runner -match 'ccb-image-modal-transform-fixture\.php') -and ($fixture -match 'delete_area_files') -and ($fixture -match 'categoryRemoved'));
    'Supervisor clears credentials and releases the lease' = (($runner.Contains('Remove-Item -LiteralPath (''Env:'' + $name)')) -and ($runner -match 'Release-EasyEduResourceLease'));
    'Supervisor forbids retries and serializes the browser' = (($runner -match '--retries=0') -and ($runner -match '--workers=1'));
}

$failed = @($checks.GetEnumerator() | Where-Object { -not $_.Value })
$checks.GetEnumerator() | ForEach-Object { '{0}: {1}' -f ($(if ($_.Value) {'PASS'} else {'FAIL'})), $_.Key }
if ($failed.Count -gt 0) { throw "Crop/Recrop QA contract failed with $($failed.Count) check(s)." }
