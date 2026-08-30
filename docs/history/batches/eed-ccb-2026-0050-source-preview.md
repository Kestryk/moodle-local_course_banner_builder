# EED-CCB-2026-0050: configured-source disclosure and preview modal parity

Status: Implemented — validation pending

## RF4 live-DOM corrective pass - 2026-08-30

The cumulative preview showed that RF3 had styled a body-appended modal without
also putting that live root inside the EasyEdu token scope. Undefined surface
variables invalidated the intended backgrounds, which explains the transparent
modal and apparently floating footer. RF4 targets that actual runtime modal,
adds the shared identity header/body/footer structure and keeps a literal opaque
fallback. Its canonical close returns a visible focus ring to the exact Preview
trigger. Desktop/Mobile stays modal-local and now uses an intentionally visible
Motion swap.

Configured-source children are table rows, so applying the generic fade
lifecycle to each `tr` could not produce a slide. RF4 uses `Motion.resize` on
the live table shell around the real row visibility mutation. That preserves
the row DOM and its direct-child layout selectors while progressively revealing
or removing the table content. The actual Layer & Overrides `details` selector
is also added to the shared cancellable accordion controller. Reduced-motion
continues to settle immediately through the existing Motion policy.

The corrective pass also shortens the inherited labels to `Inherited border`
and `Inherited overlay` (`Bordure héritée` / `Overlay hérité` in French), centres
the Layer & Overrides summary and gives Collapse all bounded inline spacing.
Accepted checkerboards, rounding, Locked state and inheritance logic are not
changed.

Two render warnings observed during the audit are removed in the same bounded
path: site-admin context is now passed explicitly to the advanced form helper,
and the source-child accessible name uses a real plugin string instead of the
missing Moodle core `toggle` string. Guide payload architecture is explicitly
outside RF4.

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
