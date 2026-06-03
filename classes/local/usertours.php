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

namespace local_course_banner_builder\local;

/**
 * User tour package installer.
 *
 * @package    local_course_banner_builder
 * @copyright  2026 Kevin Jarniac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class usertours {
    /** @var int Shipped tour version. */
    protected const TOUR_VERSION = 4;

    /** @var string[] Tour package files. */
    protected const TOUR_FILES = [
        'course_banner_builder_course_banners.json',
        'course_banner_builder_site_banner.json',
        'course_banner_builder_layer_modals.json',
        'course_banner_builder_site_layer_modals.json',
        'course_banner_builder_slideshow.json',
        'course_banner_builder_transfer.json',
        'course_banner_builder_settings.json',
    ];

    /**
     * Install or refresh the tours packaged with this plugin.
     *
     * @return void
     */
    public static function install_or_update(): void {
        global $CFG, $DB;

        $dbman = $DB->get_manager();
        $tourstable = new \xmldb_table('tool_usertours_tours');
        $stepstable = new \xmldb_table('tool_usertours_steps');
        if (
            !$dbman->table_exists($tourstable) ||
            !$dbman->table_exists($stepstable) ||
            !class_exists(\tool_usertours\manager::class)
        ) {
            return;
        }

        foreach (self::TOUR_FILES as $filename) {
            $filepath = $CFG->dirroot . '/local/course_banner_builder/tours/' . $filename;
            if (!is_readable($filepath)) {
                continue;
            }

            $tourjson = (string)file_get_contents($filepath);
            $tourconfig = json_decode($tourjson);
            if (empty($tourconfig->name)) {
                continue;
            }

            self::remove_existing_tour((string)$tourconfig->name, $filename);
            $tour = \tool_usertours\manager::import_tour_from_json($tourjson);
            $tour->set_config(\tool_usertours\manager::CONFIG_SHIPPED_TOUR, true);
            $tour->set_config(\tool_usertours\manager::CONFIG_SHIPPED_FILENAME, $filename);
            $tour->set_config(\tool_usertours\manager::CONFIG_SHIPPED_VERSION, self::TOUR_VERSION);

            if (defined('BEHAT_SITE_RUNNING') || (defined('PHPUNIT_TEST') && PHPUNIT_TEST)) {
                $tour->set_enabled(false);
            }

            $tour->persist();
        }
    }

    /**
     * Remove a previously installed plugin tour before importing its replacement.
     *
     * @param string $name Tour name.
     * @param string $filename Tour filename.
     * @return void
     */
    protected static function remove_existing_tour(string $name, string $filename): void {
        global $DB;

        $like = $DB->sql_like('configdata', ':filename', false, false);
        $params = [
            'name' => $name,
            'filename' => '%' . $DB->sql_like_escape($filename) . '%',
        ];
        $records = $DB->get_records_select(
            'tool_usertours_tours',
            'name = :name OR ' . $like,
            $params
        );

        foreach ($records as $record) {
            $tour = \tool_usertours\tour::load_from_record($record);
            $tour->remove();
        }
    }
}
