# Title-colour dialog contract

## Scope

`EED-CCB-2026-0046` replaces the browser-native colour input used by the Course, Site and Activity title editors. It does not change title persistence, inheritance, preview rendering, Crop, source-tree/Preview, Motion or Slideshow controls.

## Markup and state ownership

`admin_manage.php` keeps the hexadecimal text input and its `data-title-control` binding. The visible control is a button with `data-title-color-swatch-for`: a swatch plus its current hexadecimal value that opens a CCB-owned dialog. No title control uses `input[type=color]`.

`amd/src/admin_manage.js` owns temporary dialog state. Palette selection or HEX editing update only its preview. **Apply** validates six-digit HEX, updates the existing text input, dispatches its ordinary input event and refreshes the title preview. Cancel, backdrop dismissal and Escape remove the dialog without mutating the form. The surrounding title-editor Save remains the only persistence operation.

## Accessibility and responsive contract

The dialog is body-portal mounted with `role="dialog"`, `aria-modal="true"` and a labelled heading. It sends initial focus to HEX, traps Tab and Shift+Tab, and restores focus to the opening swatch on every close path. Palette buttons have hexadecimal accessible names and temporary `aria-pressed` state.

The dialog consumes CCB's existing Kit modal shell, focus-ring, button and surface tokens. Its palette contracts from six to five columns at narrow widths; the shell uses available viewport width and is bottom-aligned on small screens without document horizontal overflow.

## Source-ready validation

Static validation must cover PHP syntax, AMD syntax/build output, Sass output and `git diff --check`. A future lease-gated supervised capture must use separate named screenshots:

- `01-0046-course-title-colour-dialog.png`;
- `02-0046-site-title-colour-dialog.png`;
- `03-0046-activity-title-colour-dialog.png`.

For each context, prove palette selection then Apply updates preview and hexadecimal field, while Cancel and Escape retain the pre-open value and return focus to the same swatch. Include a narrow/zoom cell. No screenshot, preview, cache purge, lease or browser activity is part of this source-ready batch.
