# Course Banner Builder Navigation contract

## Scope

`EED-NAV-2026-0006` adapts the immutable EasyEdu UI Kit Navigation/Guide
snapshot `f032a18aefc8f0816a2f36c52d6e6867cd9664b8` plus the flat desktop
rail correction at `e6aab3d7ca1c022efe7895b7d38a35b92b06aaa2` to the
`local_course_banner_builder` component. It does not import the UI Kit WIP,
the Guide engine or content, EasyStud destinations, or Focus and Loading
components.

Course Banner Builder declares Moodle 4.5 as its compatibility floor in
`version.php`. The adapter keeps the plugin's existing AMD architecture; it
does not make an unrelated JavaScript migration.

## Product-owned context

`classes/output/navigation.php` is the sole owner of CCB destinations:

| Identifier | Route |
| --- | --- |
| `course` | `admin_manage.php` |
| `site` | `admin_site.php` |
| `slideshow` | `admin_slideshow.php` |
| `transfer` | `admin_transfer.php` |
| `course-format` | `admin_manage.php?openformatmodal=1&bannerformatcontext=course` |
| `site-format` | `admin_site.php?openformatmodal=1&bannerformatcontext=site` |

It supplies labels, URLs, icons and the active state to the shared Mustache
templates. The format items reuse their existing destination and modal flow;
they are not a second navigation bar. A caller must select a declared
identifier; an unknown one is a coding error.

The legacy reset form is deliberately rendered as a small generic utility,
outside the navigation rail. Its definitive move to a dedicated **Plugin
Settings / danger zone** is a planned CCB-settings follow-up: it must retain
the existing capability, sesskey and confirmation flow. This is recorded here
for Platform allocation; it is not part of `EED-NAV-2026-0006` and has not
changed the destructive operation.

## Rendering and Guide boundary

The rail is rendered by `templates/easyedu_navigation.mustache` and its item
partial. Public `data-easyedu-navigation-*` attributes are part of the local
contract and must not be renamed casually.

`scss/components/_easyedu-adapter.scss` includes the imported Navigation mixin
only below `.local-course-banner-builder-admin--native`; it does not apply
Navigation styles to Moodle or Bootstrap globals and no longer restyles desktop
destinations locally. The desktop Guide label is an out-of-flow capsule and the
compact Guide projection is one full-width gradient row. Those two rules
protect the centred destination rail, prevent launcher-shadow clipping, and
keep the compact icon and label in one target.

The compact trigger is a fixed left-edge half-pill centred in the viewport. It
does not calculate a position from Moodle's native drawer control or from page
scrolling, so the native top-edge control remains a separate, non-overlapping
surface. Its resting dimensions use the shared 2.75rem (44px) touch-target
token on both axes; its text stays visually collapsed until hover or keyboard
focus.

Desktop destination icons remain plain glyphs in a fixed alignment slot. The
compact panel deliberately restores the EasyStud icon treatment: a small
neutral bordered tile at rest and a primary-colour tile for the active entry.
The surrounding navigation row remains the single interactive target.

The Guide launcher keeps `aria-label` as its accessible name. It deliberately
omits `data-easyedu-navigation-popover`, `title` and equivalent tooltip hooks:
the animated desktop label and the permanent compact label already provide the
visible explanation, so an additional hover bubble would be redundant. This
is a required consumer rule for the next canonical UI Kit snapshot.

The CCB embedded Responsive surface predates the five public mixins used by the
snapshot. Its local supplement is deliberately limited to the Navigation panel,
backdrop, trigger, section and link helpers copied from the same immutable
snapshot. It does not import the broader Responsive component, Focus or
Loading.

Course, Site and Slideshow provide their existing rendered Guide root through
the `guidehtml` slot. The content remains CCB-owned. The Navigation/Guide
bridge:

1. initialises the rail;
2. moves the complete Guide root to `document.body`;
3. retains the existing CCB Guide root class and resolved EasyEdu tokens;
4. projects one launcher into desktop and compact navigation; and
5. closes the compact panel before it opens the Guide.

The compact navigation panel is transformed during its animation and sits at
layer `1066`. The local portal adapter raises only a portalled CCB Guide modal
above that panel. Raising a z-index without the portal is not sufficient,
because a transformed ancestor creates a separate stacking context.

`admin_transfer.php` uses the same rail but has no Guide root, so the Guide
slot remains absent there.

## Runtime order

For pages with a Guide, Moodle registers the modules in this order:

1. product page module;
2. existing CCB administration-navigation module;
3. `easyedu_navigation_guide.init('[data-easyedu-navigation]')`;
4. existing `easyedu_guide.init(...)`.

The bridge invokes the idempotent Navigation controller itself. This means the
complete Guide root is already portalled before the existing CCB Guide binds
its events and target selectors.

## Required validation

Before runtime promotion, execute source checks, build the SCSS and AMD assets
from an approved full Moodle checkout, then request a lease-gated runtime
preview. Human review must cover Course, Site, Slideshow and Transfer at
desktop and at 390 px:

- each route has the same four CCB destinations, the two format entries and
  the correct active item;
- Tab, Escape, backdrop and focus return work in the compact panel;
- the desktop Guide capsule does not move the centred destinations or clip its
  shadow, has no second hover bubble, and the compact Guide is one full-width
  gradient target where available;
- desktop destination icons remain plain and compact icons use the EasyStud
  neutral/active tile states; the closed compact handle measures 44px by 44px
  and its expanded state shows the complete localized CCB label;
- a compact Guide opens in a viewport overlay above the panel, closes cleanly
  and has no console error or overflow; and
- the two format links open their existing format modal flow, and the existing
  reset confirmation still works from its temporary generic-utility location.

Store browser evidence outside Git with the required manifest. The exact
source and generated-asset checks are listed in
`docs/testing/functional-protocol.md` and `docs/testing/release-checklist.md`.
