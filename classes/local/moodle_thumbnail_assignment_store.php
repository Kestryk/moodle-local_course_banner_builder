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
 * Moodle database implementation of the thumbnail-assignment store.
 *
 * @package    local_course_banner_builder
 * @copyright  2026 Kevin Jarniac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class moodle_thumbnail_assignment_store implements thumbnail_assignment_store {
    /** Assignment table name. */
    private const TABLE = 'local_course_banner_builder_course_thumb';

    /**
     * {@inheritDoc}
     */
    public function course_exists(int $courseid): bool {
        global $DB;

        return $DB->record_exists('course', ['id' => $courseid]);
    }

    /**
     * {@inheritDoc}
     */
    public function get_assignment(int $courseid): ?array {
        global $DB;

        $record = $DB->get_record(self::TABLE, ['courseid' => $courseid]);
        if ($record === false) {
            return null;
        }

        return [
            'mode' => (string)$record->mode,
            'assignedsourcekey' => (string)($record->assignedsourcekey ?? ''),
            'assignedcandidatekey' => (string)($record->assignedcandidatekey ?? ''),
            'assignedrevision' => (int)($record->assignedrevision ?? 0),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function save_assignment(int $courseid, array $assignment): void {
        global $DB;

        $now = time();
        $record = (object)[
            'courseid' => $courseid,
            'mode' => $assignment['mode'],
            'assignedsourcekey' => $assignment['assignedsourcekey'] ?: null,
            'assignedcandidatekey' => $assignment['assignedcandidatekey'] ?: null,
            'assignedrevision' => $assignment['assignedrevision'] ?: null,
            'timeassigned' => $now,
            'timemodified' => $now,
        ];
        $existing = $DB->get_record(self::TABLE, ['courseid' => $courseid]);
        if ($existing === false) {
            $DB->insert_record(self::TABLE, $record);
            return;
        }

        $sameassignment = (string)$existing->mode === $record->mode &&
            (string)($existing->assignedsourcekey ?? '') === (string)($record->assignedsourcekey ?? '') &&
            (string)($existing->assignedcandidatekey ?? '') === (string)($record->assignedcandidatekey ?? '');
        if ($sameassignment) {
            $record->timeassigned = (int)$existing->timeassigned;
        }
        $record->id = $existing->id;
        $DB->update_record(self::TABLE, $record);
    }

    /**
     * {@inheritDoc}
     */
    public function delete_assignment(int $courseid): void {
        global $DB;

        $DB->delete_records(self::TABLE, ['courseid' => $courseid]);
    }

    /**
     * {@inheritDoc}
     */
    public function transaction(callable $callback): mixed {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        try {
            $result = $callback();
            $transaction->allow_commit();
            return $result;
        } catch (\Throwable $exception) {
            $transaction->rollback($exception);
            throw $exception;
        }
    }
}
