# CCB paused consolidation — 2026-10-03

This branch is the single private continuation point for the paused Course
Banner Builder work. It is intentionally based on `cf6cb34`, not on the
public `main` branch.

The source worktrees listed below were captured before their historical
directories were retired. Their uncommitted tracked changes are preserved as
binary-safe Git patches under `patches/`; untracked files are copied under
`untracked/` with their original relative paths. This preserves each lot
without forcing incompatible product behaviour into the current checkpoint.

## Included source lots

| Lot | Source branch | Source HEAD | Capture |
| --- | --- | --- | --- |
| Button evidence | `work/port4719pg3/eed-kit-2026-0016-visual-evidence-ccb` | `cf6cb34` | Applied as commit `748c1e8`; its two new files are versioned directly. |
| Slideshow settled-modal evidence | `work/desktop-k1gsrvt/eed-ccb-2026-0002` | `ad39df0` | Patch snapshot. The current checkpoint already contains the settled-modal helper; retain the patch as provenance. |
| Source thumbnails | `work/port4719pg3/eed-ccb-2026-0009-source-thumbnails` | `53e5676` | Patch and untracked architecture/test files. |
| Async editor actions | `work/port4719pg3/eed-ccb-2026-0013-async-editor` | `f545480` | Patch and untracked contract files. |
| Async editor integration | `work/port4719pg3/eed-ccb-2026-0013-async-editor-integration` | `1d237ef` | Patch and untracked contract files. |
| Image modal stability | `work/port4719pg3/eed-ccb-2026-0023-image-modal-stability` | `056c044` | Patch snapshot. |
| Crop geometry | `work/port4719pg3/eed-ccb-2026-0043-rf8-crop-geometry` | `057f775` | Patch snapshot. The current checkpoint contains a later crop invariant; retain this source delta for comparison. |
| Navigation consumer | `work/port4719pg3/eed-nav-2026-0011-ccb-consumer-20260829` | `940aac3` | Patch and untracked component documentation. |

## Detached AMD build checkouts

Nine detached build checkouts also contained generated AMD artifacts and, in
some cases, an AMD source file. Their complete tracked deltas are retained
under `toolchain-patches/`. Three `0002-label-amd` copies were byte-identical;
all three paths are recorded in `toolchain-patches/README.md` but one canonical
patch is sufficient for recovery. These snapshots are evidence only: rebuild
generated AMD from a selected source candidate before any future preview.

## Resume rule

Apply one patch at a time in a dedicated feature branch, inspect conflicts
against the current CCB source, and validate the affected behaviour before
promoting any resulting product commit. Do not apply this archive wholesale,
and never merge it into public `main` directly.
