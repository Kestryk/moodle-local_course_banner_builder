# Local Course Banner Builder (Moodle Plugin)

This plugin is in a beta test and I am already working on some fixes for a soon to be released updated version. Don't hesitate to provide any feedback, sitbacks and bugs you may have encountered. 

> Overview

Course Banner Builder is a Moodle local plugin that helps administrators build,
preview, and manage course and site banners directly from Moodle.

The plugin is designed to provide a visual banner editor while keeping Moodle
administration workflows safe and predictable:

- banner rules can be configured by source, category, or custom field;
- source chains allow inherited banner content across parent and child sources;
- image layers, border layers, banner titles, and slideshows can be edited with
  live previews;
- import and export tools help move banner configurations between platforms;
- guided tours help administrators discover the full editor.

> Goals

- Provide a native Moodle banner management experience.
- Avoid manual theme or template changes for banner customisation.
- Support inherited banner rules for complex course structures.
- Keep previews close to the final rendering used on courses and site pages.
- Make advanced visual settings editable without requiring CSS knowledge.
- Keep plugin data portable through export and import packages.

> Key Features

## Course Banner Management

- Enable or disable course banners globally.
- Enable or disable banners on activity pages.
- Optionally use Moodle default course overview images when no banner rule is
  configured.
- Configure banner sources based on course categories or custom fields.
- Use source chains to inherit layers from parent sources.
- Choose cumulative rendering or random image rendering for a source.
- Preview inherited layers without making inherited content editable.

## Site Banner Management

- Configure a site-wide banner independently from course banners.
- Enable or disable the site banner from the dedicated administration page.
- Edit site title rendering separately from course title rendering.

## Banner Formats

The plugin supports several banner layouts, including:

- standard banner placement between the page title and navigation;
- full-width banner above the page title;
- compact full-width banner above the page title;
- wide content banner formats.

Preview areas are adapted to the selected banner format so that percentage-based
positioning, sizing, and cropping remain consistent with the final output.

## Image Layers

- Add one or more image layers at once.
- Edit layer name, sort order, enabled state, and rendering order.
- Drag, resize, crop, and position images visually.
- Use alignment guides and optional snap-to-guide behaviour.
- Choose sizing behaviour:
  - keep original image dimensions;
  - keep proportions;
  - fill the banner;
  - use a custom size.
- Adjust opacity without affecting the selection frame.
- Move image layers above or below a border layer.
- Bring layers forward or send them backward.
- Show or hide other layers while editing.
- Cropped layers are marked in layer lists with a discreet crop indicator.

## Border Layers

- Add and edit a dedicated border layer for a source.
- Configure border sides, colour, opacity, thickness, fade, style, dash length,
  rounded corners, and inner rounding.
- Prevent conflicting border rules across a source chain.
- When a parent source border is saved, active child borders in the same chain
  can be confirmed and disabled to avoid duplicated borders.

## Banner Titles

- Display course, activity, or site titles on top of banners.
- For activity pages, choose whether to display the course title, activity
  title, both, or no title.
- Configure position, font, colour, size, all caps, frame, frame shadow, text
  shadow, and banner overlay.
- Edit title placement visually with live previews.

## Slideshows

- Configure optional banner slideshows for course and site contexts.
- Display Moodle content such as course items, forum announcements, activities,
  due dates, and action links.
- Edit slideshow label, title, paragraph, and view button appearance.
- Configure overlay, colours, fonts, opacity, shadows, alignment, and all caps.
- Use responsive previews and alignment guides while editing.
- Slideshows are disabled by default.

## Import and Export

- Export banner configuration as a package.
- Import banner configuration on another Moodle platform.
- Transfer source rules, layers, files, title settings, slideshow settings, and
  related plugin options.
- Legacy JSON import and export have been removed in favour of the package
  workflow.

## Guided Tours

- Detailed Moodle User Tours are installed with the plugin.
- Tours cover the main administration views and important modal workflows.
- Tours are multilingual where language strings are available.
- Upgrade logic avoids duplicating existing tours.

> How to Access the Tool

Site administration -> Plugins -> Local plugins -> Course Banner Builder

Direct URLs:

- Course banner management: `/local/course_banner_builder/admin_manage.php`
- Site banner management: `/local/course_banner_builder/admin_site.php`
- Slideshow management: `/local/course_banner_builder/admin_slideshow.php`
- Import and export: `/local/course_banner_builder/admin_transfer.php`

> Typical Administrator Workflow

1. Open the Course Banner Builder administration page.
2. Enable course or site banners as needed.
3. Select or create a banner source.
4. Configure the source chain and composition mode.
5. Add image and border layers.
6. Adjust crop, size, opacity, order, and inherited layer visibility.
7. Configure optional banner titles.
8. Configure optional slideshow appearance.
9. Preview the final rendering.
10. Save the configuration.
11. Export the package if the configuration must be reused elsewhere.

> Source Composition Modes

## Cumulative

Cumulative mode renders enabled layers together. Lower sort orders are rendered
first, and higher sort orders appear above them. This mode is useful when a
banner is built from several images, overlays, or decorative layers.

## Random

Random mode keeps the source editable as a list of layers, but only one enabled
image layer is selected for display. This is useful when a category or source
should rotate between several possible banner images without showing them all at
the same time.

> Languages

Language files are provided for:

- English
- French
- German
- Spanish
- Italian
- Portuguese

All user-facing strings should be defined in the plugin language files and
displayed through Moodle `get_string()` calls.

> Privacy

The plugin implements the Moodle Privacy API.

Course Banner Builder stores banner configuration, visual settings, source
rules, and related files. It does not need to store personal user information to
provide its main features.

> Permissions

The plugin is intended for Moodle administrators and managers who can access the
plugin administration pages and manage banner configuration.

> Technical Information

- Plugin type: Local (`local/course_banner_builder`)
- Component: `local_course_banner_builder`
- Minimum Moodle version: 4.5 (`2024100700`)
- Designed for Moodle 4.5 and tested during Moodle 5.1 compatibility work
- Current release: `0.6.16`
- Current maturity: `MATURITY_BETA`

The plugin stores its configuration in Moodle configuration records, dedicated
`local_course_banner_*` database tables, Moodle file areas, and Moodle User Tour
definitions.

> Upgrade Behaviour

- Database upgrades preserve existing banner configuration.
- New settings are added with safe defaults.
- Slideshows remain disabled by default unless explicitly enabled.
- Guided tours are installed or updated when missing.
- Existing tours are not duplicated.

> Coding Standards

The plugin follows Moodle coding style requirements. PHP files use Moodle
boilerplate headers, Moodle language strings, and Moodle APIs where applicable.

Before release, the plugin should be checked with Moodle Code Checker against
the target Moodle version.

> Maturity

`MATURITY_BETA`

The plugin is approaching a first production-ready release, but should still be
tested carefully on staging platforms before deployment.

> License

GNU General Public License v3 or later.

> Roadmap and Contributions

Feedback from Moodle administrators, teachers, and platform maintainers is
welcome. Suggested improvements include additional banner format presets,
additional guided tour refinements, and broader theme compatibility testing.
