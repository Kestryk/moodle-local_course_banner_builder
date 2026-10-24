# Course card banner and title (K23-32)

## Root cause: a stale core cache, not a plugin sync failure

Course pages already render the configured banner correctly (K23-30/K23-31):
they read `manager::get_course_banner_image_url()` / regenerate the banner on
every request and never go through core's course-image cache.

Native course cards (Dashboard, My courses, course category listings) go
through a different path: `\core_course\external\course_summary_exporter::
get_course_image()` reads `\cache::make('core', 'course_image')`, an
application cache whose datasource (`\core_course\cache\course_image`) caches
the result of scanning `course/overviewfiles` **including a negative (null)
result**, "to let it be cached as false". Moodle's own `course/db/caches.php`
definition for `course_image` declares **no `invalidationevents`** — the only
place core clears it is an explicit `->delete($courseid)` call from the course
edit form (`course/lib.php`).

`manager::sync_course_overview_image()` already writes/deletes the managed
file in `course/overviewfiles` correctly and already calls
`purge_course_caches()` (`cache_helper::purge_by_event('changesincourse')`),
but that event purge never touches `course_image` because core does not wire
it to that event. The first time a course's cards were viewed before a
source/layer was configured (or before a sync ran), `course_image` permanently
cached "no image" for that course id, and no later sync ever cleared it. That
reproduces exactly: banner renders fine on the course page, every card for
that course stays blank.

Fix: `manager::sync_course_overview_image()` now also calls
`\cache::make('core', 'course_image')->delete($courseid)` (new
`invalidate_course_image_cache()` helper) on every exit path that changes, or
confirms the absence of, the managed overview file.

## Card title

The course banner title is a single, plugin-wide on/off setting
(`bannertitle_course_enabled`) plus a handful of style tokens (font family,
colour, weight, caps, overlay scrim) — not per-course data. `coursecards.js`
already knows, client-side, which cards are showing a CCB-managed banner
(`isManagedBannerUrl()`): it reuses that same signal to decide whether to
echo the title, and reads the course name straight from the card's own
already-rendered title text node (`.coursename` et al.) instead of an extra
per-course request (which would also need `require_login($course)` against
courses the current viewer may not even be enrolled in on a category listing).

The full banner title system supports absolute `x`/`y` positioning, custom
point sizes up to 480px, frames and shadows tuned for a 1200×… canvas; none of
that is reused as-is for a ~360×180 card. Only the typography tokens (colour,
font-family, weight, caps, overlay scrim) are reused; layout is a centered
overlay with `-webkit-line-clamp: 2` + ellipsis (K23-32 bis), so an overly long title is
truncated cleanly instead of being forced to fit or overflowing the card.

## Validation

- `php -l` on `classes/manager.php` and `classes/hook_callbacks.php` (PHP 8.3).
- `node --check` on `amd/src/coursecards.js` and the rebuilt
  `amd/build/coursecards.min.js`.
- Server-side CLI reproduction on the local Moodle 5.3 instance: manually
  primed `core/course_image` with a stale `null` entry (simulating a
  pre-configuration card view), called `sync_course_overview_image()`, and
  confirmed `course_summary_exporter::get_course_image()` goes from `false` to
  the fresh managed overview URL.
- jsdom-based unit run of the real `amd/src/coursecards.js` against a Boost
  `coursecard.mustache`-shaped fixture (two cards: one CCB-managed, one with a
  teacher-uploaded image): confirms the title overlay is injected only on the
  CCB-managed card, with the correct text, is idempotent across rescans, and
  is fully suppressed when `cardTitle.enabled` is false.
- Manual rebuild of `styles.css` (`scss/build.ps1`, sass 1.89.2) and
  `amd/build/coursecards.min.js` (terser 5.51.2) so the shipped assets match
  source.

Still open for a human/browser pass: actual Dashboard/My courses/category
screenshots at desktop, tablet and mobile widths, short vs. very long FR/EN
titles, and with/without a configured title, on a real browser — this batch
could not safely forge an authenticated session on the shared local instance.
