<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_course_banner_builder;

// phpcs:disable moodle.Files.MoodleInternal.MoodleInternalNotNeeded
defined('MOODLE_INTERNAL') || die();
// phpcs:enable moodle.Files.MoodleInternal.MoodleInternalNotNeeded
// phpcs:disable moodle.Files.LineLength.TooLong -- Hook output includes long generated CSS and JS selectors.

/**
 * Output hook callbacks.
 *
 * @package    local_course_banner_builder
 * @copyright  2026 Kevin Jarniac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Whether banner hooks may safely use Moodle configuration tables.
     *
     * @return bool
     */
    protected static function can_render_banners(): bool {
        return !function_exists('during_initial_install') || !during_initial_install();
    }

    /**
     * Build the native banner payload for the current course page.
     *
     * @param \moodle_page $page
     * @return array|null
     */
    protected static function get_native_course_banner_payload(\moodle_page $page): ?array {
        if (!self::can_render_banners()) {
            return null;
        }

        $courseid = (int)($page->course->id ?? 0);
        $iscoursebannerpage = self::is_course_banner_page($page);
        $hascourselayers = $iscoursebannerpage && manager::course_has_applicable_banner_layers($page->course);
        $coursecontext = $courseid > SITEID ? \context_course::instance($courseid, IGNORE_MISSING) : null;
        $hascustomcourseimage = $coursecontext && manager::course_has_custom_overview_image($coursecontext->id);
        $usedefaultimage = $iscoursebannerpage &&
            !$hascourselayers &&
            !$hascustomcourseimage &&
            manager::course_default_image_banners_enabled();
        $themehasbanner = manager::theme_seems_to_provide_course_banner();
        $themeblocksnativebanner = $themehasbanner && self::is_course_view_page($page);
        $allownativebanner = $iscoursebannerpage &&
            manager::is_course_banner_enabled() &&
            !$themeblocksnativebanner &&
            ($hascourselayers || $usedefaultimage);

        if (!$allownativebanner) {
            return null;
        }

        $hasbackground = $usedefaultimage || manager::course_has_native_banner_background($page->course);
        $overlays = manager::get_course_header_image_overlays($page->course);
        $border = manager::get_course_header_border_overlay($courseid);
        if (!$hasbackground && empty($overlays) && empty($border)) {
            return null;
        }

        $bannerurl = $usedefaultimage ?
            manager::get_generated_course_image_url($courseid) :
            ($hasbackground ? manager::get_course_banner_image_url($courseid) : null);
        $titlecontext = self::is_course_view_page($page) ? 'course' : 'activity';
        $coursetitle = (string)($page->course->fullname ?? '');
        $activitytitle = (string)($page->cm->name ?? $page->heading ?? '');
        $titletext = $coursetitle;
        if ($titlecontext === 'activity') {
            $activitytitlemode = (string)get_config('local_course_banner_builder', 'bannertitle_activity_activitytitlemode');
            $activitytitlemode = in_array($activitytitlemode, ['course', 'both', 'none'], true)
                ? $activitytitlemode
                : 'activity';
            if ($activitytitlemode === 'course') {
                $titletext = $coursetitle;
            } else if ($activitytitlemode === 'both') {
                $titletext = trim($coursetitle . "\n" . $activitytitle);
            } else if ($activitytitlemode === 'none') {
                $titletext = '';
            } else {
                $titletext = $activitytitle;
            }
        }

        return [
            'courseid' => $courseid,
            'bannerurl' => $bannerurl ? $bannerurl->out(false) : '',
            'overlays' => $overlays,
            'border' => $border,
            'title' => self::get_banner_title_overlay($titlecontext, $titletext),
            'themehasbanner' => $themehasbanner,
            'allownativebanner' => $allownativebanner,
            'hascourselayers' => $hascourselayers,
            'usesdefaultimage' => $usedefaultimage,
            'bannerformat' => manager::get_course_banner_format(),
        ];
    }

    /**
     * Whether the current page is the canonical course view page.
     *
     * @param \moodle_page $page
     * @return bool
     */
    protected static function is_course_view_page(\moodle_page $page): bool {
        $path = $page->url ? $page->url->get_path() : '';
        return $path === '/course/view.php' && !empty($page->course->id) && (int)$page->course->id > SITEID;
    }

    /**
     * Whether the current page may display a course banner.
     *
     * @param \moodle_page $page
     * @return bool
     */
    protected static function is_course_banner_page(\moodle_page $page): bool {
        if (self::is_course_view_page($page)) {
            return true;
        }

        $path = $page->url ? $page->url->get_path() : '';
        $courseid = (int)($page->course->id ?? 0);
        if (!manager::course_banners_on_activity_pages_enabled() || $courseid <= SITEID) {
            return false;
        }
        if (in_array($page->pagelayout, ['admin', 'maintenance', 'popup', 'embedded', 'print', 'redirect'], true)) {
            return false;
        }
        if ($path === '' || $path === '/course/view.php' || str_starts_with($path, '/admin/') ||
            str_starts_with($path, '/login/') || $path === '/pluginfile.php' ||
            str_starts_with($path, '/local/course_banner_builder/')) {
            return false;
        }

        return true;
    }

    /**
     * Whether the site-wide banner is allowed on the current page.
     *
     * @param \moodle_page $page
     * @return bool
     */
    protected static function is_site_banner_page(\moodle_page $page): bool {
        $path = $page->url ? $page->url->get_path() : '';
        $courseid = (int)($page->course->id ?? 0);
        if (!isloggedin() || isguestuser()) {
            return false;
        }
        if ($courseid > SITEID) {
            return false;
        }
        if (in_array($page->pagelayout, ['admin', 'maintenance', 'popup', 'embedded', 'print', 'redirect'], true)) {
            return false;
        }
        if ($path === '' || str_starts_with($path, '/admin/')) {
            return false;
        }
        if (str_starts_with($path, '/login/') || $path === '/pluginfile.php') {
            return false;
        }
        if (str_starts_with($path, '/local/course_banner_builder/')) {
            return false;
        }

        return true;
    }

    /**
     * Small runtime stylesheet for native course-banner rendering.
     *
     * This is injected from a footer hook, so it must stay inline instead of
     * using $PAGE->requires->css(), which is only valid before <head> is sent.
     *
     * @return string
     */
    protected static function get_course_banner_runtime_css(): string {
        return implode("\n", [
            '.local-course-banner-builder-native-shell {',
            '    margin: calc(var(--navbar-height, 60px) + 0.75rem) auto 0.75rem;',
            '}',
            '.local-course-banner-builder-native-shell--mounted {',
            '    margin: 0;',
            '}',
            'html.local-course-banner-builder-fullwidth-banner-mounted,',
            'body.local-course-banner-builder-fullwidth-banner-mounted {',
            '    overflow-x: hidden !important;',
            '}',
            'html.local-course-banner-builder-fullwidth-banner-mounted {',
            '    scrollbar-gutter: stable;',
            '}',
            '#page.local-course-banner-builder-fullwidth-scroll-frame {',
            '    scrollbar-gutter: stable;',
            '    overflow-x: hidden !important;',
            '}',
            '.local-course-banner-builder-native-shell--format-fullwidthtop {',
            '    width: 100% !important;',
            '    max-width: none !important;',
            '    margin-top: 0 !important;',
            '    margin-left: 0 !important;',
            '    margin-right: 0 !important;',
            '    padding-left: 0 !important;',
            '    padding-right: 0 !important;',
            '    box-sizing: border-box !important;',
            '}',
            '.local-course-banner-builder-native-shell--format-fullwidthtopcompact {',
            '    width: 100% !important;',
            '    max-width: none !important;',
            '    margin-top: 0 !important;',
            '    margin-left: 0 !important;',
            '    margin-right: 0 !important;',
            '    padding-left: 0 !important;',
            '    padding-right: 0 !important;',
            '    box-sizing: border-box !important;',
            '}',
            '.local-course-banner-builder-native-shell--format-contentwide {',
            '    width: min(100%, 1500px) !important;',
            '    max-width: 1500px !important;',
            '    margin-right: auto !important;',
            '    margin-left: auto !important;',
            '    box-sizing: border-box !important;',
            '}',
            '.local-course-banner-builder-native-shell--format-fullwidthtop.local-course-banner-builder-native-shell--mounted {',
            '    margin-top: var(--local-course-banner-builder-fullwidth-nav-offset, 0);',
            '    margin-bottom: 0.75rem;',
            '}',
            '.local-course-banner-builder-native-shell--format-fullwidthtopcompact.local-course-banner-builder-native-shell--mounted {',
            '    margin-top: var(--local-course-banner-builder-fullwidth-nav-offset, 0);',
            '    margin-bottom: 0.75rem;',
            '}',
            '.local-course-banner-builder {',
            '    position: relative;',
            '    width: 100%;',
            '    min-height: 180px;',
            '    overflow: hidden;',
            '}',
            '.local-course-banner-builder-banner-overlay-layer {',
            '    position: absolute;',
            '    inset: 0;',
            '    z-index: 70;',
            '    pointer-events: none;',
            '}',
            '.local-course-banner-builder-banner-title-overlay {',
            '    position: absolute;',
            '    z-index: 80;',
            '    max-width: min(86%, 54rem);',
            '    transform: translate(-50%, -50%);',
            '    font-weight: 800;',
            '    line-height: 1.05;',
            '    letter-spacing: 0;',
            '    white-space: pre-line;',
            '    text-shadow: 0 0.15rem 0.7rem rgba(0, 0, 0, 0.45);',
            '    pointer-events: none;',
            '}',
            '.local-course-banner-builder-slideshow-host {',
            '    position: relative !important;',
            '    overflow: hidden !important;',
            '    container-type: size;',
            '}',
            '.local-course-banner-builder-slideshow {',
            '    position: absolute;',
            '    inset: 0;',
            '    z-index: 90;',
            '    display: flex;',
            '    align-items: center;',
            '    justify-content: center;',
            '    color: #fff;',
            '    pointer-events: none;',
            '    isolation: isolate;',
            '}',
            '.local-course-banner-builder-slideshow::before {',
            '    position: absolute;',
            '    inset: 0;',
            '    z-index: -2;',
            '    background: rgba(var(--local-course-banner-builder-slideshow-overlay-rgb, 0, 0, 0), var(--local-course-banner-builder-slideshow-overlay-opacity, 0.38));',
            '    -webkit-backdrop-filter: blur(10px) saturate(1.15);',
            '    backdrop-filter: blur(10px) saturate(1.15);',
            '    opacity: 1;',
            '    transition: opacity 360ms ease;',
            '    content: "";',
            '}',
            '.local-course-banner-builder-slideshow::after {',
            '    position: absolute;',
            '    inset: 0;',
            '    z-index: -1;',
            '    background: linear-gradient(90deg, ' .
                'rgba(var(--local-course-banner-builder-slideshow-overlay-rgb, 0, 0, 0), ' .
                    'var(--local-course-banner-builder-slideshow-overlay-opacity, 0.38)), ' .
                'rgba(var(--local-course-banner-builder-slideshow-overlay-rgb, 0, 0, 0), 0.12), ' .
                'rgba(var(--local-course-banner-builder-slideshow-overlay-rgb, 0, 0, 0), ' .
                    'var(--local-course-banner-builder-slideshow-overlay-opacity, 0.38)));',
            '    opacity: 1;',
            '    transition: opacity 360ms ease;',
            '    content: "";',
            '}',
            '.local-course-banner-builder-slideshow.is-empty-active::before,',
            '.local-course-banner-builder-slideshow.is-empty-active::after {',
            '    opacity: 0;',
            '}',
            '.local-course-banner-builder-slideshow-slide {',
            '    position: absolute;',
            '    inset: 0;',
            '    display: flex;',
            '    flex-direction: column;',
            '    justify-content: center;',
            '    gap: clamp(0.28rem, 1.2cqh, 0.7rem);',
            '    width: 100%;',
            '    max-width: none;',
            '    padding: clamp(1rem, 6cqh, 2.8rem) clamp(2.2rem, 6vw, 6rem) clamp(2.35rem, 7cqh, 3.8rem);',
            '    overflow: hidden;',
            '    opacity: 0;',
            '    transform: translate3d(1.25rem, 0, 0) scale(0.985);',
            '    transition: opacity 420ms ease, transform 520ms cubic-bezier(0.22, 1, 0.36, 1);',
            '    pointer-events: none;',
            '}',
            '.local-course-banner-builder-slideshow-slide.is-active {',
            '    opacity: 1;',
            '    transform: translate3d(0, 0, 0) scale(1);',
            '    pointer-events: none;',
            '}',
            '.local-course-banner-builder-slideshow-slide--empty {',
            '    pointer-events: none;',
            '}',
            '.local-course-banner-builder-slideshow-labels {',
            '    position: absolute;',
            '    top: var(--local-course-banner-builder-slideshow-label-y, 10%);',
            '    left: var(--local-course-banner-builder-slideshow-label-x, 14%);',
            '    transform: translate(var(--local-course-banner-builder-slideshow-label-translate-x, -50%), -50%);',
            '    display: flex;',
            '    align-items: center;',
            '    flex-direction: var(--local-course-banner-builder-slideshow-label-orientation, row);',
            '    flex-wrap: nowrap;',
            '    gap: clamp(0.35rem, 1.2cqh, 0.7rem);',
            '    width: max-content;',
            '    max-width: calc(100% - 1rem);',
            '    white-space: nowrap;',
            '}',
            '.local-course-banner-builder-slideshow-label {',
            '    display: inline-flex;',
            '    align-items: center;',
            '    justify-content: center;',
            '    flex: 0 0 auto;',
            '    gap: 0.24em;',
            '    align-self: auto;',
            '    padding: clamp(0.14rem, 0.72cqh, 0.28rem) clamp(0.42rem, 1.35cqh, 0.72rem);',
            '    border: 1px solid rgba(255, 255, 255, 0.42);',
            '    border-radius: var(--local-course-banner-builder-slideshow-label-radius, 999px);',
            '    background: rgba(255, 255, 255, 0.2);',
            '    color: #fff;',
            '    font-size: var(--local-course-banner-builder-slideshow-label-font-size, clamp(3.5cqh, min(6.4cqh, 0.82cqw), 8.4cqh));',
            '    font-weight: 600;',
            '    letter-spacing: 0;',
            '    line-height: 1;',
            '    min-height: calc(1em + clamp(0.28rem, 1.42cqh, 0.56rem));',
            '    text-transform: var(--local-course-banner-builder-slideshow-label-text-transform, uppercase);',
            '    white-space: nowrap;',
            '}',
            '.local-course-banner-builder-slideshow-label > span { display: inline-flex; ' .
                'align-items: center; line-height: 1; ' .
                'transform: scale(var(--local-course-banner-builder-slideshow-label-text-scale, 1)); ' .
                'transform-origin: center; }',
            '.local-course-banner-builder-slideshow-label .fa, ' .
                '.local-course-banner-builder-slideshow-label .icon, ' .
                '.local-course-banner-builder-slideshow-label-icon { margin: 0; ' .
                'font-size: 1.08em; line-height: 1; }',
            '.local-course-banner-builder-slideshow-label-icon { width: 1.16em; height: 1.16em; ' .
                'object-fit: contain; flex: 0 0 auto; }',
            '.local-course-banner-builder-slideshow-label--course-shortname { ' .
                '--local-course-banner-builder-slideshow-label-shadow-rgb: ' .
                'var(--local-course-banner-builder-slideshow-label-courseorigin-shadow-rgb, 0, 0, 0); ' .
                'background: var(--local-course-banner-builder-slideshow-label-courseorigin-bg, rgba(255, 255, 255, 0.88)); ' .
                'border-color: var(--local-course-banner-builder-slideshow-label-courseorigin-border, #E5E7EB); ' .
                'color: var(--local-course-banner-builder-slideshow-label-courseorigin-color, #111827); ' .
                'min-height: calc(1.1em + clamp(0.45rem, min(2.2cqh, 0.28cqw), 0.74rem)); }',
            '.local-course-banner-builder-slideshow-label--forums { ' .
                '--local-course-banner-builder-slideshow-label-shadow-rgb: ' .
                'var(--local-course-banner-builder-slideshow-label-forums-shadow-rgb, 0, 0, 0); ' .
                'background: var(--local-course-banner-builder-slideshow-label-forums-bg, #0DCAF0); ' .
                'border-color: var(--local-course-banner-builder-slideshow-label-forums-border, #B6F0FF); ' .
                'color: var(--local-course-banner-builder-slideshow-label-forums-color, #07212A); }',
            '.local-course-banner-builder-slideshow-label--siteannouncements { ' .
                '--local-course-banner-builder-slideshow-label-shadow-rgb: ' .
                'var(--local-course-banner-builder-slideshow-label-siteannouncements-shadow-rgb, 0, 0, 0); ' .
                'background: var(--local-course-banner-builder-slideshow-label-siteannouncements-bg, #20C997); ' .
                'border-color: var(--local-course-banner-builder-slideshow-label-siteannouncements-border, #B5F4DF); ' .
                'color: var(--local-course-banner-builder-slideshow-label-siteannouncements-color, #06281F); }',
            '.local-course-banner-builder-slideshow-label--assignment { ' .
                '--local-course-banner-builder-slideshow-label-shadow-rgb: ' .
                'var(--local-course-banner-builder-slideshow-label-assignments-shadow-rgb, 0, 0, 0); ' .
                'background: var(--local-course-banner-builder-slideshow-label-assignments-bg, #FFC107); ' .
                'border-color: var(--local-course-banner-builder-slideshow-label-assignments-border, #FFE69C); ' .
                'color: var(--local-course-banner-builder-slideshow-label-assignments-color, #2B2100); }',
            '.local-course-banner-builder-slideshow-label--assignments { ' .
                '--local-course-banner-builder-slideshow-label-shadow-rgb: ' .
                'var(--local-course-banner-builder-slideshow-label-assignments-shadow-rgb, 0, 0, 0); ' .
                'background: var(--local-course-banner-builder-slideshow-label-assignments-bg, #FFC107); ' .
                'border-color: var(--local-course-banner-builder-slideshow-label-assignments-border, #FFE69C); ' .
                'color: var(--local-course-banner-builder-slideshow-label-assignments-color, #2B2100); }',
            '.local-course-banner-builder-slideshow-label--quiz { ' .
                '--local-course-banner-builder-slideshow-label-shadow-rgb: ' .
                'var(--local-course-banner-builder-slideshow-label-quizzes-shadow-rgb, 0, 0, 0); ' .
                'background: var(--local-course-banner-builder-slideshow-label-quizzes-bg, #DC3545); ' .
                'border-color: var(--local-course-banner-builder-slideshow-label-quizzes-border, #F1AEB5); ' .
                'color: var(--local-course-banner-builder-slideshow-label-quizzes-color, #FFFFFF); }',
            '.local-course-banner-builder-slideshow-label--quizzes { ' .
                '--local-course-banner-builder-slideshow-label-shadow-rgb: ' .
                'var(--local-course-banner-builder-slideshow-label-quizzes-shadow-rgb, 0, 0, 0); ' .
                'background: var(--local-course-banner-builder-slideshow-label-quizzes-bg, #DC3545); ' .
                'border-color: var(--local-course-banner-builder-slideshow-label-quizzes-border, #F1AEB5); ' .
                'color: var(--local-course-banner-builder-slideshow-label-quizzes-color, #FFFFFF); }',
            '.local-course-banner-builder-slideshow-content {',
            '    pointer-events: none;',
            '}',
            '.local-course-banner-builder-slideshow-title-block,',
            '.local-course-banner-builder-slideshow-body-block,',
            '.local-course-banner-builder-slideshow-action-wrap {',
            '    position: absolute;',
            '    text-align: center;',
            '    pointer-events: none;',
            '    transform: translate(-50%, -50%);',
            '    display: inline-flex;',
            '    flex-direction: column;',
            '    align-items: center;',
            '    justify-content: center;',
            '    width: max-content;',
            '}',
            '.local-course-banner-builder-slideshow-title-block {',
            '    left: var(--local-course-banner-builder-slideshow-title-x, 50%);',
            '    top: var(--local-course-banner-builder-slideshow-title-y, 32%);',
            '    max-width: min(84%, 72rem);',
            '    text-align: var(--local-course-banner-builder-slideshow-title-text-align, center);',
            '}',
            '.local-course-banner-builder-slideshow-body-block {',
            '    left: var(--local-course-banner-builder-slideshow-body-x, 50%);',
            '    top: var(--local-course-banner-builder-slideshow-body-y, 43%);',
            '    width: min(86%, 72rem);',
            '    max-width: min(86%, 72rem);',
            '    max-height: max(2rem, calc(100% - var(--local-course-banner-builder-slideshow-body-y, 43%) - 18%));',
            '    transform: translate(-50%, 0);',
            '    overflow: hidden;',
            '    text-align: var(--local-course-banner-builder-slideshow-body-text-align, center);',
            '}',
            '.local-course-banner-builder-slideshow-action-wrap {',
            '    left: var(--local-course-banner-builder-slideshow-action-x, 50%);',
            '    top: calc(var(--local-course-banner-builder-slideshow-action-y, 80%) - var(--local-course-banner-builder-slideshow-action-y-adjust, 0%));',
            '    max-width: min(96%, 42rem);',
            '}',
            '.local-course-banner-builder-native-course-banner--format-standard .local-course-banner-builder-slideshow,',
            '.local-course-banner-builder-site-banner.local-course-banner-builder-native-course-banner--format-standard ' .
                '.local-course-banner-builder-slideshow { --local-course-banner-builder-slideshow-action-y-adjust: 3%; }',
            '.local-course-banner-builder-native-course-banner--format-fullwidthtopcompact .local-course-banner-builder-slideshow,',
            '.local-course-banner-builder-site-banner.local-course-banner-builder-native-course-banner--format-fullwidthtopcompact ' .
                '.local-course-banner-builder-slideshow { --local-course-banner-builder-slideshow-action-y-adjust: 4%; }',
            '.local-course-banner-builder-slideshow-title {',
            '    margin: 0 auto;',
            '    max-width: min(100%, 72rem);',
            '    color: var(--local-course-banner-builder-slideshow-title-color, #fff);',
            '    font-size: var(--local-course-banner-builder-slideshow-title-font-size, clamp(10cqh, min(28cqh, 3.4cqw), 36cqh));',
            '    font-family: var(--local-course-banner-builder-slideshow-title-font-family, inherit);',
            '    font-weight: var(--local-course-banner-builder-slideshow-title-font-weight, 800);',
            '    font-style: var(--local-course-banner-builder-slideshow-title-font-style, normal);',
            '    text-decoration: var(--local-course-banner-builder-slideshow-title-text-decoration, none);',
            '    text-transform: var(--local-course-banner-builder-slideshow-title-text-transform, none);',
            '    line-height: 1.08;',
            '    overflow-wrap: anywhere;',
            '}',
            '.local-course-banner-builder-slideshow-meta,',
            '.local-course-banner-builder-slideshow-body {',
            '    margin: 0 auto;',
            '    max-width: min(100%, 64rem);',
            '    color: var(--local-course-banner-builder-slideshow-body-color, rgba(255, 255, 255, 0.88));',
            '    font-size: var(--local-course-banner-builder-slideshow-body-font-size, clamp(5.5cqh, min(14cqh, 1.7cqw), 19cqh));',
            '    font-family: var(--local-course-banner-builder-slideshow-body-font-family, inherit);',
            '    font-weight: var(--local-course-banner-builder-slideshow-body-font-weight, 400);',
            '    font-style: var(--local-course-banner-builder-slideshow-body-font-style, normal);',
            '    text-decoration: var(--local-course-banner-builder-slideshow-body-text-decoration, none);',
            '    text-transform: var(--local-course-banner-builder-slideshow-body-text-transform, none);',
            '    line-height: 1.35;',
            '    overflow-wrap: anywhere;',
            '}',
            '.local-course-banner-builder-slideshow-body {',
            '    display: -webkit-box;',
            '    overflow: hidden;',
            '    -webkit-box-orient: vertical;',
            '    -webkit-line-clamp: 2;',
            '}',
            '.local-course-banner-builder-slideshow-action {',
            '    display: inline-flex;',
            '    align-items: center;',
            '    justify-content: center;',
            '    min-width: var(--local-course-banner-builder-slideshow-action-width, clamp(10cqw, 18cqw, 34cqw));',
            '    height: var(--local-course-banner-builder-slideshow-action-height, clamp(10cqh, min(22cqh, 2.7cqw), 34cqh));',
            '    min-height: var(--local-course-banner-builder-slideshow-action-height, clamp(10cqh, min(22cqh, 2.7cqw), 34cqh));',
            '    padding: clamp(0.28rem, 1.15cqh, 0.55rem) clamp(0.8rem, 2.4cqh, 1.2rem);',
            '    font-size: var(--local-course-banner-builder-slideshow-action-font-size, clamp(6cqh, min(13cqh, 1.6cqw), 18cqh));',
            '    line-height: 1.1;',
            '    text-align: center;',
            '    pointer-events: auto;',
            '    transition: background-color 160ms ease, border-color 160ms ease, box-shadow 160ms ease, transform 160ms ease;',
            '}',
            '.local-course-banner-builder-slideshow-action.btn {',
            '    border-color: #fff;',
            '    background: #fff;',
            '    color: #111827;',
            '    font-size: var(--local-course-banner-builder-slideshow-action-font-size, clamp(6cqh, min(13cqh, 1.6cqw), 18cqh)) !important;',
            '    min-width: var(--local-course-banner-builder-slideshow-action-width, clamp(10cqw, 18cqw, 34cqw)) !important;',
            '    height: var(--local-course-banner-builder-slideshow-action-height, clamp(10cqh, min(22cqh, 2.7cqw), 34cqh)) !important;',
            '    min-height: var(--local-course-banner-builder-slideshow-action-height, clamp(10cqh, min(22cqh, 2.7cqw), 34cqh)) !important;',
            '    font-weight: 700;',
            '    text-transform: var(--local-course-banner-builder-slideshow-action-text-transform, none);',
            '    border-radius: var(--local-course-banner-builder-slideshow-action-radius, var(--bs-border-radius, 0.375rem));',
            '    box-shadow: 0 0.35rem 1rem rgba(0, 0, 0, 0.28);',
            '}',
            '.local-course-banner-builder-slideshow-action.btn:hover,',
            '.local-course-banner-builder-slideshow-action.btn:focus {',
            '    border-color: #fff;',
            '    background: rgba(255, 255, 255, 0.92);',
            '    color: #000;',
            '    box-shadow: 0 0.5rem 1.25rem rgba(0, 0, 0, 0.32);',
            '    transform: translateY(-1px);',
            '}',
            '.local-course-banner-builder-slideshow-action.btn:focus-visible {',
            '    outline: 0;',
            '    box-shadow: 0 0 0 0.18rem rgba(255, 255, 255, 0.38), 0 0 0 0.34rem rgba(15, 108, 191, 0.55), 0 0.5rem 1.25rem rgba(0, 0, 0, 0.32);',
            '}',
            '.local-course-banner-builder-slideshow-action.btn:active {',
            '    background: rgba(255, 255, 255, 0.82);',
            '    box-shadow: 0 0.18rem 0.55rem rgba(0, 0, 0, 0.3);',
            '    transform: translateY(1px);',
            '}',
            '.local-course-banner-builder-slideshow-nav {',
            '    position: absolute;',
            '    top: 50%;',
            '    z-index: 2;',
            '    display: inline-flex;',
            '    align-items: center;',
            '    justify-content: center;',
            '    width: clamp(2rem, 6.2cqh, 3.35rem);',
            '    height: clamp(2rem, 6.2cqh, 3.35rem);',
            '    padding: 0;',
            '    border: 1px solid rgba(255, 255, 255, 0.35);',
            '    border-radius: 999px;',
            '    background: rgba(0, 0, 0, 0.28);',
            '    color: #fff;',
            '    transform: translateY(-50%);',
            '    pointer-events: auto;',
            '    transition: background-color 160ms ease, transform 160ms ease;',
            '}',
            '.local-course-banner-builder-slideshow-nav .icon,',
            '.local-course-banner-builder-slideshow-nav .fa {',
            '    width: auto;',
            '    height: auto;',
            '    margin: 0;',
            '    line-height: 1;',
            '}',
            '.local-course-banner-builder-slideshow-nav:hover,',
            '.local-course-banner-builder-slideshow-nav:focus {',
            '    background: rgba(0, 0, 0, 0.46);',
            '    color: #fff;',
            '    transform: translateY(-50%) scale(1.04);',
            '}',
            '.local-course-banner-builder-slideshow-nav--prev { left: clamp(0.6rem, 1.8vw, 1.5rem); }',
            '.local-course-banner-builder-slideshow-nav--next { right: clamp(0.6rem, 1.8vw, 1.5rem); }',
            '.local-course-banner-builder-slideshow-dots {',
            '    position: absolute;',
            '    right: 0;',
            '    bottom: clamp(0.32rem, 1.35cqh, 0.82rem);',
            '    left: 0;',
            '    z-index: 2;',
            '    display: flex;',
            '    justify-content: center;',
            '    gap: 0.35rem;',
            '    pointer-events: auto;',
            '}',
            '.local-course-banner-builder-slideshow-dot {',
            '    width: clamp(0.55rem, 2cqh, 0.82rem);',
            '    height: clamp(0.55rem, 2cqh, 0.82rem);',
            '    border: 1px solid rgba(255, 255, 255, 0.72);',
            '    border-radius: 999px;',
            '    background: rgba(255, 255, 255, 0.2);',
            '    padding: 0;',
            '}',
            '.local-course-banner-builder-slideshow-dot.is-active {',
            '    background: #fff;',
            '}',
            '.local-course-banner-builder-slideshow.is-empty-active .local-course-banner-builder-slideshow-dot {',
            '    border-color: rgba(0, 0, 0, 0.38);',
            '    background: rgba(0, 0, 0, 0.16);',
            '}',
            '.local-course-banner-builder-slideshow.is-empty-active .local-course-banner-builder-slideshow-dot.is-active {',
            '    background: rgba(0, 0, 0, 0.62);',
            '}',
            '.drawer-toggler,',
            '.drawer-left-toggle,',
            '.drawer-right-toggle,',
            '[data-region="drawer-toggle"] {',
            '    position: relative;',
            '    z-index: 1200 !important;',
            '}',
            '.local-course-banner-builder .banner-layer {',
            '    position: absolute;',
            '    inset: 0;',
            '    display: block;',
            '    width: 100%;',
            '    height: 100%;',
            '    object-fit: cover;',
            '}',
            '.local-course-banner-builder .banner-layer-1 {',
            '    z-index: 1;',
            '}',
            '.local-course-banner-builder-native-course-banner--moodle-generated {',
            '    background-repeat: no-repeat;',
            '    background-position: center center;',
            '    background-size: cover;',
            '}',
            '.local-course-banner-builder-native-course-banner {',
            '    width: min(100%, 1320px);',
            '    aspect-ratio: 4 / 1;',
            '    container-type: size;',
            '    min-height: 0;',
            '    margin: 1rem auto 1.5rem;',
            '    border: 1px solid var(--bs-border-color, #dee2e6);',
            '    border-radius: var(--bs-border-radius-lg, 0.5rem);',
            '    background-color: var(--bs-body-bg, #fff);',
            '    background-position: center center;',
            '    background-repeat: no-repeat;',
            '    background-size: cover;',
            '    box-shadow: none;',
            '}',
            '.local-course-banner-builder-native-course-banner--format-contentwide {',
            '    width: 100% !important;',
            '    max-width: 1500px !important;',
            '    aspect-ratio: 5 / 1;',
            '    min-height: 135px;',
            '    max-height: 280px;',
            '}',
            '.local-course-banner-builder-native-course-banner--format-fullwidthtop {',
            '    width: 100% !important;',
            '    max-width: none !important;',
            '    box-sizing: border-box;',
            '    aspect-ratio: 5 / 1;',
            '    min-height: 180px;',
            '    max-height: 360px;',
            '    margin-top: 0;',
            '    border-radius: 0;',
            '}',
            '.local-course-banner-builder-native-course-banner--format-fullwidthtopcompact {',
            '    width: 100% !important;',
            '    max-width: none !important;',
            '    box-sizing: border-box;',
            '    aspect-ratio: 8 / 1;',
            '    min-height: 110px;',
            '    max-height: 210px;',
            '    margin-top: 0;',
            '    border-radius: 0;',
            '}',
            '.local-course-banner-builder-overlay-host {',
            '    position: relative;',
            '    overflow: hidden;',
            '}',
            '.local-course-banner-builder-fixed-overlays {',
            '    position: absolute;',
            '    inset: 0;',
            '    z-index: 1;',
            '    overflow: hidden;',
            '    pointer-events: none;',
            '}',
            '.local-course-banner-builder-fixed-overlay-image {',
            '    position: absolute;',
            '    inset: 0;',
            '    display: block;',
            '    max-width: none;',
            '    object-fit: contain;',
            '    pointer-events: none;',
            '}',
            '.local-course-banner-builder-fixed-border {',
            '    position: absolute;',
            '    inset: 0;',
            '    pointer-events: none;',
            '}',
            '.local-course-banner-builder-fixed-border-side,',
            '.local-course-banner-builder-fixed-border-corner {',
            '    position: absolute;',
            '    pointer-events: none;',
            '    background-repeat: no-repeat;',
            '    background-size: 100% 100%;',
            '}',
            '.local-course-banner-builder-fixed-border-hole {',
            '    position: absolute;',
            '    z-index: 4;',
            '    background: transparent;',
            '    top: var(--local-course-banner-builder-preview-top-width, 0);',
            '    right: var(--local-course-banner-builder-preview-right-width, 0);',
            '    bottom: var(--local-course-banner-builder-preview-bottom-width, 0);',
            '    left: var(--local-course-banner-builder-preview-left-width, 0);',
            '    border-top-left-radius: var(--local-course-banner-builder-preview-top-left-radius, 0);',
            '    border-top-right-radius: var(--local-course-banner-builder-preview-top-right-radius, 0);',
            '    border-bottom-right-radius: var(--local-course-banner-builder-preview-bottom-right-radius, 0);',
            '    border-bottom-left-radius: var(--local-course-banner-builder-preview-bottom-left-radius, 0);',
            '}',
            '.local-course-banner-builder-fixed-border-side-top {',
            '    top: 0;',
            '    right: var(--local-course-banner-builder-preview-top-right-offset, 0);',
            '    left: var(--local-course-banner-builder-preview-top-left-offset, 0);',
            '    height: var(--local-course-banner-builder-preview-top-width, 0);',
            '    z-index: 2;',
            '}',
            '.local-course-banner-builder-fixed-border-side-right {',
            '    top: var(--local-course-banner-builder-preview-right-top-offset, 0);',
            '    right: 0;',
            '    bottom: var(--local-course-banner-builder-preview-right-bottom-offset, 0);',
            '    left: auto;',
            '    width: var(--local-course-banner-builder-preview-right-width, 0);',
            '    z-index: 1;',
            '}',
            '.local-course-banner-builder-fixed-border-side-bottom {',
            '    top: auto;',
            '    right: var(--local-course-banner-builder-preview-bottom-right-offset, 0);',
            '    bottom: 0;',
            '    left: var(--local-course-banner-builder-preview-bottom-left-offset, 0);',
            '    height: var(--local-course-banner-builder-preview-bottom-width, 0);',
            '    z-index: 2;',
            '}',
            '.local-course-banner-builder-fixed-border-side-left {',
            '    top: var(--local-course-banner-builder-preview-left-top-offset, 0);',
            '    right: auto;',
            '    bottom: var(--local-course-banner-builder-preview-left-bottom-offset, 0);',
            '    left: 0;',
            '    width: var(--local-course-banner-builder-preview-left-width, 0);',
            '    z-index: 1;',
            '}',
            '.local-course-banner-builder-fixed-border-corner-top-left {',
            '    top: 0;',
            '    left: 0;',
            '    width: var(--local-course-banner-builder-preview-top-left-corner-size, var(--local-course-banner-builder-preview-left-width, 0));',
            '    height: var(--local-course-banner-builder-preview-top-left-corner-size, var(--local-course-banner-builder-preview-top-width, 0));',
            '    background-image: radial-gradient(circle at 100% 100%, ' .
                'rgba(0, 0, 0, 0) var(--local-course-banner-builder-preview-top-left-radius, 0), ' .
                'var(--local-course-banner-builder-preview-color-transparent, transparent) ' .
                    'var(--local-course-banner-builder-preview-top-left-radius, 0), ' .
                'var(--local-course-banner-builder-preview-color-solid, transparent) ' .
                    'var(--local-course-banner-builder-preview-top-left-fade-start, ' .
                    'var(--local-course-banner-builder-preview-top-left-corner-size, 100%)), ' .
                'var(--local-course-banner-builder-preview-color-solid, transparent) ' .
                    'var(--local-course-banner-builder-preview-top-left-corner-size, 100%));',
            '}',
            '.local-course-banner-builder-fixed-border-corner-top-right {',
            '    right: 0;',
            '    left: auto;',
            '    width: var(--local-course-banner-builder-preview-top-right-corner-size, var(--local-course-banner-builder-preview-right-width, 0));',
            '    height: var(--local-course-banner-builder-preview-top-right-corner-size, var(--local-course-banner-builder-preview-top-width, 0));',
            '    background-image: radial-gradient(circle at 0 100%, ' .
                'rgba(0, 0, 0, 0) var(--local-course-banner-builder-preview-top-right-radius, 0), ' .
                'var(--local-course-banner-builder-preview-color-transparent, transparent) ' .
                    'var(--local-course-banner-builder-preview-top-right-radius, 0), ' .
                'var(--local-course-banner-builder-preview-color-solid, transparent) ' .
                    'var(--local-course-banner-builder-preview-top-right-fade-start, ' .
                    'var(--local-course-banner-builder-preview-top-right-corner-size, 100%)), ' .
                'var(--local-course-banner-builder-preview-color-solid, transparent) ' .
                    'var(--local-course-banner-builder-preview-top-right-corner-size, 100%));',
            '}',
            '.local-course-banner-builder-fixed-border-corner-bottom-right {',
            '    top: auto;',
            '    right: 0;',
            '    bottom: 0;',
            '    left: auto;',
            '    width: var(--local-course-banner-builder-preview-bottom-right-corner-size, var(--local-course-banner-builder-preview-right-width, 0));',
            '    height: var(--local-course-banner-builder-preview-bottom-right-corner-size, var(--local-course-banner-builder-preview-bottom-width, 0));',
            '    background-image: radial-gradient(circle at 0 0, ' .
                'rgba(0, 0, 0, 0) var(--local-course-banner-builder-preview-bottom-right-radius, 0), ' .
                'var(--local-course-banner-builder-preview-color-transparent, transparent) ' .
                    'var(--local-course-banner-builder-preview-bottom-right-radius, 0), ' .
                'var(--local-course-banner-builder-preview-color-solid, transparent) ' .
                    'var(--local-course-banner-builder-preview-bottom-right-fade-start, ' .
                    'var(--local-course-banner-builder-preview-bottom-right-corner-size, 100%)), ' .
                'var(--local-course-banner-builder-preview-color-solid, transparent) ' .
                    'var(--local-course-banner-builder-preview-bottom-right-corner-size, 100%));',
            '}',
            '.local-course-banner-builder-fixed-border-corner-bottom-left {',
            '    top: auto;',
            '    bottom: 0;',
            '    width: var(--local-course-banner-builder-preview-bottom-left-corner-size, var(--local-course-banner-builder-preview-left-width, 0));',
            '    height: var(--local-course-banner-builder-preview-bottom-left-corner-size, var(--local-course-banner-builder-preview-bottom-width, 0));',
            '    background-image: radial-gradient(circle at 100% 0, ' .
                'rgba(0, 0, 0, 0) var(--local-course-banner-builder-preview-bottom-left-radius, 0), ' .
                'var(--local-course-banner-builder-preview-color-transparent, transparent) ' .
                    'var(--local-course-banner-builder-preview-bottom-left-radius, 0), ' .
                'var(--local-course-banner-builder-preview-color-solid, transparent) ' .
                    'var(--local-course-banner-builder-preview-bottom-left-fade-start, ' .
                    'var(--local-course-banner-builder-preview-bottom-left-corner-size, 100%)), ' .
                'var(--local-course-banner-builder-preview-color-solid, transparent) ' .
                    'var(--local-course-banner-builder-preview-bottom-left-corner-size, 100%));',
            '}',
        ]);
    }

    /**
     * Render one static border overlay.
     *
     * @param array $border
     * @return string
     */
    protected static function render_native_border_html(array $border): string {
        $wrapperstyle = trim((string)($border['wrapperstyle'] ?? $border['boxstyle'] ?? $border['style'] ?? ''));
        if ($wrapperstyle !== '' && !str_ends_with($wrapperstyle, ';')) {
            $wrapperstyle .= ';';
        }
        $wrapperstyle .= ' z-index: 20;';

        $out = \html_writer::start_div('local-course-banner-builder-fixed-border', [
            'aria-hidden' => 'true',
            'style' => $wrapperstyle,
        ]);

        foreach ([
            'top' => 'local-course-banner-builder-fixed-border-side local-course-banner-builder-fixed-border-side-top',
            'right' => 'local-course-banner-builder-fixed-border-side local-course-banner-builder-fixed-border-side-right',
            'bottom' => 'local-course-banner-builder-fixed-border-side local-course-banner-builder-fixed-border-side-bottom',
            'left' => 'local-course-banner-builder-fixed-border-side local-course-banner-builder-fixed-border-side-left',
        ] as $side => $class) {
            $out .= \html_writer::div('', $class, [
                'aria-hidden' => 'true',
                'style' => (string)(($border['sidestyles'][$side] ?? '')),
            ]);
        }

        foreach (['top-left', 'top-right', 'bottom-right', 'bottom-left'] as $corner) {
            $out .= \html_writer::div('', 'local-course-banner-builder-fixed-border-corner local-course-banner-builder-fixed-border-corner-' . $corner, [
                'aria-hidden' => 'true',
            ]);
        }

        $out .= \html_writer::div('', 'local-course-banner-builder-fixed-border-hole', ['aria-hidden' => 'true']);
        $out .= \html_writer::end_div();
        return $out;
    }

    /**
     * Build one optional title overlay for native banners.
     *
     * @param string $context
     * @param string $text
     * @return array|null
     */
    protected static function get_banner_title_overlay(string $context, string $text): ?array {
        $context = in_array($context, ['course', 'activity', 'site'], true) ? $context : 'course';
        $prefix = 'bannertitle_' . $context . '_';
        $text = trim($text);
        $titleenabled = (bool)get_config('local_course_banner_builder', $prefix . 'enabled') && $text !== '';
        $overlayenabled = (bool)get_config('local_course_banner_builder', $prefix . 'overlayenabled');

        $fontfamily = (string)get_config('local_course_banner_builder', $prefix . 'fontfamily');
        if ($fontfamily !== '' && !array_key_exists($fontfamily, manager::get_slideshow_font_family_options())) {
            $fontfamily = '';
        }
        $color = (string)get_config('local_course_banner_builder', $prefix . 'color');
        $color = preg_match('/^#[0-9a-f]{6}$/i', $color) ? $color : '#FFFFFF';
        $fontsize = (int)get_config('local_course_banner_builder', $prefix . 'fontsize');
        $fontsize = max(25, min(160, $fontsize ?: 100));
        $x = max(0.0, min(100.0, (float)(get_config('local_course_banner_builder', $prefix . 'x') ?: 50)));
        $y = max(0.0, min(100.0, (float)(get_config('local_course_banner_builder', $prefix . 'y') ?: 50)));
        $align = (string)get_config('local_course_banner_builder', $prefix . 'align');
        $align = in_array($align, ['left', 'center', 'right'], true) ? $align : 'center';
        $style = [];
        if ($titleenabled) {
            $bold = (bool)get_config('local_course_banner_builder', $prefix . 'bold');
            $italic = (bool)get_config('local_course_banner_builder', $prefix . 'italic');
            $underline = (bool)get_config('local_course_banner_builder', $prefix . 'underline');
            $allcaps = (bool)get_config('local_course_banner_builder', $prefix . 'allcaps');
            $style = [
                'left: ' . $x . '%;',
                'top: ' . $y . '%;',
                'color: ' . $color . ';',
                'font-size: clamp(' . round(8 * $fontsize / 100, 3) . 'cqh, ' .
                    round(4 * $fontsize / 100, 3) . 'cqw, ' . round(28 * $fontsize / 100, 3) . 'cqh);',
                'font-family: ' . ($fontfamily !== '' ? s($fontfamily) : 'inherit') . ';',
                'font-weight: ' . ($bold ? '800' : '500') . ';',
                'font-style: ' . ($italic ? 'italic' : 'normal') . ';',
                'text-decoration: ' . ($underline ? 'underline' : 'none') . ';',
                'text-transform: ' . ($allcaps ? 'uppercase' : 'none') . ';',
                'text-align: ' . $align . ';',
            ];
            if ((bool)get_config('local_course_banner_builder', $prefix . 'frameenabled')) {
                $framecolor = self::banner_title_rgba(
                    (string)get_config('local_course_banner_builder', $prefix . 'framecolor'),
                    (float)(get_config('local_course_banner_builder', $prefix . 'frameopacity') ?: 35)
                );
                $style[] = 'background: ' . $framecolor . ';';
                $style[] = 'border: ' . max(0, min(10, (float)get_config('local_course_banner_builder', $prefix . 'frameborderwidth'))) .
                    'px solid ' . self::normalise_banner_title_hex(
                        (string)get_config('local_course_banner_builder', $prefix . 'framebordercolor'),
                        '#FFFFFF'
                    ) . ';';
                $style[] = 'border-radius: ' . max(0, min(80, (float)get_config('local_course_banner_builder', $prefix . 'frameradius'))) . 'px;';
                $padding = max(0, min(80, (float)get_config('local_course_banner_builder', $prefix . 'framepadding')));
                $style[] = 'padding: ' . round($padding / 2, 2) . 'px ' . $padding . 'px;';
                if ((bool)get_config('local_course_banner_builder', $prefix . 'frameshadowenabled')) {
                    $distance = max(0, min(50, (float)get_config('local_course_banner_builder', $prefix . 'frameshadowdistance')));
                    $angle = deg2rad(max(0, min(360, (float)get_config('local_course_banner_builder', $prefix . 'frameshadowdirection'))));
                    $xoffset = round(cos($angle) * $distance, 2);
                    $yoffset = round(sin($angle) * $distance, 2);
                    $blur = max(0, min(80, (float)get_config('local_course_banner_builder', $prefix . 'frameshadowblur')));
                    $shadowcolor = self::banner_title_rgba(
                        (string)get_config('local_course_banner_builder', $prefix . 'frameshadowcolor'),
                        (float)(get_config('local_course_banner_builder', $prefix . 'frameshadowopacity') ?: 25)
                    );
                    $style[] = 'box-shadow: ' . $xoffset . 'px ' . $yoffset . 'px ' . $blur . 'px ' . $shadowcolor . ';';
                }
            }
            if ((bool)get_config('local_course_banner_builder', $prefix . 'shadowenabled')) {
                $distance = max(0, min(40, (float)get_config('local_course_banner_builder', $prefix . 'shadowdistance')));
                $angle = deg2rad(max(0, min(360, (float)get_config('local_course_banner_builder', $prefix . 'shadowdirection'))));
                $xoffset = round(cos($angle) * $distance, 2);
                $yoffset = round(sin($angle) * $distance, 2);
                $blur = max(0, min(60, (float)get_config('local_course_banner_builder', $prefix . 'shadowblur')));
                $shadowcolor = self::banner_title_rgba(
                    (string)get_config('local_course_banner_builder', $prefix . 'shadowcolor'),
                    (float)(get_config('local_course_banner_builder', $prefix . 'shadowopacity') ?: 55)
                );
                $style[] = 'text-shadow: ' . $xoffset . 'px ' . $yoffset . 'px ' . $blur . 'px ' . $shadowcolor . ';';
            } else {
                $style[] = 'text-shadow: none;';
            }
        }

        $overlaystyle = '';
        if ($overlayenabled) {
            $overlaystyle = 'background: ' . self::banner_title_rgba(
                (string)get_config('local_course_banner_builder', $prefix . 'overlaycolor'),
                (float)(get_config('local_course_banner_builder', $prefix . 'overlayopacity') ?: 25)
            ) . ';';
        }
        if (!$titleenabled && $overlaystyle === '') {
            return null;
        }

        return [
            'text' => $titleenabled ? $text : '',
            'style' => $titleenabled ? implode(' ', $style) : '',
            'overlaystyle' => $overlaystyle,
        ];
    }

    /**
     * Normalise a title hex colour.
     *
     * @param string $color
     * @param string $default
     * @return string
     */
    protected static function normalise_banner_title_hex(string $color, string $default): string {
        return preg_match('/^#[0-9a-f]{6}$/i', $color) ? strtoupper($color) : $default;
    }

    /**
     * Convert title colour settings to an rgba() CSS value.
     *
     * @param string $color
     * @param float $opacity
     * @return string
     */
    protected static function banner_title_rgba(string $color, float $opacity): string {
        $color = ltrim(self::normalise_banner_title_hex($color, '#000000'), '#');
        return 'rgba(' . hexdec(substr($color, 0, 2)) . ', ' .
            hexdec(substr($color, 2, 2)) . ', ' .
            hexdec(substr($color, 4, 2)) . ', ' .
            round(max(0, min(100, $opacity)) / 100, 3) . ')';
    }

    /**
     * Render one optional title overlay.
     *
     * @param array|null $title
     * @return string
     */
    protected static function render_banner_title_overlay(?array $title): string {
        if (empty($title)) {
            return '';
        }

        $out = '';
        if (!empty($title['overlaystyle'])) {
            $out .= \html_writer::div('', 'local-course-banner-builder-banner-overlay-layer', [
                'aria-hidden' => 'true',
                'style' => (string)$title['overlaystyle'],
            ]);
        }
        if (!empty($title['text']) && !empty($title['style'])) {
            $out .= \html_writer::div(
                format_string((string)$title['text']),
                'local-course-banner-builder-banner-title-overlay',
                [
                    'aria-hidden' => 'true',
                    'style' => (string)$title['style'],
                ]
            );
        }
        return $out;
    }

    /**
     * Render the full native course banner HTML directly from PHP.
     *
     * @param array $payload
     * @return string
     */
    protected static function render_native_course_banner_html(array $payload): string {
        $format = manager::normalise_banner_format((string)($payload['bannerformat'] ?? manager::BANNER_FORMAT_STANDARD));
        $content = '';
        $contentstyle = '';
        $usesdefaultimage = !empty($payload['usesdefaultimage']);
        if ($usesdefaultimage && !empty($payload['bannerurl'])) {
            $contentstyle = 'background-image: url("' . s((string)$payload['bannerurl']) . '");';
        } else if (!empty($payload['bannerurl'])) {
            $content .= \html_writer::empty_tag('img', [
                'class' => 'banner-layer banner-layer-1',
                'src' => (string)$payload['bannerurl'],
                'alt' => '',
                'loading' => 'lazy',
                'aria-hidden' => 'true',
            ]);
        }

        $overlayhtml = '';
        if (!empty($payload['border']) && is_array($payload['border'])) {
            $overlayhtml .= self::render_native_border_html($payload['border']);
        }
        foreach (($payload['overlays'] ?? []) as $overlay) {
            if (!is_array($overlay) || empty($overlay['url'])) {
                continue;
            }

            $overlayhtml .= \html_writer::start_div('local-course-banner-builder-fixed-overlay', [
                'aria-hidden' => 'true',
                'style' => (string)($overlay['wrapperstyle'] ?? ''),
            ]);
            $overlayhtml .= \html_writer::empty_tag('img', [
                'class' => 'local-course-banner-builder-fixed-overlay-image',
                'src' => (string)$overlay['url'],
                'alt' => '',
                'loading' => 'lazy',
                'aria-hidden' => 'true',
                'style' => (string)($overlay['imagestyle'] ?? ''),
            ]);
            $overlayhtml .= \html_writer::end_div();
        }

        if ($overlayhtml !== '') {
            $content .= \html_writer::div($overlayhtml, 'local-course-banner-builder-fixed-overlays', ['aria-hidden' => 'true']);
        }
        $content .= self::render_banner_title_overlay($payload['title'] ?? null);

        $banner = \html_writer::div(
            $content,
            'local-course-banner-builder local-course-banner-builder-native-course-banner ' .
                'local-course-banner-builder-native-course-banner--format-' . $format .
                ($usesdefaultimage ? ' local-course-banner-builder-native-course-banner--moodle-generated' : ''),
            [
                'data-course-banner-builder-native' => '1',
                'aria-hidden' => 'true',
                'style' => $contentstyle,
            ]
        );

        return \html_writer::div($banner, 'header-maxwidth d-print-none local-course-banner-builder-native-shell ' .
            'local-course-banner-builder-native-shell--format-' . $format, [
            'id' => 'local-course-banner-builder-native-shell',
            'data-banner-format' => $format,
        ]);
    }

    /**
     * Build the site-wide banner payload.
     *
     * @param \moodle_page $page
     * @return array|null
     */
    protected static function get_site_banner_payload(\moodle_page $page): ?array {
        if (!self::can_render_banners()) {
            return null;
        }

        if (!manager::is_site_banner_enabled() || !self::is_site_banner_page($page)) {
            return null;
        }

        $definition = manager::export_site_banner_render_definition();
        if (empty($definition['haslayers'])) {
            return null;
        }

        global $SITE;

        $definition['bannerformat'] = manager::get_site_banner_format();
        $definition['title'] = self::get_banner_title_overlay('site', (string)($SITE->fullname ?? ''));
        return $definition;
    }

    /**
     * Render the site-wide banner HTML.
     *
     * @param array $payload
     * @return string
     */
    protected static function render_site_banner_html(array $payload): string {
        $format = manager::normalise_banner_format((string)($payload['bannerformat'] ?? manager::BANNER_FORMAT_STANDARD));
        $content = '';
        foreach (($payload['layers'] ?? []) as $layer) {
            if (!is_array($layer) || empty($layer['enabled'])) {
                continue;
            }

            if (($layer['type'] ?? '') === 'border') {
                $content .= self::render_native_border_html($layer);
                continue;
            }

            if (($layer['type'] ?? '') !== 'image' || empty($layer['url'])) {
                continue;
            }

            $wrapperstyle = trim((string)($layer['wrapperstyle'] ?? ''));
            if ($wrapperstyle !== '' && !str_ends_with($wrapperstyle, ';')) {
                $wrapperstyle .= ';';
            }
            $wrapperstyle .= ' z-index: ' . (int)($layer['zindex'] ?? 1) . ';';

            $content .= \html_writer::start_div('local-course-banner-builder-fixed-overlay', [
                'aria-hidden' => 'true',
                'style' => $wrapperstyle,
            ]);
            $content .= \html_writer::empty_tag('img', [
                'class' => 'local-course-banner-builder-fixed-overlay-image',
                'src' => (string)$layer['url'],
                'alt' => '',
                'loading' => 'lazy',
                'aria-hidden' => 'true',
                'style' => (string)($layer['imagestyle'] ?? ''),
            ]);
            $content .= \html_writer::end_div();
        }

        if ($content === '') {
            return '';
        }

        $content = \html_writer::div($content, 'local-course-banner-builder-fixed-overlays', ['aria-hidden' => 'true']) .
            self::render_banner_title_overlay($payload['title'] ?? null);

        $banner = \html_writer::div(
            $content,
            'local-course-banner-builder local-course-banner-builder-native-course-banner local-course-banner-builder-site-banner ' .
                'local-course-banner-builder-native-course-banner--format-' . $format,
            [
                'data-course-banner-builder-site' => '1',
                'aria-hidden' => 'true',
            ]
        );

        return \html_writer::div(
            $banner,
            'header-maxwidth d-print-none local-course-banner-builder-native-shell local-course-banner-builder-site-shell ' .
                'local-course-banner-builder-native-shell--format-' . $format,
            [
                'id' => 'local-course-banner-builder-site-shell',
                'data-banner-format' => $format,
            ]
        );
    }

    /**
     * Build a tiny script that moves the server-rendered native banner into the Boost course header.
     *
     * @return string
     */
    protected static function get_native_course_banner_mount_script(): string {
        return <<<JS
(function() {
    const findScrollFrame = function(shell) {
        const page = document.getElementById('page');
        if (page && page.contains(shell) && page.classList.contains('drawers')) {
            return page;
        }
        let node = shell.parentElement;
        while (node && node !== document.body && node !== document.documentElement) {
            const style = window.getComputedStyle(node);
            const scrollsY = node.scrollHeight > node.clientHeight + 1;
            if (scrollsY && ['auto', 'scroll', 'overlay'].indexOf(style.overflowY) !== -1) {
                return node;
            }
            node = node.parentElement;
        }
        return document.documentElement;
    };

    const measureScrollbarWidth = function() {
        const probe = document.createElement('div');
        probe.style.cssText = 'position:absolute;top:-9999px;left:-9999px;width:100px;height:100px;overflow:scroll;visibility:hidden;';
        document.body.appendChild(probe);
        const width = Math.max(0, probe.offsetWidth - probe.clientWidth);
        probe.remove();
        return width;
    };

    const alignFullWidthBanner = function(shell) {
        const navbar = document.querySelector('.navbar.fixed-top, nav.fixed-top, header.navbar, .primary-navigation');
        if (!shell) {
            return;
        }
        document.documentElement.classList.add('local-course-banner-builder-fullwidth-banner-mounted');
        if (document.body) {
            document.body.classList.add('local-course-banner-builder-fullwidth-banner-mounted');
        }
        void document.documentElement.offsetWidth;
        const frame = findScrollFrame(shell);
        if (frame && frame.classList) {
            frame.classList.add('local-course-banner-builder-fullwidth-scroll-frame');
        }
        void frame.offsetWidth;
        const frameRect = frame === document.documentElement ?
            {left: 0, width: (document.documentElement.clientWidth || window.innerWidth || 0)} :
            frame.getBoundingClientRect();
        const frameScrollbarWidth = Math.max(0, Math.round((frame.offsetWidth || 0) - (frame.clientWidth || 0)));
        const hasFrameScrollbar = frame.scrollHeight > frame.clientHeight + 1;
        const overlayScrollbarReserve = hasFrameScrollbar && frameScrollbarWidth === 0 && frame !== document.documentElement ?
            measureScrollbarWidth() :
            0;
        const baseWidth = Math.floor(frame.clientWidth || frameRect.width || document.documentElement.clientWidth || window.innerWidth || 0);
        const usableWidth = Math.max(0, baseWidth - overlayScrollbarReserve);
        const targetLeft = Math.round(frameRect.left || 0);
        if (usableWidth > 0) {
            shell.style.setProperty('box-sizing', 'border-box', 'important');
            shell.style.setProperty('position', 'relative', 'important');
            shell.style.setProperty('left', '0px', 'important');
            shell.style.setProperty('right', 'auto', 'important');
            shell.style.setProperty('width', usableWidth + 'px', 'important');
            shell.style.setProperty('max-width', usableWidth + 'px', 'important');
            shell.style.setProperty('margin-left', '0px', 'important');
            shell.style.setProperty('margin-right', '0px', 'important');
            shell.style.setProperty('padding-left', '0px', 'important');
            shell.style.setProperty('padding-right', '0px', 'important');
            window.requestAnimationFrame(function() {
                const rect = shell.getBoundingClientRect();
                const delta = targetLeft - rect.left;
                if (Math.abs(delta) > 0.5) {
                    shell.style.setProperty('left', delta + 'px', 'important');
                }
            });
        }
        if (!navbar) {
            return;
        }
        shell.style.setProperty('--local-course-banner-builder-fullwidth-nav-offset', '0px');
        window.requestAnimationFrame(function() {
            const gap = Math.round(shell.getBoundingClientRect().top - navbar.getBoundingClientRect().bottom);
            if (gap !== 0) {
                shell.style.setProperty('--local-course-banner-builder-fullwidth-nav-offset', Math.min(0, -gap) + 'px');
            }
        });
    };

    const mount = function() {
        const shell = document.getElementById('local-course-banner-builder-native-shell');
        if (!shell) {
            return;
        }

        const pageHeader = document.getElementById('page-header');
        const format = shell.getAttribute('data-banner-format') || 'standard';
        const isFullWidthTop = format === 'fullwidthtop' || format === 'fullwidthtopcompact';
        if (pageHeader) {
            const method = isFullWidthTop ? 'beforebegin' : 'afterend';
            const alreadyPlaced = isFullWidthTop ?
                shell.nextElementSibling === pageHeader :
                shell.previousElementSibling === pageHeader;
            if (!alreadyPlaced) {
                pageHeader.insertAdjacentElement(method, shell);
            }
            shell.classList.add('local-course-banner-builder-native-shell--mounted');
            if (isFullWidthTop) {
                alignFullWidthBanner(shell);
                [80, 320, 900, 1800].forEach(function(delay) {
                    window.setTimeout(function() {
                        alignFullWidthBanner(shell);
                    }, delay);
                });
            }
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mount, {once: true});
    } else {
        mount();
    }

    window.setTimeout(mount, 150);
    window.setTimeout(mount, 800);
    window.setTimeout(mount, 1600);
    window.addEventListener('resize', mount);
})();
JS;
    }

    /**
     * Mount the site banner below the page header on non-course pages.
     *
     * @return string
     */
    protected static function get_site_banner_mount_script(): string {
        return <<<JS
(function() {
    const findScrollFrame = function(shell) {
        const page = document.getElementById('page');
        if (page && page.contains(shell) && page.classList.contains('drawers')) {
            return page;
        }
        let node = shell.parentElement;
        while (node && node !== document.body && node !== document.documentElement) {
            const style = window.getComputedStyle(node);
            const scrollsY = node.scrollHeight > node.clientHeight + 1;
            if (scrollsY && ['auto', 'scroll', 'overlay'].indexOf(style.overflowY) !== -1) {
                return node;
            }
            node = node.parentElement;
        }
        return document.documentElement;
    };

    const measureScrollbarWidth = function() {
        const probe = document.createElement('div');
        probe.style.cssText = 'position:absolute;top:-9999px;left:-9999px;width:100px;height:100px;overflow:scroll;visibility:hidden;';
        document.body.appendChild(probe);
        const width = Math.max(0, probe.offsetWidth - probe.clientWidth);
        probe.remove();
        return width;
    };

    const alignFullWidthBanner = function(shell) {
        const navbar = document.querySelector('.navbar.fixed-top, nav.fixed-top, header.navbar, .primary-navigation');
        if (!shell) {
            return;
        }
        document.documentElement.classList.add('local-course-banner-builder-fullwidth-banner-mounted');
        if (document.body) {
            document.body.classList.add('local-course-banner-builder-fullwidth-banner-mounted');
        }
        void document.documentElement.offsetWidth;
        const frame = findScrollFrame(shell);
        if (frame && frame.classList) {
            frame.classList.add('local-course-banner-builder-fullwidth-scroll-frame');
        }
        void frame.offsetWidth;
        const frameRect = frame === document.documentElement ?
            {left: 0, width: (document.documentElement.clientWidth || window.innerWidth || 0)} :
            frame.getBoundingClientRect();
        const frameScrollbarWidth = Math.max(0, Math.round((frame.offsetWidth || 0) - (frame.clientWidth || 0)));
        const hasFrameScrollbar = frame.scrollHeight > frame.clientHeight + 1;
        const overlayScrollbarReserve = hasFrameScrollbar && frameScrollbarWidth === 0 && frame !== document.documentElement ?
            measureScrollbarWidth() :
            0;
        const baseWidth = Math.floor(frame.clientWidth || frameRect.width || document.documentElement.clientWidth || window.innerWidth || 0);
        const usableWidth = Math.max(0, baseWidth - overlayScrollbarReserve);
        const targetLeft = Math.round(frameRect.left || 0);
        if (usableWidth > 0) {
            shell.style.setProperty('box-sizing', 'border-box', 'important');
            shell.style.setProperty('position', 'relative', 'important');
            shell.style.setProperty('left', '0px', 'important');
            shell.style.setProperty('right', 'auto', 'important');
            shell.style.setProperty('width', usableWidth + 'px', 'important');
            shell.style.setProperty('max-width', usableWidth + 'px', 'important');
            shell.style.setProperty('margin-left', '0px', 'important');
            shell.style.setProperty('margin-right', '0px', 'important');
            shell.style.setProperty('padding-left', '0px', 'important');
            shell.style.setProperty('padding-right', '0px', 'important');
            window.requestAnimationFrame(function() {
                const rect = shell.getBoundingClientRect();
                const delta = targetLeft - rect.left;
                if (Math.abs(delta) > 0.5) {
                    shell.style.setProperty('left', delta + 'px', 'important');
                }
            });
        }
        if (!navbar) {
            return;
        }
        shell.style.setProperty('--local-course-banner-builder-fullwidth-nav-offset', '0px');
        window.requestAnimationFrame(function() {
            const gap = Math.round(shell.getBoundingClientRect().top - navbar.getBoundingClientRect().bottom);
            if (gap !== 0) {
                shell.style.setProperty('--local-course-banner-builder-fullwidth-nav-offset', Math.min(0, -gap) + 'px');
            }
        });
    };

    const mount = function() {
        const shell = document.getElementById('local-course-banner-builder-site-shell');
        if (!shell) {
            return;
        }

        const pageHeader = document.getElementById('page-header');
        const format = shell.getAttribute('data-banner-format') || 'standard';
        const isFullWidthTop = format === 'fullwidthtop' || format === 'fullwidthtopcompact';
        if (pageHeader) {
            const method = isFullWidthTop ? 'beforebegin' : 'afterend';
            const alreadyPlaced = isFullWidthTop ?
                shell.nextElementSibling === pageHeader :
                shell.previousElementSibling === pageHeader;
            if (!alreadyPlaced) {
                pageHeader.insertAdjacentElement(method, shell);
            }
            shell.classList.add('local-course-banner-builder-native-shell--mounted');
            if (isFullWidthTop) {
                alignFullWidthBanner(shell);
                [80, 320, 900, 1800].forEach(function(delay) {
                    window.setTimeout(function() {
                        alignFullWidthBanner(shell);
                    }, delay);
                });
            }
            return;
        }

        const regionMain = document.getElementById('region-main');
        if (regionMain && shell.nextElementSibling !== regionMain) {
            regionMain.insertAdjacentElement('beforebegin', shell);
            shell.classList.add('local-course-banner-builder-native-shell--mounted');
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mount, {once: true});
    } else {
        mount();
    }

    window.setTimeout(mount, 150);
    window.setTimeout(mount, 800);
    window.setTimeout(mount, 1600);
    window.addEventListener('resize', mount);
})();
JS;
    }

    /**
     * Build the runtime script that overlays slideshow content on the current banner.
     *
     * @param array $payload
     * @return string
     */
    protected static function get_banner_slideshow_runtime_script(array $payload): string {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return '';
        }

        return <<<JS
