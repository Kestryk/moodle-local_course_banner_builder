# CCB Wave 15 corrective source candidate

Date: 2026-09-02

Lots: `EED-CCB-2026-0049-RF1`, `0042-RF5`, `0063-RF2`, `0064-RF2`,
`0059-RF3`, and source-only diagnostic `0078`.

## Product corrections

- The large-workspace toolbar is compact and keeps zoom-in visible. It adds a
  bounded numeric zoom field, truthful disabled endpoints and a visible focus
  return. Fit and the initial selected Image frame synchronise on first paint.
- Panning remains deliberately owned by Space plus primary drag. Ordinary
  canvas clicks keep their existing selection, Crop and drag behavior; direct
  background drag is not enabled without separate runtime proof.
- Parent Save and Cancel use the shared compact modal-action geometry. Save
  raises the standard bottom-end busy feedback and returns an updated Selected
  source fragment, including its general preview, after the existing guarded
  Parent transaction succeeds.
- The three actions in each selected-source preview action family share the
  same computed compact typography, fixed height and light Save-style state.
  Existing row Edit and Delete actions retain pointer cursors.
- Image, Border and Overlay bodies receive one subtle content reveal after the
  accepted loading ring disappears. Reduced-motion bypasses the reveal.
- A dynamically loaded Image editor synchronises the selection outline on its
  first two layout frames, before a canvas interaction is required.

No route, sesskey, capability, Parent hierarchy, Crop calculation, saved layer
geometry, classic general-preview behavior or public rendering contract is
changed.

## CCB 0078 source-only ownership diagnostic

The Course settings Filepicker failure is not owned by a CCB form override:

- CCB does not replace Moodle's course settings form or its Filepicker.
- The CCB course-card module is requested from the footer hook when banners can
  render, but it only scans course-card/banner targets and observes DOM changes;
  it does not bind, hide, disable or open a Moodle Filepicker.
- CCB reads valid files from Moodle's `course/overviewfiles` area to decide
  teacher-image priority. That read path is downstream of a successful core
  upload and cannot prevent the picker from opening.
- The inspected Moodle Core generated `core/first` and
  `core_form/changechecker` files are AMD `define(...)` artifacts. The reported
  `import declarations may only appear at top level` / `No define call` pair
  affected both CCB and EasyStud and therefore points to the shared Moodle
  JavaScript aggregation/cache/build delivery layer rather than a CCB-only
  handler.

This is a source ownership conclusion, not a runtime root-cause proof. No cache,
configuration, database, fixture or runtime was changed. The next gate belongs
to the Moodle/Platform runtime owner: capture one clean revision, requested
module response, response headers and console/network reproduction before any
repair. CCB 0047 teacher-image priority stays unvalidated until that gate.

## Static validation contract

- Rebuild `styles.css` from SCSS and `admin_manage.min.js` plus its source map
  from `amd/src/admin_manage.js`.
- Run `tools/test-ccb-wave15-corrections-contract.ps1` and all existing plugin
  source contracts.
- Run PHP and JavaScript syntax checks plus `git diff --check`.
- Managed preview, Moodle cache purge, fixtures and browser evidence are not
  part of this source worktree.
