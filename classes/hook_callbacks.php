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
     * Load the native Moodle course card thumbnail enhancer.
     *
     * @param \core\hook\output\before_footer_html_generation $hook
     * @return void
     */
    public static function before_footer_html_generation(
        \core\hook\output\before_footer_html_generation $hook
    ): void {
        global $PAGE;

        $PAGE->requires->js_call_amd('local_course_banner_builder/coursecards', 'init');
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
