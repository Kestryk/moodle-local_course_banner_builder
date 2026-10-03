# Detached AMD build snapshot index

Every patch in this directory is a direct `git diff --binary` capture from a
detached AMD build checkout. The three omitted `0002-label-amd` worktrees were
byte-identical to `moodle51-0002-label-amd.patch`:

- `C:\dev\worktrees\moodle51-build-ccb-0002-label-amd-integration-20260820\public\local\course_banner_builder`
- `C:\dev\worktrees\moodle51-build-ccb-0002-label-amd-integration-4d221bd-20260820\public\local\course_banner_builder`

The retained snapshots are:

- `moodle513-0035-current.patch`
- `moodle513-0041-rf1.patch`
- `moodle513-0041-rf2.patch`
- `moodle513-0041-rf2b.patch`
- `moodle513-wave-0006-0023-0035.patch`
- `moodle51-0002-label-amd.patch`
- `moodle51-0027-r-amd-sparse.patch`

Rebuild AMD from the selected source candidate before any future preview; do
not use a captured build artifact as a product implementation by itself.
