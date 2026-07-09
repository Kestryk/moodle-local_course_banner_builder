# EasyEdu UI Kit AI Rules

These rules are mandatory for Codex or any other AI agent working on EasyEdu UI
Kit consumers.

## Absolute rule

The EasyEdu UI Kit is the source of truth for reusable visual behaviour.

If a visual element, interaction pattern, spacing rule, animation, token, menu,
tooltip, guide element, modal, button or state can be reused by more than one
plugin, it must be defined or updated in the kit first.

Plugins consume the kit. Plugins must not reinvent reusable UI locally.

## Before creating or changing UI

1. Inspect the kit first:
   - `docs/component-matrix.md`
   - `docs/components/`
   - `scss/easyedu/`
   - `guide/`
   - `ai/COMPONENT_CONTRACT.md`
2. Identify the exact existing component, mixin, token or guide behaviour that
   should be reused.
3. If the requested behaviour does not exist, add it to the kit first.
4. Only then adapt the target plugin.
5. Do not rely on a vague prompt handoff. Encode the behaviour in the kit:
   documentation, SCSS, JS, template contract and changelog.

## Forbidden shortcuts

Do not add reusable UI in a plugin only.

Avoid:

- hard-coded colours when a token or CSS variable exists;
- plugin-local spacing that should be a kit primitive;
- plugin-local border radius for reusable controls;
- local animations for reusable states;
- duplicate HTML structures when a kit template/contract exists;
- native `title` tooltips when the kit custom tooltip pattern is used;
- absolute-position highlights for guide selectors;
- local guide behaviour that diverges from the guide kit.

## Required update set for reusable changes

When adding or changing a reusable component, update all relevant parts:

- SCSS mixin or component primitive;
- template or expected HTML contract;
- AMD/JS behaviour if interaction is involved;
- language/string contract if user-facing text is involved;
- docs under `docs/components/`;
- component matrix if the component family changes;
- changelog;
- AI contract if the behaviour is fragile or often misimplemented.

## Fragile interaction systems

Some systems are not generic kit primitives, but they are still too fragile to
be changed from memory or by visual intuition alone. Course Banner Builder crop
editing is one example.

For those systems:

1. Write or update an explicit contract in `docs/examples/<plugin>.md`.
2. State the canonical source of truth and any session-only state.
3. List the regression scenarios that must be replayed.
4. Prefer a targeted browser/headless probe over speculative patching.
5. When a fix is validated, create a handoff note so another agent does not
   have to reconstruct the reasoning from chat history.

Do not rely on:

- rough screenshots alone;
- one modal working while another uses a forked controller;
- DOM geometry reconstructed from already transformed layers when a stable data
  model exists or should exist.

## Moodle constraints

Moodle plugins consuming this kit must follow `ai/MOODLE_PLUGIN_RULES.md` and
`ai/MOODLE_PLUGIN_REVIEW_CHECKLIST.md`.

At minimum:

- PHP output should prefer templates/output APIs where practical;
- user-facing strings belong in language files;
- AMD modules must be valid Moodle AMD build sources;
- SCSS should be component-scoped and avoid global leakage;
- no inline CSS/JS for reusable UI;
- Privacy API, security validation and Moodle parameter handling are mandatory
  plugin concerns.

## Verification expectation

At the end of a UI change, report:

- existing kit pieces reused;
- new kit pieces added;
- plugin files adapted;
- commands run;
- known visual checks not run.

If visual parity is requested, compare structure, states and behaviour, not just
rough appearance.

## Audit baseline

Run `.\scripts\audit-kit.ps1 -FailOnNewWarning` when the kit changes.

The baseline in `ai/audit-baseline.json` exists to separate known debt from new
regressions. Do not run `-UpdateBaseline` unless the user explicitly accepts the
new findings or the findings were deliberately reviewed.
