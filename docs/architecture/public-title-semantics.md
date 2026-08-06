# Public banner title semantics

## Ownership

The native public banner is one visual surface, but it has three accessibility
responsibilities:

| Part | Owner | Accessibility contract |
| --- | --- | --- |
| Background, image overlays, title-overlay colour and border | `hook_callbacks::render_native_course_banner_html()` and `render_site_banner_html()` | Decorative. Images have empty alternatives and decorative layer wrappers retain `aria-hidden="true"`. |
| Primary page heading | Moodle `#page-header h1` | Canonical `h1`. CCB never emits another primary heading. |
| Extra banner context and slideshow controls | Public banner runtime | A distinct activity or site context title is a native `h2`; controls are mounted in the non-hidden public banner surface with native names and states. |

The public banner surface itself must never carry `aria-hidden="true"`. CSS
backgrounds are not announced, and its image, border, overlay and duplicate
title descendants are independently decorative. This prevents a later button,
link, or slideshow control from inheriting a hidden accessibility state.

## Course title replacement

On a course view, CCB's visual course title duplicates Moodle's course `h1`.
Whether replacement is enabled or disabled, the visual CCB title remains
decorative. When replacement is enabled, CCB applies a visually-hidden style
to Moodle's existing header heading instead of `display: none`; it therefore
remains the sole primary heading in the accessibility tree without changing
the public title's geometry, frame, z-index, or text-selection rule.

Course title replacement applies to the canonical course view only. Activity
pages retain their Moodle activity `h1`. If their configured banner title adds
course context beyond that activity heading, CCB renders it as an `h2`; an
activity-title duplicate remains decorative.

Site-banner titles likewise start as `h2` site context. The mount script only
suppresses that secondary title when its text exactly matches the mounted
Moodle `h1`. This is a de-duplication refinement, not the source of the
semantic `h1`, so JavaScript loss cannot remove the primary page heading.

## Future controls

Interactive public-banner content must be appended to the public banner
surface, never to a decorative layer marked `aria-hidden="true"`. Use native
`button` or `a` elements where possible; provide an accessible name, expose
state such as `aria-pressed` or `aria-current` when applicable, preserve a
visible focus indicator, and keep keyboard focus inside normal page order.
Do not add `aria-label` to a hidden ancestor as a workaround.

## Validation boundary

Batch 2F-A covers Moodle 5.1 with the focused shared Playwright harness at
`local/groupimport/tools/playwright/ccb-banner-public-title-accessibility-2fa.spec.js`.
It records authenticated accessibility snapshots, heading count/text, the
decorative title state, overlay/border geometry, a keyboard control probe, and
fixture/profile cleanup. The site-title and activity-specific branches receive
static ownership review until a reversible site banner and activity fixture are
versioned.

Moodle 4.5 needs a later static port review before runtime validation: locate
its equivalent footer/header injection hook, confirm its page-header `h1`
selector, and apply the same rule that only decorative descendants are hidden.
No Moodle 4.5 worktree is modified by this batch.
