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

defined('MOODLE_INTERNAL') || die();

/**
 * Pure resolver for persisted course-thumbnail assignments.
 *
 * The caller supplies applicable sources from lowest to highest and marks
 * candidates as eligible after validating their source and image definition.
 * This class performs no database, file, cache, GD, or Moodle rendering work.
 *
 * @package    local_course_banner_builder
 * @copyright  2026 Kevin Jarniac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class thumbnail_assignment_resolver {
    /** Course inherits one persisted automatic assignment. */
    public const MODE_INHERIT = 'inherit';

    /** Course uses one administrator-selected assignment. */
    public const MODE_EXPLICIT = 'explicit';

    /** Course does not publish a CCB thumbnail. */
    public const MODE_DISABLED = 'disabled';

    /** Existing assignment remains valid. */
    public const REASON_PRESERVED = 'preserved';

    /** An automatic candidate was selected and must be persisted. */
    public const REASON_ASSIGNED = 'assigned';

    /** An invalid explicit choice fell back to inheritance. */
    public const REASON_EXPLICIT_MISSING = 'explicit_missing';

    /** No eligible candidate is available. */
    public const REASON_UNRESOLVED = 'unresolved';

    /** CCB thumbnails are disabled for the course. */
    public const REASON_DISABLED = 'disabled';

    /**
     * Resolve one course assignment without side effects.
     *
     * Each source must contain `sourcekey` and `candidates`. Each candidate
     * must contain `candidatekey`, `sortorder`, `revision`, `isenabled`, and
     * an explicit boolean `eligible`. Sources must be ordered lowest first.
     *
     * @param int $courseid Stable Moodle course id used for deterministic selection.
     * @param array|null $assignment Existing persisted assignment, or null for an unassigned course.
     * @param array $sources Applicable source chain ordered from lowest to highest.
     * @return array Resolved mode, stable keys, revision, change flag, and reason.
     */
    public static function resolve(int $courseid, ?array $assignment, array $sources): array {
        $original = self::normalise_assignment($assignment);
        $mode = $original['mode'];

        if ($mode === self::MODE_DISABLED) {
            return self::result(
                self::MODE_DISABLED,
                '',
                '',
                0,
                self::REASON_DISABLED,
                $assignment
            );
        }

        $eligiblesources = self::normalise_sources($sources);
        $candidate = self::find_candidate(
            $eligiblesources,
            $original['assignedsourcekey'],
            $original['assignedcandidatekey']
        );

        if ($candidate !== null) {
            return self::result(
                $mode,
                $original['assignedsourcekey'],
                $original['assignedcandidatekey'],
                $candidate['revision'],
                self::REASON_PRESERVED,
                $assignment
            );
        }

        $reason = $mode === self::MODE_EXPLICIT ?
            self::REASON_EXPLICIT_MISSING : self::REASON_ASSIGNED;
        foreach ($eligiblesources as $source) {
            if (empty($source['candidates'])) {
                continue;
            }

            $selected = self::select_candidate($courseid, $source['sourcekey'], $source['candidates']);
            return self::result(
                self::MODE_INHERIT,
                $source['sourcekey'],
                $selected['candidatekey'],
                $selected['revision'],
                $reason,
                $assignment
            );
        }

        return self::result(
            self::MODE_INHERIT,
            '',
            '',
            0,
            $mode === self::MODE_EXPLICIT ? self::REASON_EXPLICIT_MISSING : self::REASON_UNRESOLVED,
            $assignment
        );
    }

    /**
     * Normalise an existing assignment without changing external state.
     *
     * @param array|null $assignment
     * @return array
     */
    private static function normalise_assignment(?array $assignment): array {
        $mode = (string)($assignment['mode'] ?? self::MODE_INHERIT);
        if (!in_array($mode, [self::MODE_INHERIT, self::MODE_EXPLICIT, self::MODE_DISABLED], true)) {
            $mode = self::MODE_INHERIT;
        }

        return [
            'mode' => $mode,
            'assignedsourcekey' => trim((string)($assignment['assignedsourcekey'] ?? '')),
            'assignedcandidatekey' => trim((string)($assignment['assignedcandidatekey'] ?? '')),
            'assignedrevision' => max(0, (int)($assignment['assignedrevision'] ?? 0)),
        ];
    }

    /**
     * Keep only explicitly eligible candidates while preserving source order.
     *
     * @param array $sources
     * @return array
     */
    private static function normalise_sources(array $sources): array {
        $normalised = [];
        $seensources = [];

        foreach ($sources as $source) {
            $sourcekey = trim((string)($source['sourcekey'] ?? ''));
            if ($sourcekey === '' || isset($seensources[$sourcekey])) {
                continue;
            }
            $seensources[$sourcekey] = true;

            $candidates = [];
            $seencandidates = [];
            foreach (($source['candidates'] ?? []) as $candidate) {
                $candidatekey = trim((string)($candidate['candidatekey'] ?? ''));
                if ($candidatekey === '' || isset($seencandidates[$candidatekey]) ||
                        empty($candidate['isenabled']) || ($candidate['eligible'] ?? false) !== true) {
                    continue;
                }
                $seencandidates[$candidatekey] = true;
                $candidates[] = [
                    'candidatekey' => $candidatekey,
                    'sortorder' => (int)($candidate['sortorder'] ?? 0),
                    'revision' => max(1, (int)($candidate['revision'] ?? 1)),
                ];
            }

            usort($candidates, static function(array $left, array $right): int {
                return [$left['sortorder'], $left['candidatekey']] <=> [$right['sortorder'], $right['candidatekey']];
            });
            $normalised[] = [
                'sourcekey' => $sourcekey,
                'candidates' => $candidates,
            ];
        }

        return $normalised;
    }

    /**
     * Find one persisted candidate in the current applicable source chain.
     *
     * @param array $sources
     * @param string $sourcekey
     * @param string $candidatekey
     * @return array|null
     */
    private static function find_candidate(array $sources, string $sourcekey, string $candidatekey): ?array {
        if ($sourcekey === '' || $candidatekey === '') {
            return null;
        }

        foreach ($sources as $source) {
            if ($source['sourcekey'] !== $sourcekey) {
                continue;
            }
            foreach ($source['candidates'] as $candidate) {
                if ($candidate['candidatekey'] === $candidatekey) {
                    return $candidate;
                }
            }
            return null;
        }

        return null;
    }

    /**
     * Select a stable candidate without relying on platform integer width.
     *
     * @param int $courseid
     * @param string $sourcekey
     * @param array $candidates
     * @return array
     */
    private static function select_candidate(int $courseid, string $sourcekey, array $candidates): array {
        $digest = hash('sha256', $courseid . "\0" . $sourcekey, true);
        $index = 0;
        $count = count($candidates);
        for ($position = 0; $position < 8; $position++) {
            $index = (($index * 256) + ord($digest[$position])) % $count;
        }

        return $candidates[$index];
    }

    /**
     * Build a stable resolver result and report whether persistence must change.
     *
     * @param string $mode
     * @param string $sourcekey
     * @param string $candidatekey
     * @param int $revision
     * @param string $reason
     * @param array|null $original
     * @return array
     */
    private static function result(
        string $mode,
        string $sourcekey,
        string $candidatekey,
        int $revision,
        string $reason,
        ?array $original
    ): array {
        $resolved = [
            'mode' => $mode,
            'assignedsourcekey' => $sourcekey,
            'assignedcandidatekey' => $candidatekey,
            'assignedrevision' => $revision,
        ];
        $stored = $original === null ? null : [
            'mode' => (string)($original['mode'] ?? ''),
            'assignedsourcekey' => trim((string)($original['assignedsourcekey'] ?? '')),
            'assignedcandidatekey' => trim((string)($original['assignedcandidatekey'] ?? '')),
            'assignedrevision' => max(0, (int)($original['assignedrevision'] ?? 0)),
        ];
        $resolved['changed'] = $stored === null ? $sourcekey !== '' : $stored !== $resolved;
        $resolved['reason'] = $reason;

        return $resolved;
    }
}
