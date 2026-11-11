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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_cohortbadges\local;

/**
 * Cohort membership data helper.
 *
 * @package    local_cohortbadges
 * @copyright  2026 MIA Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class membership_manager {
    /**
     * Get cohort memberships indexed by user id.
     *
     * @param int[] $userids User ids.
     * @return array<int, array<int, array<string, mixed>>>
     */
    public static function get_memberships(array $userids): array {
        global $DB;

        $userids = array_values(array_unique(array_filter(array_map('intval', $userids))));
        $result = [];
        foreach ($userids as $userid) {
            $result[$userid] = [];
        }

        if (!$userids) {
            return $result;
        }

        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'uid');
        $showhidden = (bool) get_config('local_cohortbadges', 'showhidden');
        $visibilitysql = $showhidden ? '' : ' AND c.visible = 1';
        $sql = "SELECT cm.id, cm.userid, c.id AS cohortid, c.contextid, c.name, c.idnumber, c.visible, c.component
                  FROM {cohort_members} cm
                  JOIN {cohort} c ON c.id = cm.cohortid
                 WHERE cm.userid {$insql}{$visibilitysql}
              ORDER BY c.name ASC, c.id ASC";

        $records = $DB->get_records_sql($sql, $params);
        foreach ($records as $record) {
            $context = \context::instance_by_id((int) $record->contextid, IGNORE_MISSING);
            $name = $context
                ? format_string($record->name, true, ['context' => $context])
                : format_string($record->name);

            $result[(int) $record->userid][] = [
                'id' => (int) $record->cohortid,
                'name' => $name,
                'idnumber' => (string) $record->idnumber,
                'visible' => (bool) $record->visible,
                'contextid' => (int) $record->contextid,
                'component' => (string) $record->component,
                'label' => self::format_label($name, (string) $record->idnumber),
            ];
        }

        return $result;
    }

    /**
     * Check profile viewing permissions without exposing other users' memberships.
     *
     * @param string $viewer Self or other.
     * @param \stdClass|null $course Course context when known.
     * @return bool
     */
    public static function can_view_profile_memberships(string $viewer, $course): bool {
        if ($viewer === 'self') {
            return true;
        }
        if ($course && !empty($course->id)) {
            $context = \context_course::instance((int) $course->id, IGNORE_MISSING);
            return $context && has_capability('local/cohortbadges:view', $context);
        }
        return has_capability('moodle/cohort:view', \context_system::instance());
    }

    /**
     * Format a cohort label according to plugin settings.
     *
     * @param string $name Cohort name.
     * @param string $idnumber Cohort idnumber.
     * @return string
     */
    public static function format_label(string $name, string $idnumber): string {
        $format = (string) get_config('local_cohortbadges', 'displayformat');
        if ($format === '') {
            $format = 'name';
        }

        if ($format === 'idnumber') {
            return $idnumber !== '' ? $idnumber : $name;
        }

        if ($format === 'nameandidnumber' && $idnumber !== '') {
            return get_string('nameandidnumberformat', 'local_cohortbadges', (object) [
                'name' => $name,
                'idnumber' => $idnumber,
            ]);
        }

        return $name;
    }
}
