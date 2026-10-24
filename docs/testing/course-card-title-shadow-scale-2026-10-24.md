# Course-card title shadow and configured size

Catalogue, dashboard and My courses thumbnail titles now use unshadowed text,
as full-size course banners already do. The frame/background and padding-free
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
