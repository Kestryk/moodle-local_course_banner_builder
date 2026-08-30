# EED-CCB-2026-0043 RF10 - Unbounded modal Recrop composition

## Scope

RF10 corrects only the image-layer editor inside the Add layer modal. It
addresses a human-observed defect where the third Recrop on the same image
could deform the accepted geometry even though the second Recrop appeared
correct.

## Design

Each Crop session records the visible source rectangle when the editor opens.
At Apply, the product now measures the rendered selection box against that
source rectangle. The resulting selection is then composed with the accepted
placement from the start of that session.

This makes every Recrop use the same coordinate system: first, second, third
and later edits have no special case. Fit, Cancel, Undo/Redo and multi-image
history are deliberately outside this narrow correction and retain their
existing state paths.

## Validation boundary

`tools/test-ccb-crop-history-contract.ps1` checks that the rendered selection
is used and that it is composed with the session placement. A separately
authorised preview must still verify three or more successive Recrops on one
image at desktop and narrow width.
