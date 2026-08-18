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
 * Moodle Lock API adapter for course-thumbnail lifecycle transitions.
 *
 * @package    local_course_banner_builder
 * @copyright  2026 Kevin Jarniac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class moodle_thumbnail_course_lock implements thumbnail_course_lock {
    /** @var int Maximum time to wait for another transition on the same course. */
    private int $timeout;

    /**
     * @param int $timeout Maximum lock wait in seconds.
     */
    public function __construct(int $timeout = 10) {
        $this->timeout = max(0, $timeout);
    }

    /**
     * {@inheritDoc}
     */
    public function execute(int $courseid, callable $callback): mixed {
        $factory = \core\lock\lock_config::get_lock_factory('local_course_banner_builder_thumbnail');
        $lock = $factory->get_lock('course_' . $courseid, $this->timeout);
        if ($lock === false) {
            throw new thumbnail_lock_exception('Course thumbnail lifecycle lock could not be acquired.');
        }

        try {
            return $callback();
        } finally {
            $lock->release();
        }
    }
}
