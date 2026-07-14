# Course Banner Builder UI Harmonisation Baseline

Baseline date: 2026-07-10
Workspace: Moodle 5.1
Branch: `postprod/banner-polish-2026-07`

## Scope

This baseline covers Course Banner Builder administration outside the EasyEdu
guide. The EasyStud guide is being refined independently and must not be
resynchronised during this lot.

## Visual Direction

- Inherit Moodle theme typography.
- Use very light EasyEdu context gradients, restrained borders and no decorative
  elevation.
- Keep primary navigation, contextual options and destructive actions visually
  distinct.
- Prefer grouped, collapsible option surfaces for dense settings.
- Use anchored EasyEdu popovers with keyboard focus support.
- Keep interaction motion between 150 and 250 ms and honour reduced motion.

## View Matrix

| View | Primary selectors | Risk | Current state | Next component |
| --- | --- | --- | --- | --- |
| Course banner admin | `.local-course-banner-builder-admin--native`, `.local-course-banner-builder-source-picker`, `.local-course-banner-builder-source-preview-panel` | high | Primary nav and banner options harmonised; preview/editor remains local | Panels and simple tables first; preview later |
| Site banner admin | Same engine as course admin with site source context | high | Shares navigation and option patterns | Validate parity after every course-admin change |
| Slideshow admin | `.local-course-banner-builder-slideshow-admin`, `.local-course-banner-builder-slideshow-card` | medium/high | Context cards now separate activation, content sources and playback controls through EasyEdu surfaces | Validate responsive hierarchy and appearance launcher spacing |
| Transfer | `.local-course-banner-builder-admin--native` plus Moodle forms | low | Raw headings, checkboxes and forms | Split EasyEdu transfer panels |
| Format chooser | `.local-course-banner-builder-format-card` | medium | EasyEdu choice surface and selected/focus states applied without changing format skeleton geometry | Responsive parity validation |
| Source/layer tables | `.local-course-banner-builder-configured-sources-table`, `.local-course-banner-builder-layer-table` | medium/high | EasyEdu surface applied without changing runtime hooks | Validate alongside future action-menu work |
| Image/border/overlay modals | `.local-course-banner-builder-layer-modal-content` | critical | Shared preview shell and integrated side accordions validated | Continue with preview controls and field surfaces |
| Title/slideshow modals | `.local-course-banner-builder-title-settings-modal`, `.local-course-banner-builder-slideshow-preview-modal` | critical | Shared preview shell and integrated side accordions validated; sticky preview and conditional controls remain plugin-owned | Harmonise field surfaces without changing preview calculations |

## Runtime-Sensitive Contracts

Do not rename or restructure these surfaces without a dedicated interaction
test:

- preview layers, resize handles and crop editor data attributes;
- filmstrip and active-layer selection hooks;
- source and layer table drag/drop hooks;
- modal preview action selectors and sticky footer wrappers;
- linked range/number field identifiers;
- title and slideshow preview targets;
- guide targets and guide AMD behaviour.

## First Harmonisation Lots

1. Transfer panels, simple page headings and descriptions.
2. Slideshow page-level panels and context cards without changing modal markup.
3. Source/list table shells and empty states without changing row behaviour.
4. Shared modal shell and spacing, one modal family at a time.
5. Preview toolbars, side actions, colour fields and linked range controls.

## Validation Gates

Each lot requires SCSS compilation, PHP lint, AMD syntax checks when relevant,
`git diff --check`, one desktop and one reduced-width browser check, and manual
verification of any touched action. No merge to `main` occurs before critical
preview workflows are validated.
