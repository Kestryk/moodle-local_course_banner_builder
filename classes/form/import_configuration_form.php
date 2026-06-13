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

namespace local_course_banner_builder\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use local_course_banner_builder\manager;
use moodleform;

/**
 * Course Banner Builder configuration import form.
 *
 * @package    local_course_banner_builder
 * @copyright  2026 Kevin Jarniac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_configuration_form extends moodleform {

    /**
     * Form definition.
     *
     * @return void
     */
    protected function definition(): void {
        $mform = $this->_form;

        $mform->addElement('hidden', 'importconfig', 1);
        $mform->setType('importconfig', PARAM_BOOL);

        $mform->addElement(
            'filepicker',
            'configarchive',
            get_string('importarchive', 'local_course_banner_builder'),
            null,
            [
                'accepted_types' => ['.zip'],
                'maxbytes' => get_max_upload_file_size(),
            ]
        );
        $mform->addRule('configarchive', get_string('required'), 'required', null, 'client');

        $mform->addElement(
            'advcheckbox',
            'replaceall',
            '',
            get_string('importconfigreplaceall', 'local_course_banner_builder')
        );
        $mform->setType('replaceall', PARAM_BOOL);

        $mform->addElement('html', \html_writer::tag(
            'h4',
            get_string('importoptions', 'local_course_banner_builder'),
            ['class' => 'h5 mt-3']
        ));
        foreach (manager::get_export_section_options() as $section => $label) {
            $elementname = self::get_section_element_name($section);
            $mform->addElement('advcheckbox', $elementname, '', $label);
            $mform->setDefault($elementname, 1);
            $mform->setType($elementname, PARAM_BOOL);
        }

        $mform->addElement(
            'advcheckbox',
            'importcreatecategories',
            '',
            get_string('importcreatecategories', 'local_course_banner_builder')
        );
        $mform->setDefault('importcreatecategories', 1);
        $mform->setType('importcreatecategories', PARAM_BOOL);

        $mform->addElement(
            'advcheckbox',
            'importcreatecustomfields',
            '',
            get_string('importcreatecustomfields', 'local_course_banner_builder')
        );
        $mform->setDefault('importcreatecustomfields', 1);
        $mform->setType('importcreatecustomfields', PARAM_BOOL);

        $mform->addElement('submit', 'submitbutton', get_string('importconfig', 'local_course_banner_builder'));
    }

    /**
     * Return selected import sections from submitted form data.
     *
     * @param \stdClass $data
     * @return array
     */
    public static function get_selected_sections(\stdClass $data): array {
        $sections = [];
        foreach (manager::get_export_section_options() as $section => $label) {
            $elementname = self::get_section_element_name($section);
            if (!empty($data->{$elementname})) {
                $sections[] = $section;
            }
        }
        return $sections;
    }

    /**
     * Validate import form data.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $hassection = false;
        foreach (array_keys(manager::get_export_section_options()) as $section) {
            if (!empty($data[self::get_section_element_name($section)])) {
                $hassection = true;
                break;
            }
        }
        if (!$hassection) {
            $firstsection = array_key_first(manager::get_export_section_options());
            if ($firstsection !== null) {
                $errors[self::get_section_element_name($firstsection)] = get_string('required');
            }
        }
        return $errors;
    }

    /**
     * Convert a section key into a stable form element name.
     *
     * @param string $section
     * @return string
     */
    protected static function get_section_element_name(string $section): string {
        return 'importsection_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $section);
    }
}
