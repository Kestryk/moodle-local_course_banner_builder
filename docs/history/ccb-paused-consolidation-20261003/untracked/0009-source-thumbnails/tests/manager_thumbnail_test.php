<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

use local_course_banner_builder\manager;

/**
 * Covers the source-level native thumbnail rule contract.
 *
 * @package local_course_banner_builder
 */
final class local_course_banner_builder_manager_thumbnail_test extends advanced_testcase {
    /**
     * Existing sources remain eligible for native thumbnails after upgrade.
     */
    public function test_source_thumbnail_rule_defaults_to_enabled(): void {
        $source = (object) [
            'type' => manager::SOURCE_TYPE_CATEGORY,
            'categoryid' => 987654,
            'sourcekey' => manager::get_category_source_key(987654),
        ];

        $this->assertTrue(manager::source_thumbnail_enabled($source));
    }
}
