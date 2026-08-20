# General preview asynchronous actions

## Scope

`EED-CCB-2026-0020` adds a narrow no-reload contract to the selected-source
visual preview on the Site and Course administration pages. It owns only the
preview save action and its coordination with the existing selected-layer and
all-layer destructive actions. Crop, image modals, multiple uploads,
filmstrips, Sources/Layers tables, selected-layer sticky behavior, Slideshow
and Guide remain outside this contract.

The plugin supports Moodle 4.5 and the active supervised target is Moodle 5.1.
The implementation retains the existing AMD module because that is the
plugin's established Moodle-4.5-compatible architecture.

## Server contract

The visual-preview form still sends `sesskey`, `categoryid`, `sourcekey` and
`previewlayerpayload`. JavaScript changes only `updatepreviewlayers=1` to
`updatepreviewlayersajax=1` for the local-refresh request.

`admin_manage.php` accepts that route only when all of the following remain
true:

- the request is `POST`;
- `confirm_sesskey()` succeeds;
- the selected source has already passed the page's management capability and
  source-resolution path;
- stored source settings exist.

The manager persists the payload, then the server exports and renders the full
`admin_selected` fragment. The JSON response contains that rendered fragment
and Moodle's translated `changessaved` message. A missing source-settings
record returns JSON with HTTP 422 and does not mutate the preview.

The classic `updatepreviewlayers` form route is intentionally retained as the
non-JavaScript and recovery fallback.

## Client contract

The Save button, Delete selected layer and Delete all layers share one
selected-source busy state. Once any of them is confirmed or submitted, all
three controls are disabled until the request settles. This prevents a stale
`previewlayerpayload` from being posted after a deletion, or a deletion from
starting while a save is in flight.

On success, the client replaces only the selected-source region with the
server fragment, runs the existing rehydration function, announces the Moodle
success toast, and returns focus to Save when it still exists. Error paths
retain the existing DOM, announce a Moodle error toast and return focus to the
initiating action. The two deletion actions continue to use the existing
single confirmation modal and the established EasyEdu bottom-end loading
feedback.

## Validation

Run the source/generated contract from the plugin root:

```powershell
.\tools\test-general-preview-async-contract.ps1
```

The dedicated supervised scenario is
`tools/playwright/ccb-general-preview-async.spec.js`. It must discover exactly
one test and run only after the candidate is pushed, the controlled preview
preflight reports a clean runtime, and the Moodle fixture lease is available.
It verifies no page reload, POST JSON save, shared confirmation, busy locking,
Moodle toasts and focus restoration. Moodle 4.5 and browser-matrix execution
remain explicit compatibility gaps until separately run.
