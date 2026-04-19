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

/**
 * Category banner management form.
 *
 * @package    local_course_banner_builder
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manage_banner_form extends \moodleform {
    /**
     * Form definition.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;
        $selectedcategoryid = (int)($this->_customdata['selectedcategoryid'] ?? 0);
        $filemanageroptions = $this->_customdata['filemanageroptions'] ?? [];
        $elementid = (int)($this->_customdata['elementid'] ?? 0);
        $uploadguidance = (string)($this->_customdata['uploadguidance'] ?? '');

        $mform->addElement('hidden', 'categoryid', $selectedcategoryid);
        $mform->setType('categoryid', PARAM_INT);
        $mform->setDefault('categoryid', $selectedcategoryid);

        $mform->addElement('hidden', 'elementid', $elementid);
        $mform->setType('elementid', PARAM_INT);

        $mform->addElement('text', 'name', get_string('layername', 'local_course_banner_builder'));
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('text', 'sortorder', get_string('sortorder', 'local_course_banner_builder'));
        $mform->setType('sortorder', PARAM_INT);
        $mform->setDefault('sortorder', 0);

        if (!$elementid) {
            $mform->addElement('static', 'bulkuploadnote', '', get_string('bulkuploadnote', 'local_course_banner_builder'));
        }

        if ($uploadguidance !== '') {
            $guidancehtml = \html_writer::tag(
                'details',
                \html_writer::tag('summary', get_string('uploadguidancetitle', 'local_course_banner_builder')) . $uploadguidance,
                ['class' => 'local-course-banner-builder-upload-accordion']
            );
            $mform->addElement('static', 'uploadguidance', '', $guidancehtml);
        }

        $mform->addElement('advcheckbox', 'isenabled', get_string('enabled', 'local_course_banner_builder'));
        $mform->setDefault('isenabled', 0);

        $mform->addElement(
            'filemanager',
            'bannerimage_filemanager',
            get_string('bannerimage', 'local_course_banner_builder'),
            null,
            $filemanageroptions
        );
        $mform->addHelpButton('bannerimage_filemanager', 'bannerimage', 'local_course_banner_builder');

        $this->add_action_buttons(false, get_string('savebannerlayers', 'local_course_banner_builder'));
    }
}
