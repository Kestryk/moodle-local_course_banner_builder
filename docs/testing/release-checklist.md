# Release test checklist

## 1. Preflight

- [ ] Work from the intended Course Banner Builder branch.
- [ ] Record `git status --short --branch`.
- [ ] Confirm the Moodle 4.5 minimum and Moodle 5.1 development environments.
- [ ] Confirm the plugin version, release, maturity, and required Moodle build.
- [ ] Review database savepoints when the plugin version changed.
- [ ] Use a database backup or disposable Moodle site.
- [ ] Record the fixture revision and test asset licences.
- [ ] Confirm no credentials, local reports, draft files, or browser profiles
      are tracked.

## 2. Moodle coding gates

Target command set:

```bash
moodle-plugin-ci phplint
moodle-plugin-ci phpcs --max-warnings 0
moodle-plugin-ci phpdoc --max-warnings 0
moodle-plugin-ci validate
moodle-plugin-ci savepoints
moodle-plugin-ci mustache
moodle-plugin-ci grunt --max-lint-warnings 0
moodle-plugin-ci phpunit
moodle-plugin-ci behat --profile chrome
```

- [ ] Every command passes, or the release evidence names the skipped command,
      reason, owner, and follow-up issue.
- [ ] PHPCS and PHPDoc run with zero accepted warnings for changed files.
- [ ] Language files contain data assignments only and remain ordered.
- [ ] User input uses Moodle parameter APIs with specific parameter types.
- [ ] Plugin configuration uses `get_config()`, `set_config()`, and
      `unset_config()`.
- [ ] Database table names, privacy metadata, backup/restore, and tests agree.
- [ ] AMD source and minified build artifacts are synchronized.
- [ ] No new inline CSS or JavaScript was introduced.

## 3. Local focused checks

From the plugin root:

```powershell
powershell -ExecutionPolicy Bypass -File .\scss\build.ps1
node --check .\amd\src\admin_manage.js
node --check .\amd\src\easyedu_guide.js
node --check .\amd\src\slideshow_admin.js
node --test .\tests\amd\easyedu_guide_focus_return.test.mjs
node --test .\tests\ui\easyedu_navigation_contract.test.mjs
git diff --check
```

PHP lint all plugin files:

```powershell
Get-ChildItem -Recurse -Filter *.php |
    ForEach-Object { php -l $_.FullName }
```

Run the existing PHPUnit test through the Moodle PHPUnit environment or
`moodle-plugin-ci phpunit`. The current test file is:

```text
tests/manager_crop_test.php
```

- [ ] SCSS compilation succeeds and only expected generated CSS changes.
- [ ] AMD syntax checks pass.
- [ ] PHP syntax checks pass.
- [ ] Crop PHPUnit tests pass, or GD-dependent coverage is explicitly skipped.
- [ ] `git diff --check` reports no whitespace errors.
- [ ] Desktop Navigation remains a flat EasyStud rail without a resting
      background, border or capsule per destination; icons stay plain on
      desktop and use the EasyStud neutral and active tile states in the
      compact panel. The compact trigger retains its 44px resting target,
      displays its complete localized label, and the Guide launcher exposes one
      visible help label without a duplicate popover.

## 4. Installation and upgrade

- [ ] Install the plugin on a clean Moodle 4.5 site.
- [ ] Install the plugin on a clean Moodle 5.1 site.
- [ ] Upgrade from the previous released plugin version with existing sources.
- [ ] Confirm database migrations complete without a stale upgrade lock.
- [ ] Confirm renamed tables and configuration migration retain data.
- [ ] Purge caches and reload all four administration views.
- [ ] Confirm uninstall/cleanup behaviour on disposable data only.

## 5. Capability checks

- [ ] Administrator can open course, site, slideshow, and transfer views.
- [ ] System manager with `local/course_banner_builder:manage` can open them.
- [ ] Teacher without the system capability is denied.
- [ ] Student is denied.
- [ ] Normal users can see generated banners only where configured.

Do not report course-context teacher management as supported unless the
capability model changes from its current system context.

## 6. Functional regression

Use the fixture set defined in `functional-protocol.md`.

- [ ] Create/select category and custom-field sources.
- [ ] Verify source inheritance and priority modes.
- [ ] Upload, drop, replace, and delete images.
- [ ] Reject unsupported and oversized files with localised messages.
- [ ] Add multiple image layers and preserve independent draft state.
- [ ] Move, resize, crop, validate, cancel, and crop a second time.
- [ ] Reorder by drag/drop and non-drag controls.
- [ ] Verify border, overlay, title, and slideshow z-order.
- [ ] Save, reload, and compare modal, general preview, and final banner.
- [ ] Export and import settings/assets on disposable sites.
- [ ] Verify course overview thumbnails and fallback images.
- [ ] Check empty, loading, validation-error, and server-error states.

## 7. Accessibility and guide

- [ ] Run `tests/behat/accessibility.feature` with Moodle axe enabled.
- [ ] Run the opt-in Playwright axe smoke when dependencies are available.
- [ ] Complete the keyboard protocol in `accessibility.md`.
- [ ] Verify focus enters and leaves every modal correctly.
- [ ] Verify focus remains visible in sticky preview layouts.
- [ ] Verify sliders have working numeric-input alternatives.
- [ ] Verify layer ordering and image adjustment have non-drag alternatives.
- [ ] Verify guide slides, locked states, guided paths, checklist, highlights,
      and return panel.
- [ ] Record any axe exclusion using the documented exclusion policy.

## 8. Responsive and theme matrix

- [ ] Standard Moodle theme on Moodle 4.5.
- [ ] Standard Moodle theme on Moodle 5.1.
- [ ] EasyEdu theme where available.
- [ ] Desktop, tablet portrait/landscape, and narrow mobile widths.
- [ ] 200% browser zoom.
- [ ] Long English and translated labels.
- [ ] Light and dark configured image/overlay/title combinations.
- [ ] No double scroll, covered footer, hidden action, or off-screen modal.

## 9. EasyEdu platform matrix

Before merge, run the current manual platform target against the latest branch
commit:

```powershell
gh workflow run "Moodle plugin matrix" `
    --ref <branch-name> `
    -f target=course-banner-builder-45
```

Future promotion:

- [ ] Add a Moodle 5.1 CCB target.
- [ ] Include the Behat accessibility smoke after fixture validation.
- [ ] Include stable core-flow Behat scenarios.
- [ ] Keep Playwright crop/guide flows manual until deterministic.

## 10. Release evidence

Record:

| Evidence | Value |
| --- | --- |
| Plugin commit | |
| Plugin version/release | |
| Moodle 4.5 result | |
| Moodle 5.1 result | |
| PHP version | |
| Database | |
| Browser version | |
| Theme(s) | |
| Fixture revision | |
| Moodle matrix run | |
| Skipped checks/issues | |

- [ ] Review the package manifest and SHA-256 hash.
- [ ] Confirm the ZIP has the expected plugin directory as its root.
- [ ] Update release notes and the plugin changelog.
- [ ] Obtain explicit approval before merging to the production branch.
