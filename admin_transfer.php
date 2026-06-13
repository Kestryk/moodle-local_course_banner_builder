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
 * @copyright  2026 Kevin Jarniac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// phpcs:disable moodle.Files.LineLength.TooLong -- Form element attributes are clearer kept together.

$action = optional_param('action', '', PARAM_ALPHA);
$exportsections = optional_param_array('exportsections', [], PARAM_ALPHAEXT);
$exportincludecategories = optional_param('exportincludecategories', 1, PARAM_BOOL);
$exportincludecustomfields = optional_param('exportincludecustomfields', 1, PARAM_BOOL);

$url = new moodle_url('/local/course_banner_builder/admin_transfer.php');
require_login();
require_capability('local/course_banner_builder:manage', context_system::instance());
try {
    admin_externalpage_setup('local_course_banner_builder_transfer');
} catch (\moodle_exception $exception) {
    if ($exception->errorcode !== 'sectionerror') {
        throw $exception;
    }
    $PAGE->set_context(context_system::instance());
    $PAGE->set_url($url);
    $PAGE->set_pagelayout('admin');
    $PAGE->set_title(get_string('exportimport', 'local_course_banner_builder'));
    $PAGE->set_heading(get_string('pluginname', 'local_course_banner_builder'));
}

$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('exportimport', 'local_course_banner_builder'));
$PAGE->set_heading(get_string('exportimport', 'local_course_banner_builder'));
$PAGE->requires->css('/local/course_banner_builder/styles.css');

$importform = new \local_course_banner_builder\form\import_configuration_form($url);

if (optional_param('deleteallpluginsettings', 0, PARAM_BOOL) && confirm_sesskey()) {
    \local_course_banner_builder\manager::delete_all_plugin_configuration();
    redirect($url, get_string('allpluginsettingsdeleted', 'local_course_banner_builder'));
}

if ($action === 'export' && confirm_sesskey()) {
    $archivepath = \local_course_banner_builder\manager::create_configuration_export_zip($exportsections, [
        \local_course_banner_builder\manager::EXPORT_OPTION_INCLUDE_CATEGORIES => (bool)$exportincludecategories,
        \local_course_banner_builder\manager::EXPORT_OPTION_INCLUDE_CUSTOMFIELDS => (bool)$exportincludecustomfields,
    ]);
    $filename = 'course_banner_builder_export_' . userdate(time(), '%Y%m%d_%H%M%S') . '.zip';
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($archivepath));
    readfile($archivepath);
    @unlink($archivepath);
    exit;
}

if ($importdata = $importform->get_data()) {
    try {
        $archivepath = $importform->save_temp_file('configarchive');
        if (!$archivepath) {
            throw new coding_exception('Missing course banner builder archive.');
        }
        $filename = (string)$importform->get_new_filename('configarchive');
        if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'zip') {
            throw new coding_exception('Invalid course banner builder archive.');
        }
        \local_course_banner_builder\manager::import_configuration_archive(
            $archivepath,
            !empty($importdata->replaceall),
            \local_course_banner_builder\form\import_configuration_form::get_selected_sections($importdata),
            [
                \local_course_banner_builder\manager::IMPORT_OPTION_CREATE_CATEGORIES =>
                    !empty($importdata->importcreatecategories),
                \local_course_banner_builder\manager::IMPORT_OPTION_CREATE_CUSTOMFIELDS =>
                    !empty($importdata->importcreatecustomfields),
            ]
        );
        redirect($url, get_string('importedconfig', 'local_course_banner_builder'));
    } catch (Throwable $e) {
        \core\notification::error(get_string('invalidimportpayload', 'local_course_banner_builder'));
    }
}

