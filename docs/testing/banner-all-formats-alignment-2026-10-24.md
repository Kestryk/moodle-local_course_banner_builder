# All banner formats align to the visible content edge

Both course and site mount scripts use the existing measureContentEdges helper
for every format, including standard and contentwide. Static standard centering
previously aligned to the Moodle column, leaving its internal 15px gutters outside
the banner boundary on Dashboard, My courses, catalogue and course pages.

Full-width root/scroll flags and navbar offsets stay exclusive to fullwidthtop,
fullwidthtopcompact and fullwidthtopinset. Standard keeps its normal vertical
placement, radius, aspect ratio and titles. The existing deliberate inset of the
fullwidthtopinset variant is preserved relative to the same measured content edge.
The page-header-free site fallback also aligns its shell after mounting.

No theme/core margins or settings are changed by the product code. Mount and resize
reuse one alignment implementation per existing site/course runtime, with the same
scheduled follow-up measurements. Tests compare real native banner rectangles to
the measured content frame; production changes require local before/after proof.
