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

defined('MOODLE_INTERNAL') || die();

/**
 * Persistence boundary for one course-thumbnail assignment.
 *
 * @package    local_course_banner_builder
 * @copyright  2026 Kevin Jarniac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface thumbnail_assignment_store {
    /**
     * Check whether the owning Moodle course exists.
     *
     * @param int $courseid
     * @return bool
     */
    public function course_exists(int $courseid): bool;

    /**
     * Read the persisted assignment for one course.
     *
     * @param int $courseid
     * @return array|null
     */
    public function get_assignment(int $courseid): ?array;

    /**
     * Insert or replace the persisted assignment for one course.
     *
     * @param int $courseid
     * @param array $assignment
     */
    public function save_assignment(int $courseid, array $assignment): void;

    /**
     * Delete the persisted assignment if one exists.
     *
     * @param int $courseid
     */
    public function delete_assignment(int $courseid): void;

    /**
     * Execute one callback in a delegated Moodle transaction.
     *
     * @param callable $callback
     * @return mixed
     */
    public function transaction(callable $callback): mixed;
}
