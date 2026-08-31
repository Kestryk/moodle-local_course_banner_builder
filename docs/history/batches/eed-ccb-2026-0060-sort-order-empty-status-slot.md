# EED-CCB-2026-0060 - Sort order without an empty status slot

## Scope

Centre Sort order in unlocked Image rows that have no auxiliary status icon.
Rows with Crop, fixed-centre, inheritance, placement or lock status keep their
existing two-part layout and controls.

## Implementation

The manager exports whether a row has any status indicator. Mustache emits the
indicator stack only when that flag is true, avoiding a reserved empty minimum
height for ordinary unlocked Image rows.

## Validation

- PHP syntax check.
- Focused Node source contract.
- Mustache/source review and `git diff --check`.
- Browser and managed-preview checks intentionally deferred to the cumulative
  preview owner.

## Non-regression boundary

Hidden sort values, native row ordering, drag and keyboard behavior, locked
rows and all existing status popovers remain unchanged.
