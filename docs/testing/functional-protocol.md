# Functional test protocol

## Purpose

This protocol defines the product-level checks that generic Moodle validation
cannot infer for Course Banner Builder. It separates current automated coverage
from candidate tests that still require deterministic fixtures.

## Supported environments

| Environment | Expected use |
| --- | --- |
| Moodle 4.5 | Minimum supported Moodle version and current platform matrix target |
| Moodle 5.1 | Active development and regression environment |
| Standard Moodle theme | Required fallback validation |
| EasyEdu theme | Required visual integration validation when available |

The plugin currently declares Moodle 4.5 as its minimum version.

## Current capability contract

The builder uses one system capability:

`local/course_banner_builder:manage`

It is defined at `CONTEXT_SYSTEM` and allowed for the manager archetype. The
current expected access matrix is therefore:

| User | Expected builder access |
| --- | --- |
| Site administrator | Allowed |
| System manager with the capability | Allowed |
| Course teacher without the system capability | Denied |
| Enrolled student | Denied |

The rendered course banner can be visible to normal course users, but the
administration pages are not currently course-context management pages. A
future course-context capability must be designed and tested separately rather
than assumed by this protocol.

## Fixture manifest

No reusable Behat data generator currently creates a complete CCB source. The
following fixtures are required before interaction-heavy scenarios become CI
gates.

### Users and courses

- one administrator;
- one system manager with `local/course_banner_builder:manage`;
- one editing teacher without that system capability;
- one enrolled student;
- one empty course using a standard Moodle theme;
- one course with a forum, assignment, quiz, and course overview image.

### Source rules

- parent and child course categories;
- a course in the child category;
- enabled course custom fields with at least two distinct values;
- a category source;
- a custom-field source;
- a two-level inheritance chain;
- cumulative and random composition examples;
- a source with no layers for empty-state checks.

### Files and visual layers

- small JPEG background;
- transparent PNG foreground;
- large valid image;
- unsupported file type;
- file exceeding the configured limit;
- source with two or more image layers;
- cropped image layer;
- border, overlay, and title layers;
- site and course slideshow content.

Fixtures must use distributable test assets with an explicit compatible
license. Do not depend on files from a developer's draft area.

## Test catalogue

Status values:

- `Existing`: automated now.
- `Candidate`: suitable after fixtures exist.
- `Manual`: intentionally manual because the visual result is part of the
  contract.
- `Manual accepted`: manually reviewed on the recorded managed preview; it is
  not browser automation or cross-version compatibility evidence.
- `Blocked`: required fixture or stable selector is missing.

| ID | Flow | Expected result | Owner | Status |
| --- | --- | --- | --- | --- |
| AUTH-01 | Administrator opens course banner management | Page and CCB root load | Behat | Existing |
| AUTH-02 | System manager opens builder | Access allowed | Behat | Blocked |
| AUTH-03 | Teacher without system capability opens builder | Access denied | Behat | Blocked |
| AUTH-04 | Student opens builder | Access denied | Behat | Blocked |
| LOAD-01 | Course banner admin loads with no configured source | CCB root remains available | Behat | Existing smoke |
| LOAD-02 | Site, slideshow, and transfer views load | Correct CCB root and shared Navigation rail | Behat | Candidate |
| NAV-01 | Open every CCB administration destination | Rail exposes Course, Site, Slideshow, Transfer, Course format and Site format; desktop icons remain plain, while compact icons use the EasyStud neutral tile and primary active state | Playwright/manual | Candidate |
| NAV-02 | Open and close the compact rail | The closed left-centred half-pill is 44px by 44px and its hover/focus state shows the complete localized label; backdrop, Escape and focus return work without horizontal overflow or collision with Moodle's native drawer control | Playwright/manual | Candidate |
| SRC-01 | Create and select category source | Source becomes active | Behat | Blocked |
| SRC-02 | Create custom-field source | Value-specific source is available | Behat | Blocked |
| SRC-03 | Configure inheritance chain | Final layer order follows inheritance rules | PHPUnit/Behat | Blocked |
| IMG-01 | Upload a valid image through filepicker | Draft image appears once | Playwright | Blocked |
| IMG-02 | Drop a valid image | Same state as filepicker upload | Playwright | Blocked |
| IMG-03 | Upload invalid type or oversized file | Localised error, no layer created | Behat/Playwright | Blocked |
| IMG-04 | Add and select one image layer | Handles and image tools target that layer | Playwright | Blocked |
| IMG-05 | Replace or delete an image | Preview and persisted state update | Playwright | Blocked |
| CROP-01 | Normalise crop percentages | Values clamp to valid geometry | PHPUnit | Existing |
| CROP-02 | Compute effective crop dimensions | Visible dimensions match crop | PHPUnit | Existing |
| CROP-03 | Raster crop | Output dimensions match crop | PHPUnit | Existing |
| CROP-04 | Open crop editor | Original image and crop selection are shown | Playwright | Blocked |
| CROP-05 | Move and resize crop, then validate | Crop persists after reload | Playwright | Blocked |
| CROP-06 | Cancel crop | Previous persisted geometry is restored | Playwright | Blocked |
| CROP-07 | Crop two draft images independently | Switching selection preserves both crops | Playwright | Blocked |
| LYR-01 | Reorder layers by buttons | Preview z-order changes and persists | Behat/Playwright | Blocked |
| LYR-02 | Reorder layers by drag/drop | Same persisted order as button flow | Playwright | Blocked |
| LYR-03 | Push image above overlay/border | Conflicting order controls are disabled | Playwright | Blocked |
| SAVE-01 | Save and reload source | All visual settings persist | Behat/Playwright | Blocked |
| PREV-01 | Resize admin preview | Layout remains usable at desktop/tablet/mobile | Playwright/manual | Manual |
| PREV-02 | Render final course banner | Position, crop, title, border, and overlay agree with preview | Visual/manual | Manual |
| THEME-01 | Use standard Moodle theme | Builder and banner retain a complete fallback UI | Behat/Playwright | Blocked |
| GUIDE-01 | Open guide and navigate slides | Slide state and keyboard navigation remain stable | Playwright | Blocked |
| GUIDE-02 | Show target in interface | Highlight stays aligned and return panel appears | Playwright | Blocked |
| GUIDE-03 | Complete guided path | Checklist, prerequisite, reload, and return state work | Playwright | Blocked |
| GUIDE-04 | Open Guide from the CCB rail | Desktop label reveal does not shift destinations, clip its shadow or create a second tooltip; compact launcher closes the panel, opens Guide above it and returns focus after close | Playwright/manual | Manual accepted — 2026-08-08 |
| ERR-01 | Open builder without sources | Actionable empty state, no console error | Behat | Candidate |
| ERR-02 | Server-side validation fails | Localised message and submitted data remain understandable | Behat | Blocked |
| A11Y-01 | Scan admin root | No serious or critical plugin-region axe violations | Behat/Playwright | Existing smoke |
| A11Y-02 | Keyboard-only core flow | Focus visible, modal focus restored, non-drag controls work | Manual/Behat | Manual |
| TRF-01 | Export settings and files | Package contains selected supported data | PHPUnit/Behat | Blocked |
| TRF-02 | Import package into clean site | Data maps safely and missing dependencies are reported | PHPUnit/Behat | Blocked |

