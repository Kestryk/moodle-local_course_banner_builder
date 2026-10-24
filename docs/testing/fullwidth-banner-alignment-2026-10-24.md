# Full-width banner alignment (K23-54)

The full-width formats (`fullwidthtop`, `fullwidthtopcompact`,
`fullwidthtopinset`) position the banner shell with JavaScript
(`alignFullWidthBanner`) instead of static CSS, to let it bleed past the
page's normal content box. That alignment used `findScrollFrame()` — a
heuristic meant only to find the right ancestor to flag with an overflow-x
guard class — as the width/left reference for the banner itself. That
heuristic has an early return: any ancestor `#page` with the `drawers` class
is returned immediately, which is true on virtually every Boost page whether
or not it is actually wider than the real visible content column (a docked
nav drawer taking flex space, or the theme's "limit content width" setting).
The banner then bled to the edges of that oversized frame, while the native
content below it (`#region-main`) stayed correctly laid out and narrower —
a visible misalignment on every view, not only the catalogue/dashboard.

## Fix

Introduce `findContentFrame()`, used only for the banner's width/left
target: it prefers `#region-main` (present on every page type — course,
catalogue, dashboard, course settings), falls back to the shell's own
`.main-inner` ancestor, and only falls back to the old scrolling-frame
heuristic if neither exists. `findScrollFrame()` itself is unchanged and
still used solely to flag the real scrolling ancestor with the
`local-course-banner-builder-fullwidth-scroll-frame` overflow-x guard class.

## Native regression protocol

On an isolated Moodle 5.1 instance with an authenticated test account,
measure `.local-course-banner-builder-native-shell` and `#region-main`
bounding rectangles at 1600 px wide, headless (Playwright, node, no visible
window), with `coursebannerformat`/`sitebannerformat` set to `fullwidthtop`:

- Dashboard (`/my/courses.php`)
- Course catalogue (`/course/index.php`)
- Course page (`/course/view.php?id=<id>`)
- Course settings (`/course/edit.php?id=<id>`)

Acceptance: banner and `#region-main` left/right edges match within 0.5 px.

Measured before the fix (course id 71, `claude-sm-test`, viewport 1600×1000):

| View | Banner (left–right) | `#region-main` (left–right) | Match |
|---|---|---|---|
| Dashboard | 0–1575 | 380.5–1194.5 | No |
| Catalogue | 0–1575 | 380.5–1194.5 | No |
| Course page | 0–1575 | 380.5–1194.5 | No |

After the fix, same environment:

| View | Banner (left–right) | `#region-main` (left–right) | Match |
|---|---|---|---|
| Dashboard | 380.5–1194.5 | 380.5–1194.5 | Yes |
| Catalogue | 380.5–1194.5 | 380.5–1194.5 | Yes |
| Course page | 380.5–1194.5 | 380.5–1194.5 | Yes |
| Course settings | 380.5–1194.5 | 380.5–1194.5 | Yes |

The `standard` format banner (K23-31) was re-measured unchanged on the
course page: banner and `#region-main` still match exactly (393–1207,
814 px) — no regression. `findScrollFrame()`/the overflow-x guard class
and the `fullwidthtopinset` side-inset calculation are otherwise untouched
except that their base width now comes from the content frame instead of
the raw scrolling frame.

## Follow-up — visible content edge (K23-54b, 24/10/2026)

`#region-main` is the layout column, but the visible content sits inset inside it
(padded `[role="main"]` wrapper, `.block.card` gutter on Dashboard/My courses, course
sections, catalogue tree): 15px per side on every page type. `measureContentEdges()`
now walks down from `#region-main` through unframed full-width wrappers, stops at the
first framed box or multi-child level, and aligns the banner to that outer edge. No
Moodle/theme margin is changed; fallback is the previous `#region-main` box.

Measured on :8054 (1600px and 1280px viewports), banner vs visible content:
Dashboard, My courses, catalogue, course page: 15px/15px before, 0px/0px after.
Simulated community layout (no wrapper padding, 22px block-region margin): 0px/0px.
