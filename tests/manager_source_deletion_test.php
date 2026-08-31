<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Source-owned layer deletion regression tests.
 *
 * @package   local_course_banner_builder
 * @copyright 2026 Kevin J.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Covers the source boundary used by asynchronous layer deletion.
 */
final class local_course_banner_builder_manager_source_deletion_test extends advanced_testcase {
    /**
     * Insert a minimal layer record for one source.
     *
     * @param string $sourcekey
     * @param int $categoryid
     * @return int
     */
    private function create_layer(string $sourcekey, int $categoryid): int {
        global $DB;

        $now = time();
        return (int)$DB->insert_record('local_course_banner_builder_elements', (object)[
            'categoryid' => $categoryid,
            'sourcetype' => 'category',
            'sourcekey' => $sourcekey,
            'elementtype' => 'background_image',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    /**
     * Foreign and mixed-source ids must be rejected before any mutation.
     *
     * @covers ::delete_source_banner_element
     * @covers ::delete_source_banner_elements
     */
    public function test_source_boundary_rejects_foreign_and_mixed_ids_atomically(): void {
        global $DB;

        $this->resetAfterTest(true);
        $sourcea = (object)['sourcekey' => 'category:8101'];
        $sourceb = (object)['sourcekey' => 'category:8102'];
        $layera = $this->create_layer($sourcea->sourcekey, 8101);
        $layerb = $this->create_layer($sourceb->sourcekey, 8102);

        $this->assertFalse(
            \local_course_banner_builder\manager::delete_source_banner_element($sourcea, $layerb, false)
        );
        $this->assertTrue($DB->record_exists('local_course_banner_builder_elements', ['id' => $layerb]));

        $this->assertSame(
            0,
            \local_course_banner_builder\manager::delete_source_banner_elements($sourcea, [$layera, $layerb])
        );
        $this->assertTrue($DB->record_exists('local_course_banner_builder_elements', ['id' => $layera]));
        $this->assertTrue($DB->record_exists('local_course_banner_builder_elements', ['id' => $layerb]));

        $this->assertTrue(
            \local_course_banner_builder\manager::delete_source_banner_element($sourcea, $layera, false)
        );
        $this->assertFalse($DB->record_exists('local_course_banner_builder_elements', ['id' => $layera]));
        $this->assertTrue($DB->record_exists('local_course_banner_builder_elements', ['id' => $layerb]));
    }
}
