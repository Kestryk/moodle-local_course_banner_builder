# EED-CCB-2026-0050: configured-source disclosure and preview modal parity

Status: Implemented — validation pending

## RF3 corrective pass - 2026-08-30

Human review rejected the earlier visual result. RF3 therefore strengthens
the inherited Border/Overlay labels and canonical checkerboard, restores the
spacing around Collapse all and softens the Layer & Overrides heading.

The source Preview modal now uses the opaque shared surface, common close
button, Kit loading and feedback treatments, and a footer Edit source action.
Its Desktop/Mobile choice remains transient to the modal and does not alter the
saved source or public banner mode.

## Scope

- Replace immediate `hidden` changes for configured-source descendants with the existing cancellable CCB Motion lifecycle.
- Keep each hierarchy trigger's expanded state and controlled row references accurate, including during Collapse all and reduced-motion mode.
- Give the source-preview dialog a complete EasyEdu Kit header/body/footer shell. Desktop and Mobile remain preview-canvas controls only; the edit link belongs in the footer.
- Use the Kit modal loading treatment with `is-loading` and `aria-busy`; errors retain a stable focus target and closing returns focus to the triggering Preview action.

## Explicit exclusions

Title editors from 0046, crop, slideshow, source persistence, manager code and `motion.js` are unchanged. No runtime preview, cache purge, fixture mutation, lease or browser activity is part of this batch.

## Validation contract

Before handoff, rebuild the official AMD and Sass generated assets and run static source checks. A future, separately authorized browser review should verify a nested source chain through collapse, interruption, reduced motion, modal loading/error, close-and-return-focus, and both preview-canvas modes.
