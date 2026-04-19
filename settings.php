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

/**
 * Plugin settings.
 *
 * @package    local_course_banner_builder
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig || has_capability('local/course_banner_builder:manage', context_system::instance())) {
    $ADMIN->add('localplugins', new admin_category(
        'local_course_banner_builder',
        get_string('pluginname', 'local_course_banner_builder')
    ));

    $settings = new admin_settingpage(
        'local_course_banner_builder_settings',
        get_string('settings', 'local_course_banner_builder'),
        'local/course_banner_builder:manage'
    );
    $settings->add(new admin_setting_configmultiselect(
        'local_course_banner_builder/enabledcustomfields',
        get_string('enabledcustomfields', 'local_course_banner_builder'),
        get_string('enabledcustomfields_desc', 'local_course_banner_builder'),
        [],
        \local_course_banner_builder\manager::get_course_customfield_options()
    ));
    $ADMIN->add('local_course_banner_builder', $settings);

    $ADMIN->add('local_course_banner_builder', new admin_externalpage(
        'local_course_banner_builder_manage',
        get_string('managebanners', 'local_course_banner_builder'),
        new moodle_url('/local/course_banner_builder/admin_manage.php'),
        'local/course_banner_builder:manage'
    ));

    $ADMIN->add('local_course_banner_builder', new admin_externalpage(
        'local_course_banner_builder_transfer',
        get_string('transferconfig', 'local_course_banner_builder'),
        new moodle_url('/local/course_banner_builder/admin_transfer.php'),
        'local/course_banner_builder:manage'
    ));
}
