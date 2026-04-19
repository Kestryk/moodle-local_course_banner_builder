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
 * Upgrade steps.
 *
 * @package    local_course_banner_builder
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the plugin.
 *
 * @param int $oldversion old plugin version
 * @return bool
 */
function xmldb_local_course_banner_builder_upgrade(int $oldversion): bool {
    global $CFG, $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026041701) {
        upgrade_plugin_savepoint(true, 2026041701, 'local', 'course_banner_builder');
    }

    if ($oldversion < 2026041702) {
        upgrade_plugin_savepoint(true, 2026041702, 'local', 'course_banner_builder');
    }

    if ($oldversion < 2026041703) {
        upgrade_plugin_savepoint(true, 2026041703, 'local', 'course_banner_builder');
    }

    if ($oldversion < 2026041704) {
        require_once($CFG->dirroot . '/local/course_banner_builder/lib.php');
        \local_course_banner_builder\manager::sync_all_courses_from_category_banners();
        upgrade_plugin_savepoint(true, 2026041704, 'local', 'course_banner_builder');
    }

    if ($oldversion < 2026041705) {
        require_once($CFG->dirroot . '/local/course_banner_builder/lib.php');
        \local_course_banner_builder\manager::sync_all_courses_from_category_banners();
        upgrade_plugin_savepoint(true, 2026041705, 'local', 'course_banner_builder');
    }

    if ($oldversion < 2026041706) {
        $table = new xmldb_table('local_course_banner_elements');

        $field = new xmldb_field('name', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'elementtype');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'name');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $index = new xmldb_index('category-element', XMLDB_INDEX_UNIQUE, ['categoryid', 'elementtype']);
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        $index = new xmldb_index('category-sort', XMLDB_INDEX_NOTUNIQUE, ['categoryid', 'sortorder']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $DB->execute("
            UPDATE {local_course_banner_elements}
               SET sortorder = id
             WHERE sortorder = 0 OR sortorder IS NULL
        ");

        require_once($CFG->dirroot . '/local/course_banner_builder/lib.php');
        \local_course_banner_builder\manager::sync_all_courses_from_category_banners();
        upgrade_plugin_savepoint(true, 2026041706, 'local', 'course_banner_builder');
    }

    if ($oldversion < 2026041800) {
        $table = new xmldb_table('local_course_banner_order');
        $field = new xmldb_field('compositionmode', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null,
            'cumulative', 'coursecustomfieldid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $DB->set_field('local_course_banner_order', 'compositionmode', 'cumulative', ['compositionmode' => null]);

        require_once($CFG->dirroot . '/local/course_banner_builder/lib.php');
        \local_course_banner_builder\manager::sync_all_courses_from_category_banners();
        upgrade_plugin_savepoint(true, 2026041800, 'local', 'course_banner_builder');
    }

    if ($oldversion < 2026041801) {
        $table = new xmldb_table('local_course_banner_order');

        $field = new xmldb_field('fitmode', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null,
            'bannerfit', 'compositionmode');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('fitapplyscope', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null,
            'self', 'fitmode');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        require_once($CFG->dirroot . '/local/course_banner_builder/lib.php');
        \local_course_banner_builder\manager::sync_all_courses_from_category_banners();
        upgrade_plugin_savepoint(true, 2026041801, 'local', 'course_banner_builder');
    }

    if ($oldversion < 2026041802) {
        $table = new xmldb_table('local_course_banner_elements');
        $field = new xmldb_field('fitmodeoverride', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'sortorder');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        require_once($CFG->dirroot . '/local/course_banner_builder/lib.php');
        \local_course_banner_builder\manager::sync_all_courses_from_category_banners();
        upgrade_plugin_savepoint(true, 2026041802, 'local', 'course_banner_builder');
    }

    return true;
}
