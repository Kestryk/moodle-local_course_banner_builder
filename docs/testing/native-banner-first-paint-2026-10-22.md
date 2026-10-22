# Native banner first paint

The standard banner's 1320 px maximum was already applied in the initial HTML.
The flash came from its temporary body-level parent: on a native Moodle 5.3
course page it painted at x=140, y=72, width=1320 before mounting into the
814 px content container at x=393, y=194.390625. First contentful paint was
6388 ms; the mounted geometry appeared at 6535.3 ms. CSS was present before
the banner; full-width JavaScript alignment was not involved in this case.

The runtime CSS now hides the shell until the existing `--mounted` state.
Mounting, formats, final dimensions, titles and configured identities are
unchanged. A `noscript` override preserves the previous static visibility.
This fixes both the native course shell and the site shell with one existing
state rule, without introducing another layout recipe or changing mount timing.

Native Chromium validation captures mutation and animation-frame samples from
document start, with first-contentful-paint timing, parent, format, rectangle,
mounted state and computed visibility. The corrected native course sample is
hidden before mounting and visible directly at its final 814 px width; no
JavaScript exception occurs. Repeat with site/course banners, 1600/390 and
JavaScript disabled. The latter must remain visible without `--mounted`.

For deployment, preserve and hash the current callback file first. Refuse to
overwrite concurrent edits: apply only the small visibility/noscript patch when
other changes exist. Keep the source commit and deployment backup separately.
Rollback restores only the backed-up callback if the deployed file still matches
this patch; do not revert newer changes or site/banner configuration.

PHP syntax passes. Moodle Codechecker reports two pre-existing foreach spacing
errors (parent line 1117, patched line 1119); this change adds no new errors.

The reusable native probe is `tools/playwright/ccb-native-banner-first-paint.cjs`.
Pass the base URL, banner page path and absolute evidence directory; set
`PLAYWRIGHT_MODULE` to an installed Playwright module if necessary. It uses an
authorised `CCB_STORAGE_STATE`, or the site's existing guest login, without
creating accounts. It asserts first-paint visibility, final containment at
1600/390, no JavaScript exceptions and the no-script fallback.

The final native checks passed on both staging and production at 1600/390,
including static visibility with JavaScript disabled. Remote guest access led
to the course enrolment page, which renders the same native course banner;
the authenticated course-content reproduction was performed locally. The site
banner variant is not rendered for guest users and is not claimed as a remote
authenticated site-banner test.