## Stable automation order

1. Keep the existing crop helper PHPUnit tests mandatory.
2. Run the plugin-root accessibility smoke on an empty admin page.
3. Add a Behat data generator for categories, custom fields, sources, and
   layers.
4. Add stable Behat access, empty-state, save/reload, and validation scenarios.
5. Add Playwright for filepicker, drop, crop, resize, guide, and responsive
   flows.
6. Promote a test to the EasyEdu platform matrix only after it is deterministic
   on a clean disposable Moodle site.

## Detailed manual scenarios

### Image and crop regression

1. Add at least two images in one add-layer modal.
2. Select image A, move and resize it, crop it, and validate the crop.
3. Select image B and repeat with different geometry.
4. Re-select image A without dragging it.
5. Confirm the crop remains visible immediately and the image does not flash
   back to its full contents.
6. Re-open crop on image A, select another source area, and validate.
7. Confirm image B remains unchanged.
8. Save, reload, and compare modal, general preview, and final banner.

### Layer order regression

1. Prepare three image layers plus an overlay and border.
2. Reorder images using buttons, then drag and drop.
3. Confirm the table order, preview z-order, and saved order agree.
4. Enable above-overlay or above-border states.
5. Confirm incompatible order controls and drag/drop are disabled.
6. Remove the special state and confirm normal ordering becomes available.

### Responsive rendering

Check at minimum:

- 1600 x 900;
- 1024 x 768;
- 768 x 1024;
- 390 x 844;
- browser zoom at 200%.

Verify the real banner and every applicable preview preserve crop, relative
position, title frame proportions, border containment, overlay order, and
slideshow controls.

## Reset and cleanup

- Use a disposable Moodle site or restore a database snapshot before a full
  protocol run.
- Delete CCB settings through the plugin UI only on disposable data.
- Purge Moodle caches after changing compiled SCSS or AMD build artifacts.
- Remove test draft files and generated course overview images after the run.
- Record the source ids, course ids, theme, Moodle version, browser version,
  and fixture revision in release evidence.

## Future EasyEdu platform matrix

Candidate jobs:

| Job | Moodle | Gate content |
| --- | --- | --- |
| `course-banner-builder-45` | 4.5 | Existing lint, package, PHPUnit; later stable Behat |
| `course-banner-builder-51` | 5.1 | Same checks plus current JS-heavy regression suite |
| `course-banner-builder-a11y` | 4.5 and 5.1 | Plugin-region Behat axe smoke |
| `course-banner-builder-browser` | 5.1 | Opt-in Playwright crop, guide, and responsive flows |

The browser job must remain manual until fixtures and browser timing are stable.
