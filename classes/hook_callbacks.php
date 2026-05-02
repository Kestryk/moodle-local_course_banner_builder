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

defined('MOODLE_INTERNAL') || die();

/**
 * Output hook callbacks.
 *
 * @package    local_course_banner_builder
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Build the native banner payload for the current course page.
     *
     * @param \moodle_page $page
     * @return array|null
     */
    protected static function get_native_course_banner_payload(\moodle_page $page): ?array {
        $courseid = (int)($page->course->id ?? 0);
        $iscourseview = self::is_course_view_page($page);
        $hascourselayers = $iscourseview && manager::course_has_applicable_banner_layers($page->course);
        $themehasbanner = manager::theme_seems_to_provide_course_banner();
        $allownativebanner = $iscourseview &&
            manager::is_display_enabled() &&
            !$themehasbanner &&
            $hascourselayers;

        if (!$allownativebanner) {
            return null;
        }

        $hasbackground = manager::course_has_native_banner_background($page->course);
        $overlays = manager::get_course_header_image_overlays($page->course);
        $border = manager::get_course_header_border_overlay($courseid);
        if (!$hasbackground && empty($overlays) && empty($border)) {
            return null;
        }

        $bannerurl = $hasbackground ? manager::get_course_banner_image_url($courseid) : null;
        return [
            'courseid' => $courseid,
            'bannerurl' => $bannerurl ? $bannerurl->out(false) : '',
            'overlays' => $overlays,
            'border' => $border,
            'themehasbanner' => $themehasbanner,
            'allownativebanner' => $allownativebanner,
            'hascourselayers' => $hascourselayers,
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
            '.local-course-banner-builder {',
            '    position: relative;',
            '    width: 100%;',
            '    min-height: 180px;',
            '    overflow: hidden;',
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
            '.local-course-banner-builder-native-course-banner {',
            '    width: min(100%, 1320px);',
            '    aspect-ratio: 4 / 1;',
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
            '    background-image: radial-gradient(circle at 100% 100%, rgba(0, 0, 0, 0) var(--local-course-banner-builder-preview-top-left-radius, 0), var(--local-course-banner-builder-preview-color-transparent, transparent) var(--local-course-banner-builder-preview-top-left-radius, 0), var(--local-course-banner-builder-preview-color-solid, transparent) var(--local-course-banner-builder-preview-top-left-fade-start, var(--local-course-banner-builder-preview-top-left-corner-size, 100%)), var(--local-course-banner-builder-preview-color-solid, transparent) var(--local-course-banner-builder-preview-top-left-corner-size, 100%));',
            '}',
            '.local-course-banner-builder-fixed-border-corner-top-right {',
            '    right: 0;',
            '    left: auto;',
            '    width: var(--local-course-banner-builder-preview-top-right-corner-size, var(--local-course-banner-builder-preview-right-width, 0));',
            '    height: var(--local-course-banner-builder-preview-top-right-corner-size, var(--local-course-banner-builder-preview-top-width, 0));',
            '    background-image: radial-gradient(circle at 0 100%, rgba(0, 0, 0, 0) var(--local-course-banner-builder-preview-top-right-radius, 0), var(--local-course-banner-builder-preview-color-transparent, transparent) var(--local-course-banner-builder-preview-top-right-radius, 0), var(--local-course-banner-builder-preview-color-solid, transparent) var(--local-course-banner-builder-preview-top-right-fade-start, var(--local-course-banner-builder-preview-top-right-corner-size, 100%)), var(--local-course-banner-builder-preview-color-solid, transparent) var(--local-course-banner-builder-preview-top-right-corner-size, 100%));',
            '}',
            '.local-course-banner-builder-fixed-border-corner-bottom-right {',
            '    top: auto;',
            '    right: 0;',
            '    bottom: 0;',
            '    left: auto;',
            '    width: var(--local-course-banner-builder-preview-bottom-right-corner-size, var(--local-course-banner-builder-preview-right-width, 0));',
            '    height: var(--local-course-banner-builder-preview-bottom-right-corner-size, var(--local-course-banner-builder-preview-bottom-width, 0));',
            '    background-image: radial-gradient(circle at 0 0, rgba(0, 0, 0, 0) var(--local-course-banner-builder-preview-bottom-right-radius, 0), var(--local-course-banner-builder-preview-color-transparent, transparent) var(--local-course-banner-builder-preview-bottom-right-radius, 0), var(--local-course-banner-builder-preview-color-solid, transparent) var(--local-course-banner-builder-preview-bottom-right-fade-start, var(--local-course-banner-builder-preview-bottom-right-corner-size, 100%)), var(--local-course-banner-builder-preview-color-solid, transparent) var(--local-course-banner-builder-preview-bottom-right-corner-size, 100%));',
            '}',
            '.local-course-banner-builder-fixed-border-corner-bottom-left {',
            '    top: auto;',
            '    bottom: 0;',
            '    width: var(--local-course-banner-builder-preview-bottom-left-corner-size, var(--local-course-banner-builder-preview-left-width, 0));',
            '    height: var(--local-course-banner-builder-preview-bottom-left-corner-size, var(--local-course-banner-builder-preview-bottom-width, 0));',
            '    background-image: radial-gradient(circle at 100% 0, rgba(0, 0, 0, 0) var(--local-course-banner-builder-preview-bottom-left-radius, 0), var(--local-course-banner-builder-preview-color-transparent, transparent) var(--local-course-banner-builder-preview-bottom-left-radius, 0), var(--local-course-banner-builder-preview-color-solid, transparent) var(--local-course-banner-builder-preview-bottom-left-fade-start, var(--local-course-banner-builder-preview-bottom-left-corner-size, 100%)), var(--local-course-banner-builder-preview-color-solid, transparent) var(--local-course-banner-builder-preview-bottom-left-corner-size, 100%));',
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
     * Render the full native course banner HTML directly from PHP.
     *
     * @param array $payload
     * @return string
     */
    protected static function render_native_course_banner_html(array $payload): string {
        $content = '';
        $contentstyle = '';
        if (!empty($payload['bannerurl'])) {
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

        $banner = \html_writer::div(
            $content,
            'local-course-banner-builder local-course-banner-builder-native-course-banner',
            [
                'data-course-banner-builder-native' => '1',
                'aria-hidden' => 'true',
                'style' => $contentstyle,
            ]
        );

        return \html_writer::div($banner, 'header-maxwidth d-print-none local-course-banner-builder-native-shell', [
            'id' => 'local-course-banner-builder-native-shell',
        ]);
    }

    /**
     * Build a tiny script that moves the server-rendered native banner into the Boost course header.
     *
     * @return string
     */
    protected static function get_native_course_banner_mount_script(): string {
        return <<<JS
(function() {
    const mount = function() {
        const shell = document.getElementById('local-course-banner-builder-native-shell');
        if (!shell) {
            return;
        }

        const pageHeader = document.getElementById('page-header');
        if (pageHeader && shell.previousElementSibling !== pageHeader) {
            pageHeader.insertAdjacentElement('afterend', shell);
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

        $courseid = (int)($PAGE->course->id ?? 0);
        $iscourseview = self::is_course_view_page($PAGE);
        $hascourselayers = $iscourseview && manager::course_has_applicable_banner_layers($PAGE->course);
        $themehasbanner = manager::theme_seems_to_provide_course_banner();
        $serverpayload = self::get_native_course_banner_payload($PAGE);
        $allownativebanner = $iscourseview &&
            manager::is_display_enabled() &&
            !$themehasbanner &&
            $hascourselayers &&
            $serverpayload === null;

        $PAGE->requires->js_call_amd('local_course_banner_builder/coursecards', 'init', [
            $courseid > SITEID ? $courseid : 0,
            [
                'enabled' => manager::is_display_enabled(),
                'themeHasBanner' => $themehasbanner,
                'allowCourseHeaderOverlays' => $iscourseview && $hascourselayers && $serverpayload === null,
                'allowNativeBanner' => $allownativebanner,
                'hasNativeBackground' => $allownativebanner ? manager::course_has_native_banner_background($PAGE->course) : false,
                'themeName' => (string)($PAGE->theme->name ?? ''),
            ],
        ]);

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
        if ($payload === null) {
            return;
        }

        $hook->add_html(\html_writer::tag('style', self::get_course_banner_runtime_css()));
        $hook->add_html(self::render_native_course_banner_html($payload));
        $hook->add_html(\html_writer::tag('script', self::get_native_course_banner_mount_script()));
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
