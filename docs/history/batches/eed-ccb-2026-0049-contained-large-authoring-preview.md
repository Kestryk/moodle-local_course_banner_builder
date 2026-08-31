# EED-CCB-2026-0049 - Contained large authoring preview

## Supersession and discovery gate

Human review rejected this readonly clone as the final product. It remains a
disposable source prototype only and must not be extended into an interactive
editor. The source audit, interaction research, structural wireframe and seven
product decisions for the requested desktop-first workspace are recorded in
[Large authoring workspace discovery](../../architecture/large-authoring-workspace.md).

No product code, persistence contract, runtime promotion or managed preview is
authorised until those seven recommendations are accepted or changed.

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

This section describes the historical Wave 6 prototype rather than the
approved target architecture.
