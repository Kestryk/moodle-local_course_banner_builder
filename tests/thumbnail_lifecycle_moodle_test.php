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

use local_course_banner_builder\local\moodle_thumbnail_assignment_store;
use local_course_banner_builder\local\moodle_thumbnail_course_lock;
use local_course_banner_builder\thumbnail_assignment_resolver;
use local_course_banner_builder\thumbnail_lifecycle_service;

defined('MOODLE_INTERNAL') || die();

/**
 * Moodle database and Lock API tests for course-thumbnail lifecycle writes.
 *
 * These tests intentionally use the production adapters. They must run only
 * in an initialised isolated PHPUnit database with the 0009-A tables present.
 * Cross-process contention is a separate pre-production gate; it must not be
 * substituted with an in-memory double.
 *
 * @package    local_course_banner_builder
 * @copyright  2026 Kevin Jarniac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class local_course_banner_builder_thumbnail_lifecycle_moodle_test extends advanced_testcase {
    /** Assignment table name. */
    private const TABLE = 'local_course_banner_builder_course_thumb';

    /**
     * Build one real Moodle-backed lifecycle service.
     *
     * @return thumbnail_lifecycle_service
     */
    private function service(): thumbnail_lifecycle_service {
        return new thumbnail_lifecycle_service(
            new moodle_thumbnail_assignment_store(),
            new moodle_thumbnail_course_lock(0)
        );
    }

    /**
     * Build one eligible candidate for the pure source-chain input.
     *
     * @param string $candidatekey
     * @param int $revision
     * @return array
     */
    private function candidate(string $candidatekey, int $revision = 1): array {
        return [
            'candidatekey' => $candidatekey,
            'sortorder' => 0,
            'revision' => $revision,
            'isenabled' => 1,
            'eligible' => true,
        ];
    }

    /**
     * Build a lowest-first source chain with one eligible candidate.
     *
     * @param string $sourcekey
     * @param string $candidatekey
     * @param int $revision
     * @return array
     */
    private function sources(string $sourcekey, string $candidatekey, int $revision = 1): array {
        return [[
            'sourcekey' => $sourcekey,
            'candidates' => [$this->candidate($candidatekey, $revision)],
        ]];
    }

    /**
     * The Moodle store creates, updates, and deletes the one row per course.
     *
     * @covers \local_course_banner_builder\local\moodle_thumbnail_assignment_store::save_assignment
     * @covers \local_course_banner_builder\local\moodle_thumbnail_assignment_store::delete_assignment
     */
    public function test_store_crud_uses_the_isolated_moodle_database(): void {
        global $DB;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $store = new moodle_thumbnail_assignment_store();
        $assignment = [
            'mode' => thumbnail_assignment_resolver::MODE_EXPLICIT,
            'assignedsourcekey' => 'category:child',
            'assignedcandidatekey' => 'candidate-one',
            'assignedrevision' => 1,
        ];

        $store->save_assignment($course->id, $assignment);
        $created = $DB->get_record(self::TABLE, ['courseid' => $course->id], '*', MUST_EXIST);
        $this->assertSame(thumbnail_assignment_resolver::MODE_EXPLICIT, $created->mode);
        $this->assertSame('category:child', $created->assignedsourcekey);
        $this->assertSame('candidate-one', $created->assignedcandidatekey);
        $this->assertSame(1, (int)$created->assignedrevision);

        $assignment['assignedrevision'] = 2;
        $store->save_assignment($course->id, $assignment);
        $updated = $DB->get_record(self::TABLE, ['courseid' => $course->id], '*', MUST_EXIST);
        $this->assertSame($created->id, $updated->id);
        $this->assertSame(2, (int)$updated->assignedrevision);
        $this->assertSame((int)$created->timeassigned, (int)$updated->timeassigned);

        $store->delete_assignment($course->id);
        $this->assertFalse($DB->record_exists(self::TABLE, ['courseid' => $course->id]));
    }

    /**
     * A database exception rolls back the adapter transaction and its write.
     *
     * @covers \local_course_banner_builder\local\moodle_thumbnail_assignment_store::transaction
     */
    public function test_store_transaction_rolls_back_after_exception(): void {
        global $DB;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $store = new moodle_thumbnail_assignment_store();

        try {
            $store->transaction(function() use ($store, $course): void {
                $store->save_assignment($course->id, [
                    'mode' => thumbnail_assignment_resolver::MODE_INHERIT,
                    'assignedsourcekey' => 'category:child',
                    'assignedcandidatekey' => 'candidate-one',
                    'assignedrevision' => 1,
                ]);
                throw new RuntimeException('Expected lifecycle transaction failure.');
            });
            $this->fail('The adapter transaction must rethrow its callback exception.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Expected lifecycle transaction failure.', $exception->getMessage());
        }

        $this->assertFalse($DB->record_exists(self::TABLE, ['courseid' => $course->id]));
    }

    /**
     * A no-op does not write, while a revision change preserves timeassigned.
     *
     * @covers \local_course_banner_builder\thumbnail_lifecycle_service::reconcile
     */
    public function test_reconcile_preserves_noop_timestamps_and_updates_revision(): void {
        global $DB;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $store = new moodle_thumbnail_assignment_store();
        $store->save_assignment($course->id, [
            'mode' => thumbnail_assignment_resolver::MODE_INHERIT,
            'assignedsourcekey' => 'category:child',
            'assignedcandidatekey' => 'candidate-one',
            'assignedrevision' => 1,
        ]);
        $DB->set_field(self::TABLE, 'timeassigned', 101, ['courseid' => $course->id]);
        $DB->set_field(self::TABLE, 'timemodified', 202, ['courseid' => $course->id]);

        $service = $this->service();
        $noop = $service->reconcile($course->id, $this->sources('category:child', 'candidate-one'));
        $afternoop = $DB->get_record(self::TABLE, ['courseid' => $course->id], '*', MUST_EXIST);
        $this->assertFalse($noop['changed']);
        $this->assertSame(101, (int)$afternoop->timeassigned);
        $this->assertSame(202, (int)$afternoop->timemodified);

        $revision = $service->reconcile($course->id, $this->sources('category:child', 'candidate-one', 2));
        $afterrevision = $DB->get_record(self::TABLE, ['courseid' => $course->id], '*', MUST_EXIST);
        $this->assertTrue($revision['changed']);
        $this->assertSame(2, (int)$afterrevision->assignedrevision);
        $this->assertSame(101, (int)$afterrevision->timeassigned);
        $this->assertGreaterThan(202, (int)$afterrevision->timemodified);
    }

    /**
     * Course assignment deletion stays idempotent against the real store.
     *
     * @covers \local_course_banner_builder\thumbnail_lifecycle_service::delete_course_assignment
     */
    public function test_course_assignment_deletion_is_idempotent(): void {
        global $DB;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $store = new moodle_thumbnail_assignment_store();
        $store->save_assignment($course->id, [
            'mode' => thumbnail_assignment_resolver::MODE_DISABLED,
            'assignedsourcekey' => '',
            'assignedcandidatekey' => '',
            'assignedrevision' => 0,
        ]);

        $service = $this->service();
        $first = $service->delete_course_assignment($course->id);
        $second = $service->delete_course_assignment($course->id);

        $this->assertTrue($first['changed']);
        $this->assertFalse($second['changed']);
        $this->assertFalse($DB->record_exists(self::TABLE, ['courseid' => $course->id]));
    }

    /**
     * Real Lock API resources differ by course and are released after a call.
     *
     * This is deliberately not a replacement for the separate multi-process
     * contention proof required in the pre-production matrix.
     *
     * @covers \local_course_banner_builder\local\moodle_thumbnail_course_lock::execute
     */
    public function test_course_lock_uses_distinct_resources_and_releases_them(): void {
        $this->resetAfterTest(true);
        $firstcourse = $this->getDataGenerator()->create_course();
        $secondcourse = $this->getDataGenerator()->create_course();
        $factory = \core\lock\lock_config::get_lock_factory('local_course_banner_builder_thumbnail');
        $heldlock = $factory->get_lock('course_' . $firstcourse->id, 0);
        $this->assertNotFalse($heldlock);

        try {
            $called = false;
            $this->service_lock()->execute($secondcourse->id, function() use (&$called): void {
                $called = true;
            });
            $this->assertTrue($called);
        } finally {
            $heldlock->release();
        }

        $calledafterrelease = false;
        $this->service_lock()->execute($firstcourse->id, function() use (&$calledafterrelease): void {
            $calledafterrelease = true;
        });
        $this->assertTrue($calledafterrelease);
    }

    /**
     * Build the concrete per-course lock adapter used by the lifecycle service.
     *
     * @return moodle_thumbnail_course_lock
     */
    private function service_lock(): moodle_thumbnail_course_lock {
        return new moodle_thumbnail_course_lock(0);
    }
}
