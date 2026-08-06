# EasyEdu agent contract

For cross-repository EasyEdu context, start with
`<EASYEDU_PLATFORM_ROOT>\AI\README.md` when it is available locally. Then read
`AI\course-banner-builder\context.md` in the platform repository before editing
this plugin.

Before editing, read `easyedu-kit-docs/ai/DOCUMENTATION_CONTRACT.md`. Every
feature, validation protocol or reusable UI change must update the appropriate
technical docs, changelog and AI contract, or explicitly explain why one is
not applicable.

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

## Playwright visual artifacts

Before running visual tests, read the platform policy at
docs/development/playwright-artifact-retention.md when the platform checkout
is available. Screenshots, videos and traces stay outside Git and Syncthing.
Register the run manifest, inspect the dry-run retention report and never
delete unmanifested legacy captures or another agent's active run.

If another Codex window owns the required Moodle/runtime lease, use the shared
Playwright wrapper with `-WaitForLease` and a bounded timeout when available.
Do not interrupt the owner or repeatedly relaunch the same test.

## Branch, runtime and response handoff

Read `<EASYEDU_PLATFORM_ROOT>\docs\development\branch-runtime-handoff.md` and
`<EASYEDU_PLATFORM_ROOT>\AI\prompts\procedure-refresh.md` before a branch swap,
runtime test or cross-window handoff. Several windows may work on separate
branches, but only one window may own the shared Moodle `localhost` runtime.
Use the platform read-only branch report helper before handoff and use
`-WaitForLease` for an occupied runtime. Never reset, clean, stash, merge or
rebase another window's dirty worktree.

Every response must end with:

```text
NEXT MODEL: <recommended Codex model>
MODEL REASON: <why it fits the next planned step>
NEXT TASK: <one concrete next task>
ESCALATE IF: <condition requiring another model, role or human decision>
```

This is required for corrections, diagnostics and blocked tests as well as
planned implementation work.

Read `<EASYEDU_PLATFORM_ROOT>\AI\DEVELOPMENT_PLAN.md` and the relevant
`AI\memory\course-banner-builder\CURRENT_STATE.md` or roadmap before editing.
Update the versioned plan/state files when a milestone, blocker or next step
changes. Preserve valuable Playwright specs as versioned candidates for
Docker/CI and classify them in the shared scenario registry; keep only
generated media outside Git.
