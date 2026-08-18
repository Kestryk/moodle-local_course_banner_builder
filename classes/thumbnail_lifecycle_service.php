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

namespace local_course_banner_builder;

use local_course_banner_builder\local\thumbnail_assignment_store;
use local_course_banner_builder\local\thumbnail_course_lock;

defined('MOODLE_INTERNAL') || die();

/**
 * Transactional lifecycle kernel for one course-thumbnail assignment.
 *
 * Source discovery, permissions, rendering, files, GD, cache invalidation,
 * Transfer, and backup/restore are deliberately caller responsibilities.
 *
 * @package    local_course_banner_builder
 * @copyright  2026 Kevin Jarniac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class thumbnail_lifecycle_service {
    /** An administrator selected an eligible candidate. */
    public const REASON_EXPLICIT_SET = 'explicit_set';

    /** The course returned to automatic inheritance. */
    public const REASON_INHERIT_SET = 'inherit_set';

    /** CCB thumbnail inheritance was disabled for the course. */
    public const REASON_DISABLED_SET = 'disabled_set';

    /** The course assignment was deleted. */
    public const REASON_COURSE_DELETED = 'course_deleted';

    /** @var thumbnail_assignment_store Persistence boundary. */
    private thumbnail_assignment_store $store;

    /** @var thumbnail_course_lock Per-course concurrency boundary. */
    private thumbnail_course_lock $lock;

    /**
     * @param thumbnail_assignment_store $store
     * @param thumbnail_course_lock $lock
     */
    public function __construct(thumbnail_assignment_store $store, thumbnail_course_lock $lock) {
        $this->store = $store;
        $this->lock = $lock;
    }

    /**
     * Reconcile the persisted state against an already resolved source chain.
     *
     * @param int $courseid
     * @param array $sources Sources ordered from lowest to highest.
     * @return array Previous/current state, reason, and persistence change flag.
     */
    public function reconcile(int $courseid, array $sources): array {
        return $this->transition($courseid, function(?array $previous) use ($courseid, $sources): array {
            return thumbnail_assignment_resolver::resolve($courseid, $previous, $sources);
        });
    }

    /**
     * Persist one explicit eligible candidate.
     *
     * @param int $courseid
     * @param string $sourcekey
     * @param string $candidatekey
     * @param array $sources Sources ordered from lowest to highest.
     * @return array Previous/current state, reason, and persistence change flag.
     */
    public function set_explicit(
        int $courseid,
        string $sourcekey,
        string $candidatekey,
        array $sources
    ): array {
        $this->require_positive_courseid($courseid);
        $candidate = $this->find_explicit_candidate($sourcekey, $candidatekey, $sources);

        return $this->transition($courseid, static function() use ($sourcekey, $candidatekey, $candidate): array {
            return [
                'mode' => thumbnail_assignment_resolver::MODE_EXPLICIT,
                'assignedsourcekey' => trim($sourcekey),
                'assignedcandidatekey' => trim($candidatekey),
                'assignedrevision' => $candidate['revision'],
                'reason' => self::REASON_EXPLICIT_SET,
            ];
        });
    }

    /**
     * Return a course to automatic inheritance and resolve it immediately.
     *
     * @param int $courseid
     * @param array $sources Sources ordered from lowest to highest.
     * @return array Previous/current state, reason, and persistence change flag.
     */
    public function set_inherit(int $courseid, array $sources): array {
        return $this->transition($courseid, static function() use ($courseid, $sources): array {
            $resolved = thumbnail_assignment_resolver::resolve($courseid, null, $sources);
            $resolved['reason'] = self::REASON_INHERIT_SET;
            return $resolved;
        });
    }

    /**
     * Disable CCB thumbnails for one course without affecting its banner.
     *
     * @param int $courseid
     * @return array Previous/current state, reason, and persistence change flag.
     */
    public function set_disabled(int $courseid): array {
        return $this->transition($courseid, static function(): array {
            return [
                'mode' => thumbnail_assignment_resolver::MODE_DISABLED,
                'assignedsourcekey' => '',
                'assignedcandidatekey' => '',
                'assignedrevision' => 0,
                'reason' => self::REASON_DISABLED_SET,
            ];
        });
    }

    /**
     * Delete one course assignment idempotently after course deletion.
     *
     * @param int $courseid
     * @return array Previous/current state, reason, and persistence change flag.
     */
    public function delete_course_assignment(int $courseid): array {
        $this->require_positive_courseid($courseid);

        return $this->lock->execute($courseid, function() use ($courseid): array {
            return $this->store->transaction(function() use ($courseid): array {
                $previous = $this->normalise_assignment($this->store->get_assignment($courseid));
                if ($previous !== null) {
                    $this->store->delete_assignment($courseid);
                }

                return [
                    'previous' => $previous,
                    'current' => null,
                    'changed' => $previous !== null,
                    'reason' => self::REASON_COURSE_DELETED,
                ];
            });
        });
    }

    /**
     * Execute one lifecycle transition under the course lock and transaction.
     *
     * @param int $courseid
     * @param callable $resolver
     * @return array
     */
    private function transition(int $courseid, callable $resolver): array {
        $this->require_positive_courseid($courseid);

        return $this->lock->execute($courseid, function() use ($courseid, $resolver): array {
            return $this->store->transaction(function() use ($courseid, $resolver): array {
                if (!$this->store->course_exists($courseid)) {
                    throw new \invalid_parameter_exception('Unknown course id.');
                }

                $previous = $this->normalise_assignment($this->store->get_assignment($courseid));
                $resolved = $resolver($previous);
                $reason = (string)($resolved['reason'] ?? '');
                $current = $this->normalise_assignment($resolved);
                if ($current === null) {
                    throw new \coding_exception('A lifecycle transition must resolve to a state.');
                }

                $persistedcurrent = $this->persisted_form($current);
                $changed = $previous !== $persistedcurrent;
                if ($changed) {
                    if ($persistedcurrent === null) {
                        $this->store->delete_assignment($courseid);
                    } else {
                        $this->store->save_assignment($courseid, $persistedcurrent);
                    }
                }

                return [
                    'previous' => $previous,
                    'current' => $current,
                    'changed' => $changed,
                    'reason' => $reason,
                ];
            });
        });
    }

    /**
     * Find one uniquely identified eligible explicit candidate.
     *
     * @param string $sourcekey
     * @param string $candidatekey
     * @param array $sources
     * @return array
     */
    private function find_explicit_candidate(string $sourcekey, string $candidatekey, array $sources): array {
        $sourcekey = trim($sourcekey);
        $candidatekey = trim($candidatekey);
        if ($sourcekey === '' || $candidatekey === '') {
            throw new \invalid_parameter_exception('Explicit source and candidate keys are required.');
        }

        $matches = [];
        foreach ($sources as $source) {
            if (trim((string)($source['sourcekey'] ?? '')) !== $sourcekey) {
                continue;
            }
            foreach (($source['candidates'] ?? []) as $candidate) {
                if (trim((string)($candidate['candidatekey'] ?? '')) !== $candidatekey ||
                        empty($candidate['isenabled']) || ($candidate['eligible'] ?? false) !== true) {
                    continue;
                }
                $matches[] = [
                    'revision' => max(1, (int)($candidate['revision'] ?? 1)),
                ];
            }
        }

        if (count($matches) !== 1) {
            throw new \invalid_parameter_exception('Explicit thumbnail candidate is missing or ambiguous.');
        }

        return $matches[0];
    }

    /**
     * Normalize one stored or resolved assignment.
     *
     * @param array|null $assignment
     * @return array|null
     */
    private function normalise_assignment(?array $assignment): ?array {
        if ($assignment === null) {
            return null;
        }

        return [
            'mode' => (string)($assignment['mode'] ?? thumbnail_assignment_resolver::MODE_INHERIT),
            'assignedsourcekey' => trim((string)($assignment['assignedsourcekey'] ?? '')),
            'assignedcandidatekey' => trim((string)($assignment['assignedcandidatekey'] ?? '')),
            'assignedrevision' => max(0, (int)($assignment['assignedrevision'] ?? 0)),
        ];
    }

    /**
     * Convert unresolved inheritance to the canonical absent-row form.
     *
     * @param array $assignment
     * @return array|null
     */
    private function persisted_form(array $assignment): ?array {
        if ($assignment['mode'] === thumbnail_assignment_resolver::MODE_INHERIT &&
                $assignment['assignedsourcekey'] === '' && $assignment['assignedcandidatekey'] === '') {
            return null;
        }

        return $assignment;
    }

    /**
     * Require a usable Moodle course id without accessing external state.
     *
     * @param int $courseid
     */
    private function require_positive_courseid(int $courseid): void {
        if ($courseid <= 0) {
            throw new \invalid_parameter_exception('Course id must be positive.');
        }
    }
}
