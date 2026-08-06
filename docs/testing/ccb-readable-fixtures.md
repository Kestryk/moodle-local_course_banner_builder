# CCB readable visual-review fixture

The supervised helper `tools/playwright/ccb-readable-banner-fixture.php` adds
three named, high-contrast SVG layers to the active course source. It is
idempotent: existing layers are preserved and a layer is added only when its
fixture name is absent.

## Usage

Run from the Moodle 5.1 plugin checkout while holding the
`moodle51-active-fixture-write` lease:

```powershell
$env:EASYEDU_MOODLE_CONFIG = Join-Path $MoodleRoot 'config.php'
& $MoodlePhp -f (Join-Path $PluginRoot 'tools/playwright/ccb-readable-banner-fixture.php') -- 11 3
Remove-Item Env:EASYEDU_MOODLE_CONFIG -ErrorAction SilentlyContinue
```

The helper appends these layers to source `category:3` for course `11`:

- `CCB QA readable - blue base - 128px policy`;
- `CCB QA readable - alignment markers - 100 200`;
- `CCB QA readable - contrast label - activity context`.

The helper uses Moodle's CLI bootstrap, draft file area and File API, then
delegates element persistence to the CCB manager. It does not run Playwright,
change banner CSS/SCSS, remove existing elements or delete a source.

## Evidence

The mutation report is written outside Git under the configured EasyEdu artifact
root. Review the JSON report together with the rendered course page before any
visual acceptance decision. Browser validation remains a separate leased step.

The plugin declares Moodle 4.5 support in `version.php`; this fixture was
created on the Moodle 5.1 runtime. The implementation follows the Moodle 5.1
CLI and File API documentation and has only been PHP-linted here; no Moodle
4.5 execution was claimed.
