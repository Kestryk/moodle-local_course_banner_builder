# CCB administration page identity

## Scenario

`ccb-admin-page-identity` is the focused read-only visual regression scenario
for the CCB Course, Site and Transfer page headers.

Source: `tools/playwright/ccb-admin-page-identity.spec.js`.

## Contract

At a 390 px viewport, the scenario verifies each page has a non-empty,
localised CCB product mark, title and description before the existing
Navigation. It also rejects horizontal overflow of the identity itself and
fails on any state-changing request to the three audited routes.

The scenario does not open a modal, change a source, submit a form, write a
fixture or visit Slideshow.

## Execution gate

Run only after a controlled preview is visible and a CCB runtime lease has
been acquired. Credentials stay process-local; screenshots, traces and videos
remain in the external artifact root and require a manifest.
