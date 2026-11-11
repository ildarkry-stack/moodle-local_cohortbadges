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

/**
 * AJAX endpoint for inline cohort management on the user profile page.
 *
 * @package    local_cohortbadges
 * @copyright  2026 MIA Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/user/lib.php');

use local_cohortbadges\local\cohort_management;
use local_cohortbadges\local\profile_display;

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new moodle_exception('invalidrequest', 'error');
    }
    require_login();
    require_sesskey();

    $userid = required_param('userid', PARAM_INT);
    $action = required_param('action', PARAM_ALPHA);
    $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
    $usercontext = context_user::instance($userid, MUST_EXIST);
    if (!user_can_view_profile($user, null, $usercontext)) {
        throw new moodle_exception('nopermissions', 'error');
    }
    if (!cohort_management::enabled()) {
        throw new moodle_exception('managementdisabled', 'local_cohortbadges');
    }

    $contextids = cohort_management::assignable_contextids();
    if (!$contextids) {
        throw new moodle_exception('nopermissions', 'error');
    }

    if ($action === 'search') {
        $search = core_text::substr(optional_param('search', '', PARAM_RAW_TRIMMED), 0, 100);
        $page = max(0, optional_param('page', 0, PARAM_INT));
        $result = cohort_management::search_available($userid, $contextids, $search, $page, 25);
        $items = [];
        foreach ($result['cohorts'] as $cohort) {
            $context = context::instance_by_id((int) $cohort->contextid, IGNORE_MISSING);
            $name = $context
                ? format_string($cohort->name, true, ['context' => $context])
                : format_string($cohort->name);
            $items[] = [
                'id' => (int) $cohort->id,
                'name' => trim(strip_tags($name)),
                'idnumber' => (string) $cohort->idnumber,
            ];
        }

        $response = [
            'items' => $items,
            'total' => (int) $result['total'],
            'hasmore' => ($page + 1) * 25 < (int) $result['total'],
        ];
    } else if ($action === 'add' || $action === 'remove') {
        $cohortid = required_param('cohortid', PARAM_INT);
        if ($action === 'remove' && !required_param('confirm', PARAM_BOOL)) {
            throw new moodle_exception('invalidrequest', 'error');
        }
        $changed = cohort_management::change_membership($userid, $cohortid, $action);
        $response = [
            'changed' => $changed,
            'html' => profile_display::render_editable_badges($userid),
        ];
    } else {
        throw new moodle_exception('invalidrequest', 'error');
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'error' => get_string('requestfailed', 'local_cohortbadges'),
    ], JSON_UNESCAPED_UNICODE);
}
