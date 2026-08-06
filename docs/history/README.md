# Course Banner Builder historical registry

This directory is the human-readable history of Course Banner Builder (CCB).
It records verified batches, incidents, migrations, and release-relevant
product evolutions so that a future maintainer can continue the work without
depending on an agent conversation. It complements the [human changelog](../../CHANGELOG.md),
the [architecture notes](../architecture/), and the [testing protocols](../testing/).

## What an entry represents

- A **batch** is a bounded product change or validation gate with an explicit
  scope and acceptance criteria.
- An **incident** is an observed failure, regression, or recovery event and its
  correction.
- A **migration** changes ownership, storage, workstation, or runtime
  location without itself being a product batch.
- A **release** is a controlled, reviewable promotion reference. A dirty
  worktree, a test run, or a snapshot is not a release.

The [registry](batch-registry.md) is the index. Detailed entries live in
`batches/` and use stable relative links.

## Status vocabulary

Use only these values in the registry:

- `Validated` — the stated acceptance evidence is available and matches the
  documented scope.
- `Implemented — validation pending` — implementation is evidenced, but the
  required gate has not been run or recorded.
- `Partially validated` — a named subset is validated; the remaining scope is
  explicit in the entry.
- `Blocked` — work cannot proceed until a named dependency or decision is
  resolved.
- `Deferred` — intentionally postponed; no completion is implied.
- `Pending historical confirmation` — the work is referenced, but the date,
  authority, or lineage is not yet proven.
- `Unknown from available evidence` — no reliable conclusion can be drawn.

Never turn a pending, blocked, or deferred gate into a completed status merely
because code or a test file exists. In particular, genuine 200% browser
validation is distinct from a 100% run, and Batch 2A/2B remain blocked until
their recorded dependencies are complete.

## Evidence rules

Each claim must point to evidence that a human can inspect: source files,
commits, focused tests, QA artifacts, snapshot reports, or an approved
migration record. Record the branch and commit when they are relevant, and say
when a value belongs to an uncommitted restored worktree. Do not copy passwords,
session keys, personal paths, or disposable fixture credentials into this
history. External test artifacts may be referenced by their documented artifact
root without copying them into Git.

If the evidence conflicts, preserve both facts and mark the conclusion
`Pending historical confirmation` until the authority is resolved.

## Adding or updating an entry

Every future batch must update, in the same controlled change set as the batch
or before its final commit:

1. the detailed batch document;
2. this registry;
3. `CHANGELOG.md` when the change is user-visible or release-relevant; and
4. architecture or testing documentation when the contract changes.

The entry must state scope, implementation ownership, validation commands and
results, dependencies, remaining work, and evidence. Review relative links,
status vocabulary, secrets, and the distinction between committed, dirty, and
snapshot-only content before committing. AI memory may supplement this record;
it cannot replace it.

## Future BookStack mapping

The README can become a BookStack chapter, the registry a landing-page table,
and each file in `batches/` a child page. Keep filenames and headings stable so
that an eventual import can preserve links and history. Do not publish an
unverified entry merely to make the BookStack tree look complete.
