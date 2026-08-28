# CCB administration page identity

## Scope

`EED-CCB-2026-0015-B` defines the visible identity for the Course banners,
Site banner and Transfer pages. Each page renders the same CCB-owned header
before its existing Navigation: product mark, localised page title and short
localised description.

The component is `templates/admin_page_identity.mustache`. Its placement and
copy remain CCB-owned. It deliberately does not make Navigation a child of the
header, so Navigation destinations, Guide behavior and responsive drawer
semantics remain unchanged.

## Styling contract

`scss/components/_admin-page-identity.scss` composes the embedded UI Kit's
existing `type-eyebrow`, `type-page-title` and `type-body` roles. The product
mark uses the established `--easyedu-primary` token; title and description use
the Kit text tokens. No UI Kit source or token is added by this feature.

The header has a bounded `max-inline-size`, a small internal rhythm and a
single bottom margin before Navigation. The mobile rule only tightens that
spacing; it does not introduce a separate layout or Navigation behavior.

## Ownership boundaries

Included:

- `admin_manage.php`, which also serves `admin_site.php`;
- `admin_transfer.php`;
- the CCB identity template, local SCSS, translated copy, generated stylesheet
  and focused regression scenario.

Excluded:

- `admin_slideshow.php` and `_slideshow-page-skeleton.scss`, owned by the
  separate Skeleton lot;
- Navigation, Guide, AMD, previews, sticky states, async editor, Crop,
  modals, Reset and the shared UI Kit.

## Validation

`tools/playwright/ccb-admin-page-identity.spec.js` is a future lease-gated,
read-only browser scenario. It checks Course, Site and Transfer at a 390 px
viewport for a non-empty localised identity before Navigation, no horizontal
overflow of the identity itself and no state-changing requests. It uses
process-local credentials and external artifacts only when separately
authorised.
