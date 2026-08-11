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
 * Confirm deletion of all Course Banner Builder configuration.
 *
 * @package    local_course_banner_builder
 * @copyright  2026 Kevin Jarniac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$context = context_system::instance();
$url = new moodle_url('/local/course_banner_builder/admin_reset.php');
$returnurl = new moodle_url('/admin/settings.php', [
    'section' => 'local_course_banner_builder_settings',
]);

require_login();
require_capability('local/course_banner_builder:manage', $context);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('deleteallpluginsettings', 'local_course_banner_builder'));
$PAGE->set_heading(get_string('pluginname', 'local_course_banner_builder'));

if (optional_param('confirmdelete', 0, PARAM_BOOL)) {
    require_sesskey();
    \local_course_banner_builder\manager::delete_all_plugin_configuration();
    redirect($returnurl, get_string('allpluginsettingsdeleted', 'local_course_banner_builder'));
}

$confirmurl = new moodle_url($url, [
    'confirmdelete' => 1,
    'sesskey' => sesskey(),
]);
$continue = new single_button(
    $confirmurl,
    get_string('delete', 'moodle'),
    'post',
    single_button::BUTTON_DANGER
);

echo $OUTPUT->header();
echo $OUTPUT->confirm(
    get_string('deleteallpluginsettingsconfirm', 'local_course_banner_builder'),
    $continue,
    $returnurl,
    [
        'confirmtitle' => get_string('confirm', 'moodle'),
        'cancelstr' => get_string('cancel', 'moodle'),
    ]
);
echo $OUTPUT->footer();
