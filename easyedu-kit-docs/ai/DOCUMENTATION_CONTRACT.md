# EasyEdu documentation contract

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
