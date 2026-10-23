# Standard banner alignment

The standard native banner shell reuses Moodle's `header-maxwidth` class for
its width ceiling. This class can bring horizontal padding of its own. CCB
already places the shell inside the page's content box, so it must not inset
the banner a second time. Its standard shell therefore explicitly has zero
horizontal padding.

After mounting, the standard banner has zero top margin. The previous internal
one-rem margin could collapse through the shell and introduce a gap after the
page header. Its bottom spacing, ratio, border, title, format and mounting
visibility remain unchanged. Full-width and content-wide formats retain their
separate layout policies.

## Native regression protocol

On an isolated Moodle 5.3 course with an active standard CCB banner, measure
the shell, visible banner, native page header and main content rectangles.
Test course view, section view, Student Management and Mass Import at 1600,
1280, 768 and 390 px, with editing both enabled and disabled. Check actual
body editing state rather than assuming every edit switch triggers navigation.

Acceptance: banner and content left/right edges differ by at most 0.5 px,
standard shell has no horizontal padding, mounted standard banner has zero
top margin, title is visible when enabled, and no frame paints a native shell
before its mounted state. Keep the no-script visibility fallback.

For comparison with additional Boost width customisation, inject the exact
width rules into the test browser only. A page-width policy must not be used
to compensate for padding internal to the banner component. Do not edit
Moodle core or change shared theme settings for this component correction.
