# EED-CCB-2026-0075 - Dynamic layer deletion

## Scope

Course and Site Banner Manager delete one selected preview layer, a table
selection, or every layer of the selected source without reloading the page.
The established Moodle confirmation, capability, sesskey, translated toast,
busy indicator and focus-return behavior remain authoritative.

This batch hardens the already-present asynchronous foundation instead of
creating a parallel client API. It excludes source/rule deletion, layer-form
save, Filemanager draft deletion and inherited layers owned by another source.

## Implementation

- The selected source is now the server mutation boundary for single and bulk
  element ids. The complete bulk selection is validated before the first
  deletion, preventing a stale or forged id from deleting another source's
  layer or causing a partial mixed-source operation.
- Valid bulk deletion suppresses per-row synchronisation and synchronises the
  selected source once after the batch.
- The server-rendered `admin_selected` fragment remains the authority for the
  refreshed layer table and general preview.
- The three under-preview mutation controls share one busy lock. Successful
  multi-layer deletion also removes the deleted ids from already-loaded modal
  preview contexts and resynchronises those modal controls.
- A rejected source/element pairing leaves the current DOM intact, uses the
  translated error feedback, and returns focus to the initiating control.

## Validation boundary

Source-ready checks for this batch cover PHP and JavaScript syntax, the focused
asynchronous editor source/generated contract, the repository release
validation available in the checkout, a PHPUnit regression source for foreign
and mixed-source identifiers, and `git diff --check`. The PHPUnit source is
committed but is not claimed as executed when the local Moodle PHPUnit database
is unavailable.

No runtime, browser, fixture, cache or managed-preview work belongs to this
source candidate. The existing supervised scenario remains the next gate and
must confirm delete-one, delete-many and delete-all keep the URL stable, lock
the sibling controls, refresh list and preview, preserve inherited layers, show
one transient toast and return useful focus.

## Handoff

The source candidate is intended for a controlled Moodle 5.1 preview after its
clean commit is pushed. Moodle 4.5 executable coverage remains required before
release because the plugin declares Moodle 4.5 as its minimum version.