echo $OUTPUT->header();
echo html_writer::start_div('local-course-banner-builder-admin local-course-banner-builder-admin--native');
echo html_writer::div(
    html_writer::link(
        new moodle_url('/local/course_banner_builder/admin_manage.php'),
        html_writer::tag('i', '', ['class' => 'fa fa-image me-2', 'aria-hidden' => 'true']) .
            html_writer::span(get_string('managecoursebannersquick', 'local_course_banner_builder')),
        ['class' => 'btn btn-outline-secondary local-course-banner-builder-dashed-action']
    ) .
    html_writer::link(
        new moodle_url('/local/course_banner_builder/admin_site.php'),
        html_writer::tag('i', '', ['class' => 'fa fa-desktop me-2', 'aria-hidden' => 'true']) .
            html_writer::span(get_string('managesitebannerquick', 'local_course_banner_builder')),
        ['class' => 'btn btn-outline-secondary local-course-banner-builder-dashed-action']
    ) .
    html_writer::link(
        new moodle_url('/local/course_banner_builder/admin_slideshow.php'),
        html_writer::tag('i', '', ['class' => 'fa fa-images me-2', 'aria-hidden' => 'true']) .
            html_writer::span(get_string('manageslideshowquick', 'local_course_banner_builder')),
        ['class' => 'btn btn-outline-secondary local-course-banner-builder-dashed-action']
    ) .
    html_writer::link(
        new moodle_url('/local/course_banner_builder/admin_manage.php', [
            'openformatmodal' => 1,
            'bannerformatcontext' => 'course',
        ]),
        html_writer::tag('i', '', ['class' => 'fa fa-columns me-2', 'aria-hidden' => 'true']) .
            html_writer::span(get_string('coursebannerformatbutton', 'local_course_banner_builder')),
        ['class' => 'btn btn-outline-secondary local-course-banner-builder-dashed-action']
    ) .
    html_writer::link(
        new moodle_url('/local/course_banner_builder/admin_site.php', [
            'openformatmodal' => 1,
            'bannerformatcontext' => 'site',
        ]),
        html_writer::tag('i', '', ['class' => 'fa fa-columns me-2', 'aria-hidden' => 'true']) .
            html_writer::span(get_string('sitebannerformatbutton', 'local_course_banner_builder')),
        ['class' => 'btn btn-outline-secondary local-course-banner-builder-dashed-action']
    ) .
    html_writer::tag(
        'form',
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]) .
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'deleteallpluginsettings', 'value' => 1]) .
        html_writer::tag(
            'button',
            html_writer::tag('i', '', ['class' => 'fa fa-trash-can me-2', 'aria-hidden' => 'true']) .
                html_writer::span(get_string('deleteallpluginsettings', 'local_course_banner_builder')),
            [
                'type' => 'submit',
                'class' => 'btn btn-outline-danger local-course-banner-builder-dashed-action local-course-banner-builder-admin-reset-button',
                'data-modal' => 'confirmation',
                'data-modal-title' => get_string('confirm', 'moodle'),
                'data-modal-content' => get_string('deleteallpluginsettingsconfirm', 'local_course_banner_builder'),
                'data-modal-yes-button' => get_string('delete', 'moodle'),
            ]
        ),
        [
            'method' => 'post',
            'action' => $url->out(false),
            'class' => 'd-inline local-course-banner-builder-admin-reset-form',
        ]
    ),
    'local-course-banner-builder-admin-switcher mb-3'
);
echo $OUTPUT->heading(get_string('exportimport', 'local_course_banner_builder'));

echo html_writer::tag('p', get_string('exportconfigdesc', 'local_course_banner_builder'));
echo $OUTPUT->heading(get_string('exportoptions', 'local_course_banner_builder'), 3);
echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out(false), 'class' => 'mb-4']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'export']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'exportsections[]', 'value' => '__submitted']);
foreach (\local_course_banner_builder\manager::get_export_section_options() as $section => $label) {
    echo html_writer::div(
        html_writer::checkbox('exportsections[]', $section, true, $label),
        'form-check mb-2'
    );
}
echo html_writer::div(
    html_writer::checkbox(
        'exportincludecategories',
        1,
        (bool)$exportincludecategories,
        get_string('exportincludecategories', 'local_course_banner_builder')
    ),
    'form-check mb-2'
);
echo html_writer::div(
    html_writer::checkbox(
        'exportincludecustomfields',
        1,
        (bool)$exportincludecustomfields,
        get_string('exportincludecustomfields', 'local_course_banner_builder')
    ),
    'form-check mb-2'
);
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'value' => get_string('exportconfig', 'local_course_banner_builder'),
    'class' => 'btn btn-primary mt-2',
]);
echo html_writer::end_tag('form');

echo $OUTPUT->heading(get_string('importconfig', 'local_course_banner_builder'), 3);
echo html_writer::tag('p', get_string('importconfigdesc', 'local_course_banner_builder'));
$importform->display();

echo html_writer::end_div();
echo $OUTPUT->footer();
