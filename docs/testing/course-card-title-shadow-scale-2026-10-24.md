# Course-card title shadow and configured size

Catalogue, dashboard and My courses thumbnail titles now use unshadowed text,
matching the default full-size banner paint. The frame/background and padding-free
two-line clamp remain unchanged.

The course-title size setting is a percentage, not a fixed pixel size. Before
this correction, get_course_card_title_options omitted it and thumbnails used
only their responsive CSS clamp. The bounded percentage (25–480%, default
100%) now reaches --local-course-banner-builder-card-title-scale; CSS multiplies
the existing responsive base by it. A 150% setting therefore paints at 1.5 times
the 100% size. Full-size banner sizing and position are unchanged.

Native validation uses authenticated local administration, short/long titles,
catalogue and Card layouts at 1600/768/390px, and 100%/150% settings. Compare
computed font size, shadow, clamp, containment and the rendered admin value.
Restore settings, remove owned test courses/account and restore runtime code
from its verified backup. No production deployment is included. Baseline CSS
is supplied in the browser; actual markup and saved settings stay native.

The final native catalogue run passed twelve before/after cases for two titles.
Admin 100% paints at 12.8px (1600) / 9.92px (768/390); 150% now paints at
19.2px / 14.88px instead of ignoring the setting. The clamp and containment pass.
Dashboard presentation was not certified by that catalogue run.

Full-banner reservation: the local copy has an explicit title-shadow setting
enabled, which still emits an inline shadow and overrides the shadow-free
default CSS. This is unchanged before/after the thumbnail patch; do not report
an unconditional shadow-free full-banner PASS for that configuration. Changing
the explicit setting or its product policy is outside the thumbnail correction.
