# Orchestration

Orchestration describes the JavaScript contract that keeps EasyEdu visual
components aligned when a plugin changes its interface dynamically.

The kit intentionally does not own plugin business logic. It provides stable
CSS primitives, guide events and recommended timing patterns. Each plugin keeps
control of its own data, permissions and Moodle actions.

## When to use these patterns

Use orchestration hooks when a plugin has any of these behaviours:

- switching between major views or panels;
- filtering lists or search results;
- changing paginated list pages;
- expanding cards, accordions or inline forms;
- inserting, moving or deleting DOM nodes after Ajax;
- opening responsive action trays or long-press menus;
- showing a guide highlight over an element that can move.

## Guide highlight refresh

After a visual transition that can move the highlighted element, dispatch the
public guide refresh event:

```js
document.dispatchEvent(new CustomEvent('easyedu:guide-refresh-highlight', {
  detail: {
    dock: true
  }
}));
```

Omit `target` to refresh the current guided target. This is the safest default
for plugin-owned UI transitions because it does not need to know which guide
step is currently active.

Use a target key or selector only when the plugin intentionally wants to move the
highlight to a specific element:

```js
document.dispatchEvent(new CustomEvent('easyedu:guide-refresh-highlight', {
  detail: {
    target: 'createPanel',
    dock: true
  }
}));
```

## Transition timing

One refresh immediately after a click is often too early. Accordions,
pagination, Ajax insertion and CSS transitions can still be moving.

Recommended plugin helper:

```js
const refreshGuideHighlight = () => {
  [0, 160, 380, 760].forEach(delay => {
    window.setTimeout(() => {
      document.dispatchEvent(new CustomEvent('easyedu:guide-refresh-highlight', {
        detail: {
          dock: true
        }
      }));
    }, delay);
  });
};
```

Call this helper after the plugin changes layout. If a component exposes a
`transitionend` event, call the helper both when the transition starts and when
it ends.

## Recommended hook map

Major view toggle:
Dispatch after the root layout class changes, after hidden panels are toggled
and after action bars are recalculated.

Filters and search:
Dispatch after hidden states, empty states and pagination have been recomputed.

Pagination:
Dispatch after `data-page` or equivalent state changes, then again after the
page transition class is removed.

Card/accordion expansion:
Dispatch when expansion starts and on `transitionend`. For nested cards,
dispatch after the parent container recalculates its height.

Ajax create/move/delete:
Dispatch after the DOM node is inserted or removed, badges/counts are updated,
empty states are synced and pagination is recalculated.

Responsive action tray:
Dispatch after the tray opens, closes, changes height or moves from one side of
the viewport to another.

Long-press context menu:
Do not dispatch during the press threshold. Dispatch only after the menu is
actually opened so the guide does not chase temporary drag feedback.

## Guided step completion

Only complete a guided step when the real user action happened:

```js
document.dispatchEvent(new CustomEvent('easyedu:guide-step-complete', {
  detail: {
    path: 'structure',
    step: 'create-group'
  }
}));
```

Checklist clicks may focus the right UI, but the action itself should complete
the step. This avoids a guide that says "done" before Moodle data really
changed.

## Responsive collision rules

When the guide checklist and a plugin action tray are both visible, avoid
placing them on top of each other.

Recommended order:

1. Keep the Moodle primary navigation clear.
2. Keep the highlighted target visible.
3. Move the guided checklist left or right away from the highlighted target.
4. Stack the mobile action tray and guided checklist if both must stay visible.
5. Hide duplicate desktop action bars on small screens when a mobile tray is
   active.

## Extraction status

These patterns are intentionally behavioural. The kit documents the contract and
provides guide events; plugins still own their exact selectors, data mutations
and capability checks.
