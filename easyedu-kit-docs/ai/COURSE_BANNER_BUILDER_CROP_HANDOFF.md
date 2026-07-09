# Course Banner Builder Crop Handoff

Use this note when another Codex/chat window needs to continue work on the
Course Banner Builder crop system without reconstructing the whole history.

## Scope

Target plugin:

- `local_course_banner_builder`

Primary file:

- `amd/src/admin_manage.js`

Related files:

- `amd/build/admin_manage.min.js`
- `amd/build/admin_manage.min.js.map`
- `styles.css`
- `scss/components/_border-preview.scss`
- `easyedu-kit-docs/docs/examples/course-banner-builder.md`
- `CHANGELOG.md`

## What was changed

### 1. Session-only crop geometry

The crop workflow now keeps temporary edit-session geometry in DOM attributes
that are explicitly session-only:

- `data-preview-crop-session-source-left`
- `data-preview-crop-session-source-top`
- `data-preview-crop-session-source-width`
- `data-preview-crop-session-source-height`
- `data-preview-crop-session-placement`
- `data-preview-crop-session-initial-crop`
- `data-preview-crop-session-dirty`

These are used only while a crop editor is open.

The durable crop state remains:

- `imagecropenabled`
- `imagecropleftpercent`
- `imagecroptoppercent`
- `imagecropwidthpercent`
- `imagecropheightpercent`

### 2. Second-crop drift fix

Problem:

- reopening an existing crop in the add-image modal;
- clicking apply without changing the crop;
- image slowly shrank or drifted on every reopen/apply cycle.

Fix:

- store the placement state and initial crop state when crop editing starts;
- if the crop session is still clean when apply happens, reuse the stored
  placement state instead of recalculating placement from current DOM geometry.

This was implemented through:

- `localCourseBannerBuilderSetCropSessionPlacementState()`
- `localCourseBannerBuilderGetCropSessionPlacementState()`
- `localCourseBannerBuilderSetCropSessionInitialCropState()`
- `localCourseBannerBuilderGetCropSessionInitialCropState()`
- `localCourseBannerBuilderSetCropSessionDirty()`
- `localCourseBannerBuilderIsCropSessionDirty()`
- `localCourseBannerBuilderCropStatesMatch()`

and a guarded early return in:

- `localCourseBannerBuilderGetCropSelectionCustomState()`

### 3. Fixed crop action placement

Problem:

- crop apply/cancel buttons were often forced back inside the crop frame.

Fix:

- crop action placement now uses viewport-fixed positioning based on the crop
  box rect and frame rect;
- candidate positions are scored to minimise overlap with the crop box.

Relevant function:

- `localCourseBannerBuilderRefreshCropEditor()`

Related CSS:

- `.local-course-banner-builder-crop-actions` now uses `position: fixed`.

### 4. Draft visual layer protection

Problem:

- during crop editing in the add-image modal, the draft visual/context layer
  could inherit the active crop layout and temporarily re-expand or overlay
  other images.

Fix:

- `localCourseBannerBuilderMirrorDraftSelectionVisual()` now treats active crop
  editing as a special case and avoids copying the temporary crop-edit style
  into the visual mirror.

### 5. Release-click suppression

Problem:

- if the crop was enlarged while another image sat underneath, releasing the
  pointer could select the background image.

Fix:

- a short click-suppression window is now applied after crop pointer release;
- modal preview and source preview are handled separately.

Relevant state:

- `localCourseBannerBuilderSuppressModalPreviewClickUntil`
- existing `localCourseBannerBuilderSuppressSourcePreviewClickUntil`

Relevant function:

- `localCourseBannerBuilderStopCropInteraction()`

Relevant global click handler:

- early suppression checks in the main `document.addEventListener('click', ...)`
  preview selection block.

## Required invariants

Do not break these:

1. Reopening a crop must reconstruct the expanded source image from the visible
   layer placement plus persisted crop rectangle, not from an already cropped
   visual node.
2. Applying a reopened crop without changing anything must preserve the exact
   visible placement.
3. Draft visual/context layers must never become a second truth for crop
   session geometry.
4. Crop action buttons must remain outside the crop box.
5. Releasing the pointer after crop drag/resize must not change the selected
   image.

## Regression scenarios to rerun

Always rerun these after touching crop code:

1. General preview crop, verify apply/cancel placement.
2. Add-image modal, first crop then apply.
3. Add-image modal, second crop of same image with no movement, then apply.
4. Add-image modal, crop image A, switch to image B, return to A.
5. Add-image modal, enlarge crop while image B sits underneath, then release.

## Commands used

Syntax/build:

```powershell
node --check amd/src/admin_manage.js
npx terser amd/src/admin_manage.js --compress --mangle --source-map "filename='admin_manage.min.js.map',url='admin_manage.min.js.map'" -o amd/build/admin_manage.min.js
node --check amd/build/admin_manage.min.js
git diff --check
```

If the browser still appears to use old AMD output on the Moodle 5.1 local
instance, clear:

- `moodledata/localcache/js`
- `moodledata/localcache/requirejs`

## Prompt seed for another Codex window

Use this as a starting point:

> You work on Moodle plugin `local_course_banner_builder` in Moodle 5.1.
> Focus only on the crop system in `amd/src/admin_manage.js`.
> Read `easyedu-kit-docs/docs/examples/course-banner-builder.md` and
> `easyedu-kit-docs/ai/COURSE_BANNER_BUILDER_CROP_HANDOFF.md` first.
> Preserve these invariants:
> - session-only crop geometry stays session-only;
> - second crop of the same image must not drift when apply is clicked without changes;
> - draft visual layers must not inherit active crop-edit layout;
> - crop action buttons stay outside the crop box;
> - crop pointer release must not select the image underneath.
> Rebuild the AMD artifact after changes and rerun the documented regression scenarios.
