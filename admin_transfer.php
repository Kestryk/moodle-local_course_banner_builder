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
 * Export/import admin page.
 *
 * @package    local_course_banner_builder
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$action = optional_param('action', '', PARAM_ALPHA);
$replaceall = optional_param('replaceall', 0, PARAM_BOOL);

admin_externalpage_setup('local_course_banner_builder_transfer');
require_capability('local/course_banner_builder:manage', context_system::instance());

$url = new moodle_url('/local/course_banner_builder/admin_transfer.php');
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('exportimport', 'local_course_banner_builder'));
$PAGE->set_heading(get_string('exportimport', 'local_course_banner_builder'));

if ($action === 'export' && confirm_sesskey()) {
    $export = \local_course_banner_builder\manager::export_configuration();
    $filename = 'course_banner_builder_export_' . userdate(time(), '%Y%m%d_%H%M%S') . '.json';
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (optional_param('importconfig', 0, PARAM_BOOL) && confirm_sesskey()) {
    $configjson = required_param('configjson', PARAM_RAW);
    try {
        \local_course_banner_builder\manager::import_configuration($configjson, (bool)$replaceall);
        redirect($url, get_string('importedconfig', 'local_course_banner_builder'));
    } catch (Throwable $e) {
        \core\notification::error(get_string('invalidimportpayload', 'local_course_banner_builder'));
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('exportimport', 'local_course_banner_builder'));

$exporturl = new moodle_url('/local/course_banner_builder/admin_transfer.php', [
    'action' => 'export',
    'sesskey' => sesskey(),
]);
echo html_writer::tag('p', get_string('exportconfigdesc', 'local_course_banner_builder'));
echo html_writer::link($exporturl, get_string('exportconfig', 'local_course_banner_builder'), ['class' => 'btn btn-primary mb-4']);

echo $OUTPUT->heading(get_string('importconfig', 'local_course_banner_builder'), 3);
echo html_writer::tag('p', get_string('importconfigdesc', 'local_course_banner_builder'));
echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out(false)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'importconfig', 'value' => 1]);
echo html_writer::tag('textarea', '', [
    'name' => 'configjson',
    'rows' => 18,
    'class' => 'form-control mb-3',
]);
echo html_writer::checkbox('replaceall', 1, (bool)$replaceall, get_string('importconfigreplaceall', 'local_course_banner_builder'));
echo html_writer::empty_tag('br');
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'value' => get_string('importconfig', 'local_course_banner_builder'),
    'class' => 'btn btn-secondary mt-3',
]);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
