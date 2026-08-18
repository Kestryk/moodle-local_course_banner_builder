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

use local_course_banner_builder\local\thumbnail_assignment_store;
use local_course_banner_builder\local\thumbnail_course_lock;
use local_course_banner_builder\thumbnail_assignment_resolver;
use local_course_banner_builder\thumbnail_lifecycle_service;

defined('MOODLE_INTERNAL') || die();

/**
 * Transactional thumbnail lifecycle tests using in-memory boundaries.
 *
 * @package    local_course_banner_builder
 * @copyright  2026 Kevin Jarniac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class local_course_banner_builder_thumbnail_lifecycle_service_test extends basic_testcase {
    /**
     * Build one eligible candidate.
     *
     * @param string $key
     * @param int $revision
     * @return array
     */
    private function candidate(string $key, int $revision = 1): array {
        return [
            'candidatekey' => $key,
            'sortorder' => 0,
            'revision' => $revision,
            'isenabled' => 1,
            'eligible' => true,
        ];
    }

    /**
     * Build the service and its observable in-memory boundaries.
     *
     * @param array|null $assignment
     * @return array
     */
    private function service(?array $assignment = null): array {
        $store = new local_course_banner_builder_test_thumbnail_store($assignment);
        $lock = new local_course_banner_builder_test_thumbnail_lock();
        return [new thumbnail_lifecycle_service($store, $lock), $store, $lock];
    }

    /**
     * A new inherited assignment is persisted once under lock and transaction.
     *
     * @covers \local_course_banner_builder\thumbnail_lifecycle_service::reconcile
     */
    public function test_reconcile_persists_first_automatic_assignment(): void {
        [$service, $store, $lock] = $this->service();

        $result = $service->reconcile(42, [
            ['sourcekey' => 'category:child', 'candidates' => [$this->candidate('one')]],
        ]);

        $this->assertTrue($result['changed']);
        $this->assertSame('category:child', $store->assignment['assignedsourcekey']);
        $this->assertSame(1, $store->savecount);
        $this->assertSame(1, $store->transactioncount);
        $this->assertSame([42], $lock->courseids);
    }

    /**
     * A new lower source does not replace a still-valid persisted assignment.
     *
     * @covers \local_course_banner_builder\thumbnail_lifecycle_service::reconcile
     */
    public function test_reconcile_preserves_valid_assignment_without_write(): void {
        [$service, $store] = $this->service([
            'mode' => thumbnail_assignment_resolver::MODE_INHERIT,
            'assignedsourcekey' => 'category:parent',
            'assignedcandidatekey' => 'parent-one',
            'assignedrevision' => 1,
        ]);

        $result = $service->reconcile(42, [
            ['sourcekey' => 'category:child', 'candidates' => [$this->candidate('child-new')]],
            ['sourcekey' => 'category:parent', 'candidates' => [$this->candidate('parent-one')]],
        ]);

        $this->assertFalse($result['changed']);
        $this->assertSame(0, $store->savecount);
        $this->assertSame(0, $store->deletecount);
    }

    /**
     * A revision change updates the same source and candidate.
     *
     * @covers \local_course_banner_builder\thumbnail_lifecycle_service::reconcile
     */
    public function test_reconcile_updates_only_changed_revision(): void {
        [$service, $store] = $this->service([
            'mode' => thumbnail_assignment_resolver::MODE_INHERIT,
            'assignedsourcekey' => 'category:parent',
            'assignedcandidatekey' => 'parent-one',
            'assignedrevision' => 1,
        ]);

        $result = $service->reconcile(42, [
            ['sourcekey' => 'category:parent', 'candidates' => [$this->candidate('parent-one', 2)]],
        ]);

        $this->assertTrue($result['changed']);
        $this->assertSame('parent-one', $result['current']['assignedcandidatekey']);
        $this->assertSame(2, $result['current']['assignedrevision']);
        $this->assertSame(1, $store->savecount);
    }

    /**
     * A missing explicit choice falls back to inherited automatic selection.
     *
     * @covers \local_course_banner_builder\thumbnail_lifecycle_service::reconcile
     */
    public function test_missing_explicit_choice_returns_to_inherit(): void {
        [$service, $store] = $this->service([
            'mode' => thumbnail_assignment_resolver::MODE_EXPLICIT,
            'assignedsourcekey' => 'category:deleted',
            'assignedcandidatekey' => 'deleted-one',
            'assignedrevision' => 1,
        ]);

        $result = $service->reconcile(42, [
            ['sourcekey' => 'category:parent', 'candidates' => [$this->candidate('parent-one')]],
        ]);

        $this->assertSame(thumbnail_assignment_resolver::MODE_INHERIT, $result['current']['mode']);
        $this->assertSame('parent-one', $store->assignment['assignedcandidatekey']);
        $this->assertSame(thumbnail_assignment_resolver::REASON_EXPLICIT_MISSING, $result['reason']);
    }

    /**
     * Explicit mode accepts exactly one eligible candidate.
     *
     * @covers \local_course_banner_builder\thumbnail_lifecycle_service::set_explicit
     */
    public function test_explicit_transition_rejects_ineligible_choice_before_lock(): void {
        [$service, $store, $lock] = $this->service();
        $candidate = $this->candidate('one');
        $candidate['eligible'] = false;

        try {
            $service->set_explicit(42, 'category:child', 'one', [
                ['sourcekey' => 'category:child', 'candidates' => [$candidate]],
            ]);
            $this->fail('An ineligible explicit choice must be rejected.');
        } catch (invalid_parameter_exception $exception) {
            $this->assertStringContainsString('missing or ambiguous', $exception->getMessage());
        }

        $this->assertSame([], $lock->courseids);
        $this->assertSame(0, $store->transactioncount);
        $this->assertNull($store->assignment);
    }

    /**
     * Explicit, disabled, and inherited user transitions persist canonical states.
     *
     * @covers \local_course_banner_builder\thumbnail_lifecycle_service::set_explicit
     * @covers \local_course_banner_builder\thumbnail_lifecycle_service::set_disabled
     * @covers \local_course_banner_builder\thumbnail_lifecycle_service::set_inherit
     */
    public function test_user_transitions_persist_canonical_states(): void {
        [$service, $store] = $this->service();
        $sources = [
            ['sourcekey' => 'category:child', 'candidates' => [$this->candidate('one', 3)]],
        ];

        $explicit = $service->set_explicit(42, 'category:child', 'one', $sources);
        $this->assertSame(thumbnail_assignment_resolver::MODE_EXPLICIT, $explicit['current']['mode']);
        $this->assertSame(3, $explicit['current']['assignedrevision']);

        $disabled = $service->set_disabled(42);
        $this->assertSame(thumbnail_assignment_resolver::MODE_DISABLED, $disabled['current']['mode']);
        $this->assertSame('', $store->assignment['assignedsourcekey']);

        $inherited = $service->set_inherit(42, $sources);
        $this->assertSame(thumbnail_assignment_resolver::MODE_INHERIT, $inherited['current']['mode']);
        $this->assertSame('one', $store->assignment['assignedcandidatekey']);
        $this->assertSame(3, $store->savecount);
    }

    /**
     * Unresolved inheritance uses the canonical absent-row state.
     *
     * @covers \local_course_banner_builder\thumbnail_lifecycle_service::set_inherit
     */
    public function test_unresolved_inheritance_deletes_existing_row(): void {
        [$service, $store] = $this->service([
            'mode' => thumbnail_assignment_resolver::MODE_DISABLED,
            'assignedsourcekey' => '',
            'assignedcandidatekey' => '',
            'assignedrevision' => 0,
        ]);

        $result = $service->set_inherit(42, []);

        $this->assertTrue($result['changed']);
        $this->assertSame(thumbnail_assignment_resolver::MODE_INHERIT, $result['current']['mode']);
        $this->assertNull($store->assignment);
        $this->assertSame(1, $store->deletecount);
    }

    /**
     * Course deletion cleanup is idempotent and does not require the course row.
     *
     * @covers \local_course_banner_builder\thumbnail_lifecycle_service::delete_course_assignment
     */
    public function test_course_deletion_cleanup_is_idempotent(): void {
        [$service, $store, $lock] = $this->service([
            'mode' => thumbnail_assignment_resolver::MODE_INHERIT,
            'assignedsourcekey' => 'category:parent',
            'assignedcandidatekey' => 'parent-one',
            'assignedrevision' => 1,
        ]);
        $store->courseexists = false;

        $first = $service->delete_course_assignment(42);
        $second = $service->delete_course_assignment(42);

        $this->assertTrue($first['changed']);
        $this->assertFalse($second['changed']);
        $this->assertSame(1, $store->deletecount);
        $this->assertSame([42, 42], $lock->courseids);
    }
}

