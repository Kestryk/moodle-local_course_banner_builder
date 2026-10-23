# Native Moodle page heading

The site and course banner scopes each have an independent **Hide the native page
title** setting. Both default to off. They are available in the plugin's standard
settings and on the matching Site banner / Course banners administration page.
These two controls address the same stored setting, not separate overrides.

- `hidepagetitle_site`: applies where the existing site-banner eligibility rules
  actually produce a banner, including Dashboard and My courses.
- `hidepagetitle_course`: applies where the existing course-banner rules actually
  produce a banner, including the course and eligible activity pages.

The scope follows the existing global site/course title settings. Category and
course source resolution, inherited layers and visibility rules remain unchanged;
this option does not add per-category overrides or display a missing banner.

The option is independent of `bannertitle_*_enabled` and the older course title
replacement option. It does not enable, remove or change text drawn inside the
banner. Without a renderable banner, the native heading remains visible.

The existing top-of-body output hook emits a scoped heading-visibility rule only
for an eligible payload. The existing banner mount then places that shell before
`#page-header`, instead of after it in the standard format. Only native heading
text is hidden; breadcrumbs and header actions are preserved. Neither all of
`#page-header` nor headers on unrelated pages are hidden. Disabling the option
and loading the page again restores the pre-existing heading and banner policy.
An older, separately enabled course-title replacement still applies; this option
does not silently disable that independent setting.

Standard Moodle admin checkboxes are used. The banner administration forms retain
the existing system capability check, require POST and a valid sesskey, and never
accept a caller-supplied configuration key. No database upgrade, new table, new
AMD asset or theme-specific stylesheet is needed.

## Validation

The native candidate `tools/playwright/candidates/native-page-heading.cjs` accepts
absolute Playwright module, base URL, course id and output directory arguments.
Supply `EASYEDU_TEST_USER` and `EASYEDU_TEST_PASSWORD` through the environment.
Run only on an explicitly owned test/deployment target: it submits the two real
administration forms, briefly disables each option independently, and ends with
both options enabled. The starting state must already have both options enabled.
It closes any Moodle user-tour overlay through its own End tour control before
using the form; it does not remove a backdrop or force a hidden click.

The check covers headings returning when each setting is disabled, independent
site/course scopes, visible banners, and banner-before-header order. Screenshots
and page/console errors are written outside Git. This is not a full theme audit.
The delivered native run used the authorised Community/staging environments;
Moodle 4.5 compatibility additionally requires its own native acceptance run.
The implementation reuses the output hook and admin-setting APIs already present
in the plugin's declared Moodle 4.5 floor; no newer API is introduced.
An isolated probe also executes the policy with the actual Moodle 4.5 `html_writer`
and checks that the hook, checkbox API and header selector exist in that core.
Its configuration doubles and lack of Moodle bootstrap make this a compatibility
probe, not a claim of native Moodle 4.5 acceptance.

## Integration and rollback

Apply the feature commit to the current CCB deployment source, retaining newer
font and initial-mount fixes. Never replace a whole deployed file with a file
from an older branch merely because that branch is the canonical starting point.
For the delivered deployment, a three-way merge plus exact inverse comparison
proved that all later production changes were retained.
The deployed baseline additionally contained sixteen mechanical MoodleCS errors.
Their formatting-only repair was checked against all non-whitespace PHP tokens
before deployment. It does not import unrelated production changes into this
feature branch; retain both the deployed baseline and merged delivery hashes.

To roll back the presentation, disable the two options first. To roll back code,
restore only the backed-up affected files after checking for later changes;
reload the pages and purge Moodle caches if replacing PHP/language files.
The separately delivered temporary Boost course-width patch has its own rollback
and must eventually move into the future EasyEdu theme.
