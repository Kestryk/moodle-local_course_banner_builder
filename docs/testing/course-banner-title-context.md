# Course banner title context

Course title settings apply to course sections, course settings and course-scoped
tools as well as the main course page. Only an actual module page uses activity
title settings. The existing course-title fallback for activities remains unchanged.

## Regression protocol

Use an authenticated Moodle 5.3 test site with a configured course banner and
course title enabled, activity title disabled. Compare the main course page,
`course/section.php`, `course/edit.php`, Student Management and Mass Import.
Each must render one visible banner title containing the course name; the main
course page's banner geometry must remain unchanged. Repeat at 1600 and 390 px.

Also check the payload with course/activity title settings off/off, on/off,
off/on and on/on. Non-module course pages follow the course setting independently
of the activity setting. Module pages preserve activity-only, combined-title and
course-only fallback behavior. Technical layouts and site banners are unchanged.

The title is emitted by CCB's PHP payload and HTML renderer, not injected by the
course editor or a delayed title JavaScript callback. Missing title HTML should
therefore be diagnosed before changing mount timing, font loading or Moodle core.
