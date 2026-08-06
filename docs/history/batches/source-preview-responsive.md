# CCB source-preview responsive audit

## Status

Gate 2 browser validation blocked after the source/CSS patch. The latest
controlled run passed 3/5 cells but two 100% cells failed during Moodle
login/navigation before CCB geometry assertions. Read-only Apache correlation
shows valid 200/303 responses arriving after the protected 20–30 second
navigation budgets; this is currently classified as local Moodle navigation
latency, not a CCB geometry failure.

## Scope

The selected source visual editor in `admin_manage.php`, including its canvas,
preview mode switcher, filmstrip/visibility row, primary actions and control
rail. The CCB scope does not include Moodle Boost navigation, Moodle core menu
surfaces, EasyEdu UI Kit primitives, GroupImport, or other global UI work.

## Correction

The source preview now has a visible canvas card and an inner preview surface.
The mode control, action group and side controls have bounded surfaces and
`min-width: 0` ownership. Primary actions share a row at sufficient width and
become a full-width vertical stack below the 992px responsive breakpoint.
Labels wrap instead of being clipped; no DOM order, `data-action` contract,
AMD handler, banner ratio, 128px policy, or H1/H2 contract was changed.

## Validation

The dedicated single-test supervisor selected exactly one Playwright test before
fixture work, acquired the Moodle/course-11 lease, and restored the original
course format, category and profile in `finally`.

The historical pre-Gate-2 evidence run recorded these required cells as passed:

- 1600x900 at 100%;
- 1024x768 at 100%;
- 390x844 at 100%;
- 1600x900 at genuine native 200%;
- 390x844 at genuine native 200%.

The native 200% cells used Chromium per-host zoom preferences (`innerWidth`
800/195 and `devicePixelRatio` 2) and external CDP `Page.captureScreenshot`
captures. No desktop capture was used. Preview overflow, element escape,
overlap and text clipping were empty in every cell; console errors and
non-aborted failed requests were empty.

That historical evidence is external to Git and Syncthing at:

`C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\responsive\supervised\ccb-source-preview-responsive-20260728T100931534Z-19496`

The post-patch Gate 2 evidence is retained separately at:

`C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\responsive\supervised\ccb-source-preview-responsive-20260728T125908577Z-22268`

Its discovery selected exactly one test and its cells were executed
sequentially. `390x844` at 100%, `1600x900` at native 200% and `390x844` at
native 200% passed. `1600x900` at 100% failed during login/navigation and
`1024x768` at 100% timed out in `page.goto`; the latter reported no console or
failed requests. Cleanup restored course/category/format state and removed
profiles (`cleanup.json` `complete=true`). This artifact is the current gate
authority; no UI-green conclusion may be drawn from the historical run alone.

## Handoff

The Moodle administrative menus visible behind the CCB surface remain a
separate global UI chantier. Their overflow ownership should be reviewed in
Moodle Boost and the EasyEdu UI Kit using the kit menu contracts; no such files
were modified here.

## Release state

No staging, commit, push, build, Batch 2F-B run, or GroupImport change was
performed.

## Latest Gate 2 update — 2026-07-29

The latest one-test matrix passed 4/5 cells. Desktop native 200% failed before
geometry assertions because `/my/` returned HTTP 500 and cache-store renames
reported `Access denied`. GroupImport requests were present in the same
runtime window, and the final lease audit showed live EasyStud and CCB owners
concurrently. The gate is therefore blocked on global runtime lease
exclusivity. The evidence is external at:

`C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\responsive\supervised\ccb-source-preview-responsive-20260729T092709891Z-12348`
