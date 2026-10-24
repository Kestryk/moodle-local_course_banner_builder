Full-size banner and course-card thumbnail titles carry no text shadow. Thumbnail typography keeps its responsive base size and multiplies it by the configured course-title percentage; retain the padding-free two-line clamp and image-only containing block. Do not turn the percentage into a fixed pixel size.

Thumbnail-only density is 0.88 of that responsive/configured size. Apply it once:
frame padding already uses em, while the overlay's rem gutter is scaled explicitly.
Do not reduce the full-size banner or change pill shape/alignment.

Catalogue IMG overlays require an image-only containing block, including before aggregate CSS loads. Never infer thumbnail height from an arbitrary parent. Preserve the background-image Card path and padding-free clamp; verify both contexts separately.

# EasyEdu documentation contract

Native course-card title tests must retain Moodle's real accessible and visual
name duplicates plus favourite-icon labels. Prefer the explicit full-name
attribute, never a generic titled descendant. Keep the visual clamp on a
padding-free child and verify actual compact screenshots, not only computed
line-clamp. See docs/testing/course-card-title-2026-10-23.md.

Course-card title typography is transported as CSS through JSON/DOM, not as
pre-escaped HTML. Preserve the strict font-family allowlist and let html_writer
escape native-banner attributes once. Check quoted font families and both
configured weights against the full banner, with text-box centering measured
inside the actual thumbnail. Keep the existing image and title-clamp contracts.

This portable contract applies to every AI agent working on Course Banner
Builder. The canonical wording is maintained in the EasyEdu platform
repository under `AI/DOCUMENTATION_CONTRACT.md`.

Before editing, read the platform workflow, this plugin's `AGENTS.md`, the
relevant UI Kit contracts and the canonical `EED-*` batch record when work
crosses repositories.

## Documentation impact

| Change | Required record |
| --- | --- |
| User-visible feature or bug fix | Changelog and functional or technical documentation |
| Reusable component, token, template or interaction | Kit/consumer contract, examples, docs and changelog |
| Script, test, validation or workflow | Procedure, evidence format and validation result |
| Fragile behavior or repeated integration mistake | AI rule, checklist or contract |
| Cross-repository or release work | Canonical batch page and portable backlink |

## Final agent report

Every final report must state branches and dirty state, changed files, docs and
changelog updates, AI-contract decisions, checks run and skipped, evidence,
risks, cleanup, rollback notes and the next step. Git remains authoritative;
Syncthing and future BookStack/design/automation integrations are secondary
publishing or transport layers only.


Native page-heading suppression requires a renderable banner payload and its
independent site/course setting. Preserve source resolution and header actions;
never hide the whole page header or couple this policy to banner title layers.
See docs/native-page-heading.md for native reversal and integration checks.
