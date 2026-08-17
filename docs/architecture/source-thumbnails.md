# Source thumbnail assignment foundation

## Scope

EED-CCB-2026-0009-A establishes storage and a pure assignment decision for
future CCB thumbnails. It deliberately does not generate or publish images,
change Moodle cards, call GD, mutate caches, add administration UI, or execute
an upgrade.

The plugin continues to declare Moodle 4.5 as its compatibility floor. Moodle
5.1 is the first planned runtime-validation target, but this source-only
sub-lot does not claim executable compatibility on any Moodle version.

## Storage contract

`local_course_banner_builder_thumbnail` stores multiple transferable
candidate definitions for each stable `sourcekey`. The pair
(`sourcekey`, `candidatekey`) is unique. Candidate records contain only logical
intent and revision metadata; generated wide and square files never belong in
the table.

`local_course_banner_builder_course_thumb` stores at most one decision per
course. Its `mode` is `inherit`, `explicit`, or `disabled`. Stable source and
candidate keys preserve the assignment independently from local numeric record
ids. An absent row means unassigned inheritance.

The schema definitions in `db/install.xml` and `db/upgrade.php` are kept in
parity. The upgrade step creates tables only. It performs no backfill, image
generation, GD operation, cache mutation, or plugin-service call.

## Pure resolver contract

`thumbnail_assignment_resolver::resolve()` receives:

- the course id used for deterministic candidate selection;
- an optional persisted assignment;
- applicable sources ordered from lowest/nearest to highest, with candidates
  already marked eligible by a future boundary service.

It returns a normalized decision and whether persistence must change. It does
not access Moodle globals, the database, files, GD, caches, renderers, or
configuration.

Rules:

1. `disabled` returns no CCB assignment.
2. A valid `explicit` choice remains explicit.
3. A valid persisted `inherit` choice remains unchanged even if a new lower
   source appears.
4. A missing explicit choice becomes `inherit` and follows automatic
   selection.
5. Automatic selection uses the first source in the supplied lowest-first
   chain with eligible candidates.
6. Candidates are sorted by `sortorder` and `candidatekey`, selected
   deterministically from course id and source key, then intended to be
   persisted once by a future write service.
7. With no eligible candidate, inheritance remains unresolved and Moodle's
   later rendering boundary must retain its native fallback.

Teacher-image priority, GD availability and output retraction belong to the
future publication service. They must not be folded into this pure resolver.

## Deferred sub-lots

- candidate and course-selection write services with capability checks and
  delegated transactions;
- lifecycle jobs for source deletion, invalidation and recomputation;
- administrator interfaces for candidates and per-course choice;
- wide Dashboard/My courses and square coursebox composition and publication;
- Transfer plus Moodle duplicate/backup/restore integration;
- migration execution, Moodle 4.5/5.1 validation and browser evidence.

## Official Moodle references consulted

- Moodle 4.5 common plugin files and database-schema guidance;
- Moodle 5.1 Plugin Upgrades guide;
- XMLDB editor documentation;
- Moodle PHP coding style.
