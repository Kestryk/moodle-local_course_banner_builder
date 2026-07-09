You are working on the Moodle plugin `local_course_banner_builder` on the
Moodle 5.1 local instance.

Before changing anything, read these files completely:

- `easyedu-kit-docs/docs/examples/course-banner-builder.md`
- `easyedu-kit-docs/ai/COURSE_BANNER_BUILDER_CROP_HANDOFF.md`
- `easyedu-kit-docs/ai/AI_RULES.md`
- `easyedu-kit-docs/ai/IMPLEMENTATION_CHECKLIST.md`
- `easyedu-kit-docs/ai/MOODLE_PLUGIN_RULES.md`

## Goal

Continue work on the Course Banner Builder crop system without regressing the
recent stabilisation work.

Primary implementation file:

- `amd/src/admin_manage.js`

Related generated/build files:

- `amd/build/admin_manage.min.js`
- `amd/build/admin_manage.min.js.map`

Related style files:

- `styles.css`
- `scss/components/_border-preview.scss`

## Current crop architecture

The crop system now distinguishes:

1. **Durable crop state**  
   This is the only persistent crop truth:
   - `imagecropenabled`
   - `imagecropleftpercent`
   - `imagecroptoppercent`
   - `imagecropwidthpercent`
   - `imagecropheightpercent`

2. **Session-only crop edit state**  
   Used only while the crop editor is open:
   - `data-preview-crop-session-source-left`
   - `data-preview-crop-session-source-top`
   - `data-preview-crop-session-source-width`
   - `data-preview-crop-session-source-height`
   - `data-preview-crop-session-placement`
   - `data-preview-crop-session-initial-crop`
   - `data-preview-crop-session-dirty`

Do not reintroduce a second durable crop truth through mirrored preview layers,
legacy `imagecropsource*` persistence, hidden form fields, or draft visual
layers.

## Fixes already in place

These behaviours were intentionally fixed and must stay correct:

1. Reopening a crop in the add-image modal and clicking apply without changing
   the crop no longer shrinks or drifts the image.
2. Crop apply/cancel buttons are positioned outside the crop box using a fixed
   overlay placement strategy.
3. Draft visual/context layers do not inherit the temporary crop-edit layout of
   the active image.
4. Releasing the pointer after enlarging a crop over another image does not
   select the image underneath.

## Invariants you must preserve

1. Reopening an existing crop must reconstruct the expanded source image from
   the visible placement plus crop rectangle, not from a visually cropped DOM
   node.
2. Applying a reopened crop without interaction must preserve visible placement
   exactly.
3. Modal multi-image crop sessions must remain isolated from one another.
4. Crop controls must remain outside the crop box.
5. Pointer release after crop drag/resize must not trigger selection changes on
   underlying images.

## Validation required after changes

Run at minimum:

```powershell
node --check amd/src/admin_manage.js
npx terser amd/src/admin_manage.js --compress --mangle --source-map "filename='admin_manage.min.js.map',url='admin_manage.min.js.map'" -o amd/build/admin_manage.min.js
node --check amd/build/admin_manage.min.js
git diff --check
```

If AMD changes do not appear in the browser on Moodle 5.1, clear:

- `moodledata/localcache/js`
- `moodledata/localcache/requirejs`

## Mandatory regression scenarios

Re-test these scenarios, ideally with targeted headless probes if browser
automation is available:

1. General preview crop: buttons stay outside the crop box.
2. Add-image modal: first crop then apply.
3. Add-image modal: second crop of the same image with no crop movement, then
   apply.
4. Add-image modal: crop image A, switch to image B, return to A.
5. Add-image modal: enlarge crop while another image sits underneath, then
   release pointer.

## Constraints

- Keep Moodle compatibility in mind, especially 4.5/5.1 shared behaviour where
  possible.
- Do not do broad refactors outside the crop system.
- Do not rely only on screenshots or assumptions when a short probe can confirm
  the bug precisely.
- Update docs/changelog when the crop contract changes.