(function() {
    const payload = {$json};
    if (!payload || !payload.enabled || !Array.isArray(payload.slides) || !payload.slides.length) {
        return;
    }

    const processedAttribute = 'data-course-banner-builder-slideshow-processed';
    const context = payload.context === 'site' ? 'site' : 'course';
    const selectors = context === 'site' ?
        [
            '.local-course-banner-builder-site-banner'
        ] :
        [
            '.local-course-banner-builder-native-course-banner',
            '#page-header .page-header-banner',
            '.page-header-banner',
            '.course-banner',
            '.course-header-banner',
            '[data-region="course-banner"]'
        ];

    const isVisibleBanner = function(target) {
        if (!target || target.closest('.local-course-banner-builder-slideshow')) {
            return false;
        }
        const rect = target.getBoundingClientRect();
        const style = window.getComputedStyle(target);
        return rect.width > 120 &&
            rect.height > 40 &&
            style.display !== 'none' &&
            style.visibility !== 'hidden';
    };

    const createIcon = function(name) {
        const icon = document.createElement('i');
        icon.className = 'icon fa fa-' + name + ' fa-fw';
        icon.setAttribute('aria-hidden', 'true');
        return icon;
    };

    const createText = function(tag, className, text) {
        const node = document.createElement(tag);
        node.className = className;
        node.textContent = text || '';
        return node;
    };

    const activityIconMap = {
        forums: ['mod_forum', 'monologo', 'comments'],
        siteannouncements: ['mod_forum', 'monologo', 'comments'],
        assignments: ['mod_assign', 'monologo', 'tasks'],
        assignment: ['mod_assign', 'monologo', 'tasks'],
        quizzes: ['mod_quiz', 'monologo', 'question-circle'],
        quiz: ['mod_quiz', 'monologo', 'question-circle']
    };

    const createActivityIcon = function(type) {
        const definition = activityIconMap[type];
        if (!definition) {
            return null;
        }
        if (window.M && M.util && typeof M.util.image_url === 'function') {
            const icon = document.createElement('img');
            icon.className = 'icon activityicon local-course-banner-builder-slideshow-label-icon';
            icon.src = M.util.image_url(definition[1], definition[0]);
            icon.alt = '';
            icon.setAttribute('aria-hidden', 'true');
            icon.addEventListener('error', function() {
                icon.replaceWith(createIcon(definition[2]));
            }, {once: true});
            return icon;
        }
        return createIcon(definition[2]);
    };

    const createLabel = function(type, text, withIcon) {
        const label = document.createElement('div');
        label.className = 'local-course-banner-builder-slideshow-label local-course-banner-builder-slideshow-label--' + type;
        const icon = withIcon ? createActivityIcon(type) : null;
        if (icon) {
            label.appendChild(icon);
        }
        const span = document.createElement('span');
        span.textContent = text || '';
        label.appendChild(span);
        return label;
    };

    const buildSlide = function(slide, index) {
        const isEmpty = !!slide.empty;
        const rawType = String(slide.type || 'generic').toLowerCase();
        const type = rawType.replace(/[^a-z0-9_-]/g, '') || 'generic';
        const node = document.createElement('article');
        node.className = 'local-course-banner-builder-slideshow-slide' +
            (isEmpty ? ' local-course-banner-builder-slideshow-slide--empty' : '') +
            (index === 0 ? ' is-active' : '');
        node.setAttribute('data-slideshow-slide', String(index));
        node.setAttribute('aria-hidden', index === 0 ? 'false' : 'true');
        if (isEmpty) {
            return node;
        }

        const labels = document.createElement('div');
        labels.className = 'local-course-banner-builder-slideshow-labels';
        labels.setAttribute('data-slideshow-preview-draggable', 'label');
        labels.appendChild(createLabel(type, slide.label || '', true));
        if (slide.secondaryLabel) {
            labels.appendChild(createLabel('course-shortname', slide.secondaryLabel, false));
        }
        node.appendChild(labels);
        const content = document.createElement('div');
        content.className = 'local-course-banner-builder-slideshow-content';
        const titleWrap = document.createElement('div');
        titleWrap.className = 'local-course-banner-builder-slideshow-title-block';
        titleWrap.appendChild(createText('h3', 'local-course-banner-builder-slideshow-title', slide.title || ''));
        content.appendChild(titleWrap);
        const bodyWrap = document.createElement('div');
        bodyWrap.className = 'local-course-banner-builder-slideshow-body-block';
        if (slide.meta) {
            bodyWrap.appendChild(createText('p', 'local-course-banner-builder-slideshow-meta', slide.meta));
        }
        if (slide.body) {
            bodyWrap.appendChild(createText('p', 'local-course-banner-builder-slideshow-body', slide.body));
        }
        content.appendChild(bodyWrap);
        if (slide.url) {
            const actionWrap = document.createElement('div');
            actionWrap.className = 'local-course-banner-builder-slideshow-action-wrap';
            const link = document.createElement('a');
            link.className = 'btn local-course-banner-builder-slideshow-action';
            link.href = slide.url;
            link.textContent = (payload.strings && payload.strings.view) || 'View';
            actionWrap.appendChild(link);
            content.appendChild(actionWrap);
        }
        node.appendChild(content);

        return node;
    };

    const activate = function(root, index, direction) {
        const slides = Array.prototype.slice.call(root.querySelectorAll('[data-slideshow-slide]'));
        const dots = Array.prototype.slice.call(root.querySelectorAll('[data-slideshow-dot]'));
        if (!slides.length) {
            return;
        }
        const next = ((index % slides.length) + slides.length) % slides.length;
        root.dataset.slideshowIndex = String(next);
        root.dataset.slideshowDirection = direction || 'forward';
        root.classList.toggle('is-empty-active', !!(payload.slides[next] && payload.slides[next].empty));
        slides.forEach(function(slide, slideIndex) {
            const active = slideIndex === next;
            slide.classList.toggle('is-active', active);
            slide.setAttribute('aria-hidden', active ? 'false' : 'true');
        });
        dots.forEach(function(dot, dotIndex) {
            dot.classList.toggle('is-active', dotIndex === next);
        });
    };

    const buildSlideshow = function(target) {
        const old = target.querySelector(':scope > .local-course-banner-builder-slideshow');
        if (old) {
            old.remove();
        }

        const root = document.createElement('div');
        root.className = 'local-course-banner-builder-slideshow';
        root.setAttribute('data-course-banner-builder-slideshow', '1');
        root.setAttribute('role', 'region');
        root.setAttribute('aria-label', 'Banner slideshow');
        root.dataset.slideshowIndex = '0';
        root.classList.toggle('is-empty-active', !!(payload.slides[0] && payload.slides[0].empty));
        root.style.setProperty('--local-course-banner-builder-slideshow-overlay-rgb', payload.overlayRgb || '0, 0, 0');
        root.style.setProperty(
            '--local-course-banner-builder-slideshow-overlay-opacity',
            String(payload.overlayOpacity || 0.38)
        );
        const getFormatSizeScale = function(format) {
            if (format === 'standard') {
                return 1.24;
            }
            if (format === 'fullwidthtopcompact') {
                return 0.78;
            }
            return 1;
        };
        const format = target.getAttribute('data-banner-format') ||
            (target.closest('[data-banner-format]') ? target.closest('[data-banner-format]').getAttribute('data-banner-format') : '') ||
            'standard';
        const formatSizeScale = getFormatSizeScale(format);
        const buildTitleSize = function(percent) {
            const value = Math.max(25, Math.min(100, parseInt(percent || 100, 10)));
            const scale = value / 100 * formatSizeScale;
            return 'clamp(' + (10 * scale).toFixed(3) + 'cqh, ' +
                'min(' + (28 * scale).toFixed(3) + 'cqh, ' +
                (3.4 * scale).toFixed(3) + 'cqw), ' +
                (36 * scale).toFixed(3) + 'cqh)';
        };
        const buildBodySize = function(percent) {
            const value = Math.max(25, Math.min(100, parseInt(percent || 100, 10)));
            const scale = value / 100 * formatSizeScale;
            return 'clamp(' + (5.5 * scale).toFixed(3) + 'cqh, ' +
                'min(' + (14 * scale).toFixed(3) + 'cqh, ' +
                (1.7 * scale).toFixed(3) + 'cqw), ' +
                (19 * scale).toFixed(3) + 'cqh)';
        };
        const buildLabelSize = function(percent) {
            const value = Math.max(25, Math.min(100, parseInt(percent || 100, 10)));
            const scale = value / 100 * formatSizeScale;
            return 'clamp(' + (3.5 * scale).toFixed(3) + 'cqh, ' +
                'min(' + (6.4 * scale).toFixed(3) + 'cqh, ' +
                (0.82 * scale).toFixed(3) + 'cqw), ' +
                (8.4 * scale).toFixed(3) + 'cqh)';
        };
        const buildActionSize = function(percent) {
            const value = Math.max(25, Math.min(100, parseInt(percent || 100, 10)));
            const scale = value / 100 * formatSizeScale;
            return 'clamp(' + (6 * scale).toFixed(3) + 'cqh, ' +
                'min(' + (13 * scale).toFixed(3) + 'cqh, ' +
                (1.6 * scale).toFixed(3) + 'cqw), ' +
                (18 * scale).toFixed(3) + 'cqh)';
        };
        const buildActionWidth = function(percent) {
            const value = Math.max(25, Math.min(100, parseInt(percent || 100, 10)));
            const scale = value / 100 * formatSizeScale;
            return 'clamp(' + (10 * scale).toFixed(3) + 'cqw, ' +
                (18 * scale).toFixed(3) + 'cqw, ' +
                (34 * scale).toFixed(3) + 'cqw)';
        };
        const buildActionHeight = function(percent) {
            const value = Math.max(25, Math.min(100, parseInt(percent || 100, 10)));
            const scale = value / 100 * formatSizeScale;
            return 'clamp(' + (10 * scale).toFixed(3) + 'cqh, ' +
                'min(' + (22 * scale).toFixed(3) + 'cqh, ' +
                (2.7 * scale).toFixed(3) + 'cqw), ' +
                (34 * scale).toFixed(3) + 'cqh)';
        };
        root.style.setProperty(
            '--local-course-banner-builder-slideshow-title-font-size',
            buildTitleSize(payload.titleFontPercent)
        );
        root.style.setProperty(
            '--local-course-banner-builder-slideshow-body-font-size',
            buildBodySize(payload.bodyFontPercent)
        );
        root.style.setProperty(
            '--local-course-banner-builder-slideshow-action-font-size',
            buildActionSize(payload.actionSizePercent)
        );
        root.style.setProperty(
            '--local-course-banner-builder-slideshow-action-width',
            buildActionWidth(payload.actionWidthPercent)
        );
        root.style.setProperty(
            '--local-course-banner-builder-slideshow-action-height',
            buildActionHeight(payload.actionHeightPercent)
        );
        root.style.setProperty(
            '--local-course-banner-builder-slideshow-label-font-size',
            buildLabelSize(payload.labelSizePercent)
        );
        root.style.setProperty(
            '--local-course-banner-builder-slideshow-label-text-scale',
            (Math.max(25, Math.min(160, parseInt(payload.labelTextSizePercent || 100, 10))) / 100).toFixed(2)
        );
        root.style.setProperty(
            '--local-course-banner-builder-slideshow-label-orientation',
            payload.labelOrientation === 'column' ? 'column' : 'row'
        );
        root.style.setProperty(
            '--local-course-banner-builder-slideshow-label-radius',
            payload.labelCorners === 'square' ? '0.28rem' : '999px'
        );
        root.style.setProperty(
            '--local-course-banner-builder-slideshow-action-radius',
            payload.actionCorners === 'square' ? '0.28rem' : '999px'
        );
        const colourToRgb = function(value) {
            const raw = String(value || '#000000').replace('#', '');
            if (!/^[0-9a-f]{6}$/i.test(raw)) {
                return '0, 0, 0';
            }
            return [
                parseInt(raw.substring(0, 2), 16) || 0,
                parseInt(raw.substring(2, 4), 16) || 0,
                parseInt(raw.substring(4, 6), 16) || 0
            ].join(', ');
        };
        const setDesignVariable = function(target, key, value, suffix) {
            root.style.setProperty(
                '--local-course-banner-builder-slideshow-' + target + '-' + key,
                String(value) + (suffix || '')
            );
        };
        const setShadowVector = function(target, distance, direction) {
            const radians = ((parseFloat(direction || 90) || 0) * Math.PI) / 180;
            const offset = parseFloat(distance || 0) || 0;
            setDesignVariable(target, 'shadow-x', (Math.cos(radians) * offset).toFixed(2), 'px');
            setDesignVariable(target, 'shadow-y', (Math.sin(radians) * offset).toFixed(2), 'px');
        };
        ['action', 'label'].forEach(function(target) {
            setDesignVariable(target, 'opacity', Math.max(0, Math.min(100, payload[target + 'Opacity'] || 100)) / 100);
            setDesignVariable(target, 'border-width', payload[target + 'BorderWidth'] || 0, 'px');
            setDesignVariable(target, 'radius', payload[target + 'Radius'] || 0, 'px');
            setDesignVariable(target, 'padding', payload[target + 'Padding'] || 0, 'px');
            setDesignVariable(target, 'shadow-opacity', Math.max(0, Math.min(100, payload[target + 'ShadowOpacity'] || 0)) / 100);
            setDesignVariable(target, 'shadow-blur', payload[target + 'ShadowBlur'] || 0, 'px');
            setShadowVector(target, payload[target + 'ShadowDistance'], payload[target + 'ShadowDirection']);
            setDesignVariable(target, 'background', payload[target + 'BackgroundColor'] || '#FFFFFF');
            setDesignVariable(target, 'border-color', payload[target + 'BorderColor'] || '#FFFFFF');
            setDesignVariable(target, 'shadow-rgb', colourToRgb(payload[target + 'ShadowColor'] || '#000000'));
            setDesignVariable(target, 'font-family', payload[target + 'FontFamily'] || 'inherit');
            setDesignVariable(target, 'text-color', payload[target + 'TextColor'] || '#111827');
        });
        root.style.setProperty(
            '--local-course-banner-builder-slideshow-title-color',
            String(payload.titleColor || '#FFFFFF')
        );
        root.style.setProperty(
            '--local-course-banner-builder-slideshow-body-color',
            String(payload.bodyColor || '#FFFFFF')
        );
        root.style.setProperty(
            '--local-course-banner-builder-slideshow-title-font-family',
            String(payload.titleFontFamily || 'inherit')
        );
        root.style.setProperty(
            '--local-course-banner-builder-slideshow-body-font-family',
            String(payload.bodyFontFamily || 'inherit')
        );
        root.style.setProperty(
            '--local-course-banner-builder-slideshow-title-text-align',
            ['left', 'center', 'right'].indexOf(payload.titleAlign) !== -1 ? payload.titleAlign : 'center'
        );
        root.style.setProperty(
            '--local-course-banner-builder-slideshow-body-text-align',
            ['left', 'center', 'right'].indexOf(payload.bodyAlign) !== -1 ? payload.bodyAlign : 'center'
        );
        root.style.setProperty(
            '--local-course-banner-builder-slideshow-label-translate-x',
            payload.labelAlign === 'left' ? '0%' : (payload.labelAlign === 'right' ? '-100%' : '-50%')
        );
        const titleDecoration = [];
        if (payload.titleUnderline) {
            titleDecoration.push('underline');
        }
        if (payload.titleStrike) {
            titleDecoration.push('line-through');
        }
        const bodyDecoration = [];
        if (payload.bodyUnderline) {
            bodyDecoration.push('underline');
        }
        if (payload.bodyStrike) {
            bodyDecoration.push('line-through');
        }
        root.style.setProperty(
            '--local-course-banner-builder-slideshow-title-font-weight',
            payload.titleBold ? '800' : '400'
        );
        root.style.setProperty(
            '--local-course-banner-builder-slideshow-title-font-style',
            payload.titleItalic ? 'italic' : 'normal'
        );
        root.style.setProperty(
            '--local-course-banner-builder-slideshow-title-text-decoration',
            titleDecoration.length ? titleDecoration.join(' ') : 'none'
        );
        root.style.setProperty(
            '--local-course-banner-builder-slideshow-title-text-transform',
            payload.titleAllCaps ? 'uppercase' : 'none'
        );
        root.style.setProperty(
            '--local-course-banner-builder-slideshow-body-font-weight',
            payload.bodyBold ? '700' : '400'
        );
        root.style.setProperty(
            '--local-course-banner-builder-slideshow-body-font-style',
            payload.bodyItalic ? 'italic' : 'normal'
        );
        root.style.setProperty(
            '--local-course-banner-builder-slideshow-body-text-decoration',
            bodyDecoration.length ? bodyDecoration.join(' ') : 'none'
        );
        root.style.setProperty(
            '--local-course-banner-builder-slideshow-body-text-transform',
            payload.bodyAllCaps ? 'uppercase' : 'none'
        );
        ['action', 'label'].forEach(function(target) {
            const decoration = [];
            if (payload[target + 'Underline']) {
                decoration.push('underline');
            }
            if (payload[target + 'Strike']) {
                decoration.push('line-through');
            }
            root.style.setProperty(
                '--local-course-banner-builder-slideshow-' + target + '-font-weight',
                payload[target + 'Bold'] ? '700' : '400'
            );
            root.style.setProperty(
                '--local-course-banner-builder-slideshow-' + target + '-font-style',
                payload[target + 'Italic'] ? 'italic' : 'normal'
            );
            root.style.setProperty(
                '--local-course-banner-builder-slideshow-' + target + '-text-decoration',
                decoration.length ? decoration.join(' ') : 'none'
            );
            root.style.setProperty(
                '--local-course-banner-builder-slideshow-' + target + '-text-transform',
                payload[target + 'AllCaps'] ? 'uppercase' : 'none'
            );
        });
        root.style.setProperty(
            '--local-course-banner-builder-slideshow-title-x',
            String(payload.titleX || 50) + '%'
        );
        root.style.setProperty(
            '--local-course-banner-builder-slideshow-title-y',
            String(payload.titleY || 32) + '%'
        );
        root.style.setProperty(
            '--local-course-banner-builder-slideshow-body-x',
            String(payload.bodyX || 50) + '%'
        );
        root.style.setProperty(
            '--local-course-banner-builder-slideshow-body-y',
            String(payload.bodyY || 43) + '%'
        );
        root.style.setProperty(
            '--local-course-banner-builder-slideshow-action-x',
            String(payload.actionX || 50) + '%'
        );
        root.style.setProperty(
            '--local-course-banner-builder-slideshow-action-y',
            String(payload.actionY || 74) + '%'
        );
        root.style.setProperty(
            '--local-course-banner-builder-slideshow-label-x',
            String(payload.labelX || 14) + '%'
        );
        root.style.setProperty(
            '--local-course-banner-builder-slideshow-label-y',
            String(payload.labelY || 10) + '%'
        );
        if (payload.labelColors) {
            Object.keys(payload.labelColors).forEach(function(type) {
                const safeType = String(type).replace(/[^a-z0-9_-]/g, '');
                const colours = payload.labelColors[type] || {};
                if (safeType && colours.background) {
                    root.style.setProperty(
                        '--local-course-banner-builder-slideshow-label-' + safeType + '-bg',
                        String(colours.background)
                    );
                }
                if (safeType && colours.text) {
                    root.style.setProperty(
                        '--local-course-banner-builder-slideshow-label-' + safeType + '-color',
                        String(colours.text)
                    );
                }
                if (safeType && colours.border) {
                    root.style.setProperty(
                        '--local-course-banner-builder-slideshow-label-' + safeType + '-border',
                        String(colours.border)
                    );
                }
                if (safeType && colours.shadow) {
                    root.style.setProperty(
                        '--local-course-banner-builder-slideshow-label-' + safeType + '-shadow',
                        String(colours.shadow)
                    );
                    root.style.setProperty(
                        '--local-course-banner-builder-slideshow-label-' + safeType + '-shadow-rgb',
                        colourToRgb(String(colours.shadow))
                    );
                }
            });
        }

        payload.slides.forEach(function(slide, index) {
            root.appendChild(buildSlide(slide, index));
        });

        if (payload.slides.length > 1 && payload.arrows) {
            const previous = document.createElement('button');
            previous.type = 'button';
            previous.className = 'local-course-banner-builder-slideshow-nav local-course-banner-builder-slideshow-nav--prev';
            previous.setAttribute('aria-label', (payload.strings && payload.strings.previous) || 'Previous slide');
            previous.appendChild(createIcon('chevron-left'));
            previous.addEventListener('click', function() {
                activate(root, parseInt(root.dataset.slideshowIndex || '0', 10) - 1, 'backward');
            });
            root.appendChild(previous);

            const next = document.createElement('button');
            next.type = 'button';
            next.className = 'local-course-banner-builder-slideshow-nav local-course-banner-builder-slideshow-nav--next';
            next.setAttribute('aria-label', (payload.strings && payload.strings.next) || 'Next slide');
            next.appendChild(createIcon('chevron-right'));
            next.addEventListener('click', function() {
                activate(root, parseInt(root.dataset.slideshowIndex || '0', 10) + 1, 'forward');
            });
            root.appendChild(next);
        }

        if (payload.slides.length > 1 && payload.dots) {
            const dots = document.createElement('div');
            dots.className = 'local-course-banner-builder-slideshow-dots';
            payload.slides.forEach(function(slide, index) {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'local-course-banner-builder-slideshow-dot' + (index === 0 ? ' is-active' : '');
                dot.setAttribute('data-slideshow-dot', String(index));
                dot.setAttribute('aria-label', ((payload.strings && payload.strings.slide) || 'Slide') + ' ' + (index + 1));
                dot.addEventListener('click', function() {
                    const current = parseInt(root.dataset.slideshowIndex || '0', 10);
                    activate(root, index, index >= current ? 'forward' : 'backward');
                });
                dots.appendChild(dot);
            });
            root.appendChild(dots);
        }

        target.classList.add('local-course-banner-builder-slideshow-host');
        target.removeAttribute('aria-hidden');
        target.appendChild(root);
        target.setAttribute(processedAttribute, context);

        let timer = null;
        const stop = function() {
            if (timer) {
                window.clearInterval(timer);
                timer = null;
            }
        };
        const start = function() {
            stop();
            if (!payload.autoplay || payload.slides.length < 2) {
                return;
            }
            timer = window.setInterval(function() {
                activate(root, parseInt(root.dataset.slideshowIndex || '0', 10) + 1, 'forward');
            }, Math.max(1000, parseInt(payload.delay || 7000, 10)));
        };
        root.addEventListener('mouseenter', stop);
        root.addEventListener('mouseleave', start);
        root.addEventListener('focusin', stop);
        root.addEventListener('focusout', start);
        start();
    };

    const findTargets = function() {
        const targets = [];
        selectors.forEach(function(selector) {
            document.querySelectorAll(selector).forEach(function(target) {
                if (targets.indexOf(target) === -1 && isVisibleBanner(target)) {
                    targets.push(target);
                }
            });
        });
        if (context === 'course') {
            const native = targets.find(function(target) {
                return target.classList.contains('local-course-banner-builder-native-course-banner');
            });
            return native ? [native] : targets.slice(0, 1);
        }
        return targets.slice(0, 1);
    };

    const install = function() {
        findTargets().forEach(function(target) {
            if (target.getAttribute(processedAttribute) === context &&
                target.querySelector(':scope > .local-course-banner-builder-slideshow')) {
                return;
            }
            buildSlideshow(target);
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', install, {once: true});
    } else {
        install();
    }
    window.setTimeout(install, 150);
    window.setTimeout(install, 650);
    window.setTimeout(install, 1400);
})();
JS;
    }

    /**
     * Build a small inline script that injects the native Moodle course banner.
     *
     * @param array $payload
     * @return string
     */
    protected static function get_course_banner_runtime_script(array $payload): string {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return '';
        }

        return <<<JS
(function() {
    const payload = {$json};
    if (!payload || !payload.courseid) {
        return;
    }

    const bannerClass = 'local-course-banner-builder-native-course-banner';

    const appendOverlays = function(banner) {
        if (!banner) {
            return;
        }

        let host = banner.querySelector('.local-course-banner-builder-fixed-overlays');
        if (!host) {
            host = document.createElement('div');
            host.className = 'local-course-banner-builder-fixed-overlays';
            host.setAttribute('aria-hidden', 'true');
            banner.appendChild(host);
        }

        host.replaceChildren();

        if (payload.border) {
            const border = document.createElement('div');
            border.className = 'local-course-banner-builder-fixed-border';
            border.setAttribute('aria-hidden', 'true');
            border.setAttribute('style', payload.border.wrapperstyle || payload.border.boxstyle || payload.border.style || '');

            [
                ['top', 'local-course-banner-builder-fixed-border-side local-course-banner-builder-fixed-border-side-top'],
                ['right', 'local-course-banner-builder-fixed-border-side local-course-banner-builder-fixed-border-side-right'],
                ['bottom', 'local-course-banner-builder-fixed-border-side local-course-banner-builder-fixed-border-side-bottom'],
                ['left', 'local-course-banner-builder-fixed-border-side local-course-banner-builder-fixed-border-side-left']
            ].forEach(function(entry) {
                const side = document.createElement('div');
                side.className = entry[1];
                side.setAttribute('aria-hidden', 'true');
                side.setAttribute('style', (payload.border.sidestyles && payload.border.sidestyles[entry[0]]) || '');
                border.appendChild(side);
            });

            ['top-left', 'top-right', 'bottom-right', 'bottom-left'].forEach(function(name) {
                const corner = document.createElement('div');
                corner.className = 'local-course-banner-builder-fixed-border-corner local-course-banner-builder-fixed-border-corner-' + name;
                corner.setAttribute('aria-hidden', 'true');
                border.appendChild(corner);
            });

            const hole = document.createElement('div');
            hole.className = 'local-course-banner-builder-fixed-border-hole';
            hole.setAttribute('aria-hidden', 'true');
            border.appendChild(hole);
            host.appendChild(border);
        }

        (payload.overlays || []).forEach(function(overlay) {
            const wrapper = document.createElement('div');
            wrapper.className = 'local-course-banner-builder-fixed-overlay';
            wrapper.setAttribute('aria-hidden', 'true');
            wrapper.setAttribute('style', overlay.wrapperstyle || '');

            const image = document.createElement('img');
            image.className = 'local-course-banner-builder-fixed-overlay-image';
            image.src = overlay.url;
            image.alt = '';
            image.loading = 'lazy';
            image.setAttribute('aria-hidden', 'true');
            image.setAttribute('style', overlay.imagestyle || '');
            wrapper.appendChild(image);
            host.appendChild(wrapper);
        });
    };

    const findAnchor = function() {
        const courseHeader = document.getElementById('course-header');
        if (courseHeader) {
            return {element: courseHeader, mode: 'append'};
        }

        const pageHeader = document.getElementById('page-header');
        if (pageHeader) {
            return {element: pageHeader, mode: 'afterend'};
        }

        const regionMain = document.getElementById('region-main');
        if (regionMain) {
            return {element: regionMain, mode: 'beforebegin'};
        }

        return null;
    };

    const inject = function() {
        if (document.querySelector('.' + bannerClass + '[data-course-banner-builder-native="1"]')) {
            appendOverlays(document.querySelector('.' + bannerClass + '[data-course-banner-builder-native="1"]'));
            return;
        }

        const anchor = findAnchor();
        if (!anchor || !anchor.element) {
            return false;
        }

        const banner = document.createElement('div');
        banner.className = 'local-course-banner-builder ' + bannerClass;
        banner.setAttribute('data-course-banner-builder-native', '1');
        banner.setAttribute('aria-hidden', 'true');

        if (payload.bannerurl) {
            banner.style.backgroundImage = 'url("' + payload.bannerurl + '")';
        }
        if (payload.usesdefaultimage) {
            banner.classList.add('local-course-banner-builder-native-course-banner--moodle-generated');
        }

        if (anchor.mode === 'append') {
            anchor.element.replaceChildren(banner);
        } else {
            anchor.element.insertAdjacentElement(anchor.mode, banner);
        }

        appendOverlays(banner);
        return true;
    };

    const schedule = function(delay) {
        window.setTimeout(inject, delay);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            inject();
            schedule(150);
            schedule(600);
            schedule(1200);
        }, {once: true});
    } else {
        inject();
        schedule(150);
        schedule(600);
        schedule(1200);
    }
})();
JS;
    }

    /**
     * Load the native Moodle course card thumbnail enhancer.
     *
     * @param \core\hook\output\before_footer_html_generation $hook
     * @return void
     */
    public static function before_footer_html_generation(
        \core\hook\output\before_footer_html_generation $hook
    ): void {
        global $PAGE;

        if (!self::can_render_banners()) {
            return;
        }

        $courseid = (int)($PAGE->course->id ?? 0);
        $iscourseview = self::is_course_view_page($PAGE);
        $iscoursebannerpage = self::is_course_banner_page($PAGE);
        $hascourselayers = $iscoursebannerpage && manager::course_has_applicable_banner_layers($PAGE->course);
        $coursecontext = $courseid > SITEID ? \context_course::instance($courseid, IGNORE_MISSING) : null;
        $hascustomcourseimage = $coursecontext && manager::course_has_custom_overview_image($coursecontext->id);
        $usedefaultimage = $iscoursebannerpage &&
            !$hascourselayers &&
            !$hascustomcourseimage &&
            manager::course_default_image_banners_enabled();
        $themehasbanner = manager::theme_seems_to_provide_course_banner();
        $serverpayload = self::get_native_course_banner_payload($PAGE);
        $allownativebanner = $iscoursebannerpage &&
            manager::is_course_banner_enabled() &&
            !$themehasbanner &&
            ($hascourselayers || $usedefaultimage) &&
            $serverpayload === null;

        $PAGE->requires->js_call_amd('local_course_banner_builder/coursecards', 'init', [
            $courseid > SITEID ? $courseid : 0,
            [
                'enabled' => manager::is_display_enabled(),
                'themeHasBanner' => $themehasbanner,
                'allowCourseHeaderOverlays' => manager::is_course_banner_enabled() &&
                    $iscoursebannerpage && $hascourselayers && $serverpayload === null,
                'allowNativeBanner' => $allownativebanner,
                'hasNativeBackground' => $allownativebanner ? manager::course_has_native_banner_background($PAGE->course) : false,
                'bannerFormat' => manager::get_course_banner_format(),
                'themeName' => (string)($PAGE->theme->name ?? ''),
            ],
        ]);

        $slideshowpayload = null;
        if ($iscoursebannerpage && manager::is_course_banner_enabled()) {
            $candidate = manager::get_course_slideshow_payload($PAGE->course);
            if (!empty($candidate['enabled']) && !empty($candidate['hasSlides']) &&
                ($hascourselayers || $themehasbanner || $serverpayload !== null)) {
                $slideshowpayload = $candidate;
            }
        } else if (self::is_site_banner_page($PAGE) && manager::is_site_banner_enabled()) {
            $candidate = manager::get_site_slideshow_payload();
            if (!empty($candidate['enabled']) && !empty($candidate['hasSlides'])) {
                $slideshowpayload = $candidate;
            }
        }

        if ($slideshowpayload !== null) {
            $hook->add_html(\html_writer::tag('style', self::get_course_banner_runtime_css()));
            $hook->add_html(\html_writer::tag('script', self::get_banner_slideshow_runtime_script($slideshowpayload)));
        }

        if ($serverpayload === null) {
            return;
        }
    }

    /**
     * Inject the native banner script near the top of the body for better reliability on Boost.
     *
     * @param \core\hook\output\before_standard_top_of_body_html_generation $hook
     * @return void
     */
    public static function before_standard_top_of_body_html_generation(
        \core\hook\output\before_standard_top_of_body_html_generation $hook
    ): void {
        global $PAGE;

        $payload = self::get_native_course_banner_payload($PAGE);
        if ($payload !== null) {
            $hook->add_html(\html_writer::tag('style', self::get_course_banner_runtime_css()));
            $hook->add_html(self::render_native_course_banner_html($payload));
            $hook->add_html(\html_writer::tag('script', self::get_native_course_banner_mount_script()));
            return;
        }

        $sitepayload = self::get_site_banner_payload($PAGE);
        if ($sitepayload === null) {
            return;
        }

        $hook->add_html(\html_writer::tag('style', self::get_course_banner_runtime_css()));
        $hook->add_html(self::render_site_banner_html($sitepayload));
        $hook->add_html(\html_writer::tag('script', self::get_site_banner_mount_script()));
    }

    /**
     * Sync the managed banner image after course creation.
     *
     * @param \core_course\hook\after_course_created $hook
     * @return void
     */
    public static function after_course_created(
        \core_course\hook\after_course_created $hook
    ): void {
        manager::sync_course_overview_image($hook->course);
    }

    /**
     * Sync the managed banner image after course update.
     *
     * @param \core_course\hook\after_course_updated $hook
     * @return void
     */
    public static function after_course_updated(
        \core_course\hook\after_course_updated $hook
    ): void {
        manager::sync_course_overview_image($hook->course);
    }
}
