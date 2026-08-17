# EasyEdu agent contract

For cross-repository EasyEdu context, start with
`D:\dev\easyedu-platform\AI\README.md` when it is available locally. Then read
`AI\course-banner-builder\context.md` in the platform repository before editing
this plugin.

## Multi-machine development contract

Every AI agent working in this repository must follow the canonical EasyEdu
handoff procedure:

https://github.com/Kestryk/workstation-sync/blob/main/docs/PROJECT-HANDOFF.md

A handoff is complete only when the relevant worktree is clean, its branch has
an upstream, and all local commits are pushed. Unfinished work must use an
explicit pushed WIP commit on a feature branch; a Git stash is not a handoff.

Before handoff, create a verified workspace snapshot. On the destination
workstation, fetch and fast-forward only from a clean checkout. One branch has
one workstation owner at a time.

Never reset, clean, discard, merge or rebase a dirty handoff automatically.
Preserve and compare it first. Report repository, branch, HEAD, upstream,
clean/dirty state and ahead/behind counts before claiming readiness. Managed
workstations receive no remote service, elevated configuration or policy bypass
without explicit organisational approval.
