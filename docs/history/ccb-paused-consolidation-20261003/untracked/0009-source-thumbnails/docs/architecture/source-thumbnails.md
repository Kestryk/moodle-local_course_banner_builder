# Source thumbnails contract

## Status

EED-CCB-2026-0009-A introduces the stored source-level rule used by later
native Moodle thumbnail rendering work. It does not change any current card,
coursebox, Moodle renderer, AMD module, generated image, cache, or UI.

## Rule storage

`local_course_banner_builder_order.thumbnailenabled` belongs to a CCB source,
not to an individual course. It defaults to enabled so upgrading an existing
site preserves its historical generated-thumbnail behaviour.

The rule is evaluated against the existing effective source/layer chain. A
later rendering sub-lot will consume
`manager::get_thumbnail_layer_specs_for_course()` for Dashboard/My courses
wide cards and native coursebox square cards.

## Precedence and fallback

1. A valid teacher-managed Moodle course overview image wins when Teacher image
   preservation is enabled.
2. Otherwise the current CCB source inheritance chain supplies thumbnail
   layers from sources whose rule is enabled.
3. If a winning source or all its drawable layers disappear, CCB resolves the
   next eligible inherited source.
4. If no eligible source remains, CCB removes its managed derived files and
   Moodle renders its native fallback. Missing GD must take this same safe path.

The rule applies only to course thumbnails. It does not enable/disable public
course banners, delete a teacher image, or introduce a per-course toggle.

## Lifecycle and migration

The upgrade adds a non-null field with default `1`. It does not regenerate
files or alter courses during upgrade. Source/layer mutations already use the
source sync path; the later native-rendering sub-lot will switch that path to
the thumbnail-specific layer resolver.

Generated image files remain derived artifacts. They are not transfer or
backup payload. EED-CCB-2026-0009-F will extend the CCB Transfer configuration
schema with the source rule and regenerate outputs after import.

## Compatibility

The plugin declares Moodle 4.5 as its minimum version. This storage contract
uses Moodle's established XMLDB upgrade API and no Moodle 5.x-only API.
Moodle 5.1.3 source was audited; executable Moodle 4.5 and 5.1 upgrade tests
remain required before a release claim.
