# EED-CCB-2026-0049 - Contained large authoring preview

## Scope

Expose the selected source's current authoring renderer in the existing opaque
Source Preview modal. Keep Desktop/Mobile simulation available without saving
the selected mode or changing source, layer or public-banner state.

## Implementation

- Adds one explicit large-preview launcher beside the source preview modes.
- Clones the live renderer so unsaved visual state can be inspected.
- Removes forms, editor actions, filmstrip and nested launchers from the clone.
- Marks the clone readonly and keeps modal mode changes out of the authoring
  mode map.
- Reuses the accepted close, Escape, backdrop and focus-return lifecycle.

## Validation

- PHP and JavaScript syntax checks.
- Focused Node source contract.
- Sass/source and generated-asset consistency.
- Browser, runtime and managed-preview checks intentionally deferred to the
  cumulative preview owner.

## Non-regression boundary

No persistence schema, public renderer, source payload, Crop state, layer
geometry, toast, motion or source-row action is changed.
