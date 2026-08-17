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

use local_course_banner_builder\thumbnail_assignment_resolver;

defined('MOODLE_INTERNAL') || die();

/**
 * Pure thumbnail-assignment resolver tests.
 *
 * @package    local_course_banner_builder
 * @copyright  2026 Kevin Jarniac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class local_course_banner_builder_thumbnail_assignment_resolver_test extends basic_testcase {
    /**
     * Build one eligible candidate.
     *
     * @param string $key
     * @param int $sortorder
     * @param int $revision
     * @return array
     */
    private function candidate(string $key, int $sortorder = 0, int $revision = 1): array {
        return [
            'candidatekey' => $key,
            'sortorder' => $sortorder,
            'revision' => $revision,
            'isenabled' => 1,
            'eligible' => true,
        ];
    }

    /**
     * The first eligible source in the lowest-first chain wins.
     *
     * @covers \local_course_banner_builder\thumbnail_assignment_resolver::resolve
     */
    public function test_lowest_eligible_source_wins(): void {
        $sources = [
            ['sourcekey' => 'category:child', 'candidates' => []],
            ['sourcekey' => 'category:parent', 'candidates' => [$this->candidate('parent-one')]],
            ['sourcekey' => 'category:root', 'candidates' => [$this->candidate('root-one')]],
        ];

        $result = thumbnail_assignment_resolver::resolve(42, null, $sources);

        $this->assertSame(thumbnail_assignment_resolver::MODE_INHERIT, $result['mode']);
        $this->assertSame('category:parent', $result['assignedsourcekey']);
        $this->assertSame('parent-one', $result['assignedcandidatekey']);
        $this->assertTrue($result['changed']);
    }

    /**
     * Candidate selection is deterministic even when input order changes.
     *
     * @covers \local_course_banner_builder\thumbnail_assignment_resolver::resolve
     */
    public function test_multiple_candidates_are_selected_deterministically(): void {
        $first = [
            ['sourcekey' => 'category:child', 'candidates' => [
                $this->candidate('candidate-c', 20),
                $this->candidate('candidate-a', 10),
                $this->candidate('candidate-b', 10),
            ]],
        ];
        $second = [
            ['sourcekey' => 'category:child', 'candidates' => array_reverse($first[0]['candidates'])],
        ];

        $resultone = thumbnail_assignment_resolver::resolve(314, null, $first);
        $resulttwo = thumbnail_assignment_resolver::resolve(314, null, $second);

        $this->assertSame($resultone['assignedcandidatekey'], $resulttwo['assignedcandidatekey']);
    }

    /**
     * A valid persisted automatic choice is not replaced by a new lower source.
     *
     * @covers \local_course_banner_builder\thumbnail_assignment_resolver::resolve
     */
    public function test_valid_inherited_assignment_is_not_silently_reassigned(): void {
        $assignment = [
            'mode' => thumbnail_assignment_resolver::MODE_INHERIT,
            'assignedsourcekey' => 'category:parent',
            'assignedcandidatekey' => 'parent-one',
            'assignedrevision' => 1,
        ];
        $sources = [
            ['sourcekey' => 'category:child', 'candidates' => [$this->candidate('child-new')]],
            ['sourcekey' => 'category:parent', 'candidates' => [$this->candidate('parent-one')]],
        ];

        $result = thumbnail_assignment_resolver::resolve(42, $assignment, $sources);

        $this->assertSame('category:parent', $result['assignedsourcekey']);
        $this->assertSame('parent-one', $result['assignedcandidatekey']);
        $this->assertFalse($result['changed']);
        $this->assertSame(thumbnail_assignment_resolver::REASON_PRESERVED, $result['reason']);
    }

    /**
     * A valid explicit choice remains explicit.
     *
     * @covers \local_course_banner_builder\thumbnail_assignment_resolver::resolve
     */
    public function test_valid_explicit_assignment_is_preserved(): void {
        $assignment = [
            'mode' => thumbnail_assignment_resolver::MODE_EXPLICIT,
            'assignedsourcekey' => 'category:parent',
            'assignedcandidatekey' => 'parent-two',
            'assignedrevision' => 3,
        ];
        $sources = [
            ['sourcekey' => 'category:child', 'candidates' => [$this->candidate('child-one')]],
            ['sourcekey' => 'category:parent', 'candidates' => [$this->candidate('parent-two', 0, 3)]],
        ];

        $result = thumbnail_assignment_resolver::resolve(42, $assignment, $sources);

        $this->assertSame(thumbnail_assignment_resolver::MODE_EXPLICIT, $result['mode']);
        $this->assertSame('parent-two', $result['assignedcandidatekey']);
        $this->assertFalse($result['changed']);
    }

    /**
     * A candidate revision change keeps the assignment and requests persistence.
     *
     * @covers \local_course_banner_builder\thumbnail_assignment_resolver::resolve
     */
    public function test_candidate_revision_change_keeps_assignment_and_marks_it_changed(): void {
        $assignment = [
            'mode' => thumbnail_assignment_resolver::MODE_INHERIT,
            'assignedsourcekey' => 'category:parent',
            'assignedcandidatekey' => 'parent-one',
            'assignedrevision' => 2,
        ];
        $sources = [
            ['sourcekey' => 'category:parent', 'candidates' => [$this->candidate('parent-one', 0, 3)]],
        ];

        $result = thumbnail_assignment_resolver::resolve(42, $assignment, $sources);

        $this->assertSame('category:parent', $result['assignedsourcekey']);
        $this->assertSame('parent-one', $result['assignedcandidatekey']);
        $this->assertSame(3, $result['assignedrevision']);
        $this->assertTrue($result['changed']);
        $this->assertSame(thumbnail_assignment_resolver::REASON_PRESERVED, $result['reason']);
    }

    /**
     * Disabled and ineligible candidates are skipped before inherited fallback.
     *
     * @covers \local_course_banner_builder\thumbnail_assignment_resolver::resolve
     */
    public function test_disabled_and_ineligible_candidates_are_ignored(): void {
        $disabled = $this->candidate('child-disabled');
        $disabled['isenabled'] = 0;
        $ineligible = $this->candidate('child-ineligible');
        $ineligible['eligible'] = false;
        $sources = [
            ['sourcekey' => 'category:child', 'candidates' => [$disabled, $ineligible]],
            ['sourcekey' => 'category:parent', 'candidates' => [$this->candidate('parent-fallback')]],
        ];

        $result = thumbnail_assignment_resolver::resolve(42, null, $sources);

        $this->assertSame('category:parent', $result['assignedsourcekey']);
        $this->assertSame('parent-fallback', $result['assignedcandidatekey']);
        $this->assertTrue($result['changed']);
        $this->assertSame(thumbnail_assignment_resolver::REASON_ASSIGNED, $result['reason']);
    }

    /**
     * A missing explicit choice becomes inherited and selects automatically.
     *
     * @covers \local_course_banner_builder\thumbnail_assignment_resolver::resolve
     */
    public function test_missing_explicit_assignment_returns_to_inherit(): void {
        $assignment = [
            'mode' => thumbnail_assignment_resolver::MODE_EXPLICIT,
            'assignedsourcekey' => 'category:deleted',
            'assignedcandidatekey' => 'deleted-one',
            'assignedrevision' => 1,
        ];
        $sources = [
            ['sourcekey' => 'category:parent', 'candidates' => [$this->candidate('parent-one')]],
        ];

        $result = thumbnail_assignment_resolver::resolve(42, $assignment, $sources);

        $this->assertSame(thumbnail_assignment_resolver::MODE_INHERIT, $result['mode']);
        $this->assertSame('category:parent', $result['assignedsourcekey']);
        $this->assertSame(thumbnail_assignment_resolver::REASON_EXPLICIT_MISSING, $result['reason']);
        $this->assertTrue($result['changed']);
    }

    /**
     * Disabled mode clears CCB assignment keys.
     *
     * @covers \local_course_banner_builder\thumbnail_assignment_resolver::resolve
     */
    public function test_disabled_mode_returns_no_ccb_assignment(): void {
        $assignment = [
            'mode' => thumbnail_assignment_resolver::MODE_DISABLED,
            'assignedsourcekey' => 'category:parent',
            'assignedcandidatekey' => 'parent-one',
            'assignedrevision' => 1,
        ];

        $result = thumbnail_assignment_resolver::resolve(42, $assignment, []);

        $this->assertSame('', $result['assignedsourcekey']);
        $this->assertSame('', $result['assignedcandidatekey']);
        $this->assertTrue($result['changed']);
        $this->assertSame(thumbnail_assignment_resolver::REASON_DISABLED, $result['reason']);
    }

    /**
     * Missing candidates leave inheritance unresolved without requesting a row.
     *
     * @covers \local_course_banner_builder\thumbnail_assignment_resolver::resolve
     */
    public function test_unassigned_course_without_candidates_stays_unresolved(): void {
        $result = thumbnail_assignment_resolver::resolve(42, null, []);

        $this->assertSame(thumbnail_assignment_resolver::MODE_INHERIT, $result['mode']);
        $this->assertSame('', $result['assignedsourcekey']);
        $this->assertFalse($result['changed']);
        $this->assertSame(thumbnail_assignment_resolver::REASON_UNRESOLVED, $result['reason']);
    }

    /**
     * An invalid persisted mode is normalized and marked for correction.
     *
     * @covers \local_course_banner_builder\thumbnail_assignment_resolver::resolve
     */
    public function test_invalid_persisted_mode_is_marked_as_changed(): void {
        $result = thumbnail_assignment_resolver::resolve(42, [
            'mode' => 'invalid',
            'assignedsourcekey' => '',
            'assignedcandidatekey' => '',
            'assignedrevision' => 0,
        ], []);

        $this->assertSame(thumbnail_assignment_resolver::MODE_INHERIT, $result['mode']);
        $this->assertTrue($result['changed']);
    }
}
