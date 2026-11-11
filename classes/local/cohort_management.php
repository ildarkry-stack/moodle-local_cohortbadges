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
 * Permission-checked cohort membership management.
 *
 * Changes are delegated to Moodle core so cohort events and synchronisation run normally.
 *
 * @package    local_cohortbadges
 * @copyright  2026 MIA Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class cohort_management {
    /**
     * Whether the optional write functionality has been enabled by an administrator.
     *
     * @return bool
     */
    public static function enabled(): bool {
        return (bool) get_config('local_cohortbadges', 'allowmanagement');
    }

    /**
     * Find contexts containing manually managed cohorts the current user can assign.
     *
     * @return int[]
     */
    public static function assignable_contextids(): array {
        global $DB;

        if (!self::enabled()) {
            return [];
        }

        if (
            !has_capability('moodle/cohort:assign', \context_system::instance())
            && !\core_course_category::has_capability_on_any('moodle/cohort:assign')
        ) {
            return [];
        }

        $contextids = $DB->get_fieldset_sql(
            "SELECT DISTINCT contextid
               FROM {cohort}
              WHERE component IS NULL OR component = ''"
        );
        $allowed = [];
        foreach ($contextids as $contextid) {
            $context = \context::instance_by_id((int) $contextid, IGNORE_MISSING);
            if ($context && has_capability('moodle/cohort:assign', $context)) {
                $allowed[] = (int) $contextid;
            }
        }
        return $allowed;
    }

    /**
     * Return a manually managed cohort after checking the core assignment capability.
     *
     * @param int $cohortid Cohort identifier.
     * @return \stdClass
     */
    public static function require_editable_cohort(int $cohortid): \stdClass {
        global $DB;

        if (!self::enabled()) {
            throw new \moodle_exception('managementdisabled', 'local_cohortbadges');
        }

        $cohort = $DB->get_record('cohort', ['id' => $cohortid], '*', MUST_EXIST);
        $context = \context::instance_by_id((int) $cohort->contextid, MUST_EXIST);
        require_capability('moodle/cohort:assign', $context);

        // Externally managed cohorts must be changed by their owning component.
        if (!empty($cohort->component)) {
            throw new \moodle_exception('externallymanaged', 'local_cohortbadges');
        }

        return $cohort;
    }

    /**
     * Modify an existing user's cohort membership using Moodle's cohort API.
     *
     * Only call from a sesskey-checked POST action. Authorization is rechecked here.
     *
     * @param int $userid Target Moodle user.
     * @param int $cohortid Target cohort.
     * @param string $action Either add or remove.
     * @return bool Whether membership changed.
     */
    public static function change_membership(int $userid, int $cohortid, string $action): bool {
        global $DB, $CFG;

        if ($action !== 'add' && $action !== 'remove') {
            throw new \invalid_parameter_exception('Invalid cohort action.');
        }

        self::require_editable_cohort($cohortid);
        $DB->get_record('user', ['id' => $userid, 'deleted' => 0], 'id', MUST_EXIST);

        require_once($CFG->dirroot . '/cohort/lib.php');
        $ismember = cohort_is_member($cohortid, $userid);
        if ($action === 'add' && !$ismember) {
            cohort_add_member($cohortid, $userid);
            return true;
        }
        if ($action === 'remove' && $ismember) {
            cohort_remove_member($cohortid, $userid);
            return true;
        }
        return false;
    }

    /**
     * List memberships that this user may edit.
     *
     * @param int $userid Target Moodle user.
     * @param int[] $contextids Allowed cohort contexts.
     * @return array
     */
    public static function editable_memberships(int $userid, array $contextids): array {
        global $DB;

        if (!$contextids) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED, 'ctx');
        $params['userid'] = $userid;
        return $DB->get_records_sql(
            "SELECT c.id, c.name, c.idnumber, c.contextid
               FROM {cohort} c
               JOIN {cohort_members} cm ON cm.cohortid = c.id
              WHERE cm.userid = :userid AND c.contextid {$insql}
                AND (c.component IS NULL OR c.component = '')
           ORDER BY c.name ASC, c.id ASC",
            $params
        );
    }

    /**
     * Search assignable cohorts that do not already contain the target user.
     *
     * @param int $userid Target user.
     * @param int[] $contextids Allowed cohort contexts.
     * @param string $search Cohort name, ID number, or numeric ID.
     * @param int $page Zero-based page.
     * @param int $perpage Page size.
     * @return array Array with total and cohorts.
     */
    public static function search_available(
        int $userid,
        array $contextids,
        string $search,
        int $page = 0,
        int $perpage = 20
    ): array {
        global $DB;

        $search = trim($search);
        if (!$contextids) {
            return ['total' => 0, 'cohorts' => []];
        }

        [$insql, $params] = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED, 'ctx');
        $params['userid'] = $userid;

        // An empty search deliberately lists the first page of available cohorts.
        $matching = '';
        if ($search !== '') {
            $like = '%' . $DB->sql_like_escape($search) . '%';
            $params['searchname'] = $like;
            $params['searchnumber'] = $like;
            $matching = ' AND (' .
                $DB->sql_like('c.name', ':searchname', false) . ' OR ' .
                $DB->sql_like('c.idnumber', ':searchnumber', false);
            if (ctype_digit($search)) {
                $matching .= ' OR c.id = :searchid';
                $params['searchid'] = (int) $search;
            }
            $matching .= ')';
        }

        $fromsql = " FROM {cohort} c
                    WHERE c.contextid {$insql}
                      AND (c.component IS NULL OR c.component = '')
                      {$matching}
                      AND NOT EXISTS (
                          SELECT 1 FROM {cohort_members} cm
                           WHERE cm.cohortid = c.id AND cm.userid = :userid
                      )";

        $total = $DB->count_records_sql('SELECT COUNT(1)' . $fromsql, $params);
        $records = $DB->get_records_sql(
            'SELECT c.id, c.name, c.idnumber, c.contextid' . $fromsql . ' ORDER BY c.name ASC, c.id ASC',
            $params,
            max(0, $page) * $perpage,
            $perpage
        );
        return ['total' => $total, 'cohorts' => $records];
    }
}