/**
 * In-memory store for lifecycle unit tests.
 */
final class local_course_banner_builder_test_thumbnail_store implements thumbnail_assignment_store {
    /** @var array|null Current assignment. */
    public ?array $assignment;

    /** @var bool Whether the course exists. */
    public bool $courseexists = true;

    /** @var int Save call count. */
    public int $savecount = 0;

    /** @var int Delete call count. */
    public int $deletecount = 0;

    /** @var int Transaction call count. */
    public int $transactioncount = 0;

    /**
     * @param array|null $assignment
     */
    public function __construct(?array $assignment) {
        $this->assignment = $assignment;
    }

    /** {@inheritDoc} */
    public function course_exists(int $courseid): bool {
        return $this->courseexists;
    }

    /** {@inheritDoc} */
    public function get_assignment(int $courseid): ?array {
        return $this->assignment;
    }

    /** {@inheritDoc} */
    public function save_assignment(int $courseid, array $assignment): void {
        $this->assignment = $assignment;
        $this->savecount++;
    }

    /** {@inheritDoc} */
    public function delete_assignment(int $courseid): void {
        $this->assignment = null;
        $this->deletecount++;
    }

    /** {@inheritDoc} */
    public function transaction(callable $callback): mixed {
        $this->transactioncount++;
        return $callback();
    }
}

/**
 * In-memory lock for lifecycle unit tests.
 */
final class local_course_banner_builder_test_thumbnail_lock implements thumbnail_course_lock {
    /** @var array Course ids protected by the lock. */
    public array $courseids = [];

    /** {@inheritDoc} */
    public function execute(int $courseid, callable $callback): mixed {
        $this->courseids[] = $courseid;
        return $callback();
    }
}
