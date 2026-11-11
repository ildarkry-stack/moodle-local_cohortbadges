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

namespace local_cohortbadges;

use local_cohortbadges\local\profile_display;

/**
 * Test profile-only inline cohort membership rendering.
 *
 * @package    local_cohortbadges
 * @copyright  2026 MIA Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\local_cohortbadges\local\profile_display::class)]
final class profile_display_test extends \advanced_testcase {
    /**
     * Category cohort managers see their own editable cohort memberships.
     *
     * @return void
     */
    public function test_category_manager_can_see_editable_membership(): void {
        global $CFG;

        $this->resetAfterTest();
        set_config('allowmanagement', 1, 'local_cohortbadges');
        require_once($CFG->dirroot . '/cohort/lib.php');

        $category = $this->getDataGenerator()->create_category();
        $categorycontext = \context_coursecat::instance($category->id);
        $cohortid = cohort_add_cohort((object) [
            'name' => 'Faculty cohort',
            'contextid' => $categorycontext->id,
            'visible' => 0,
        ]);
        $student = $this->getDataGenerator()->create_user();
        cohort_add_member($cohortid, $student->id);

        $staff = $this->getDataGenerator()->create_user();
        $roleid = create_role('Scoped cohort manager', 'scopedcohortmanager', '');
        assign_capability('moodle/cohort:assign', CAP_ALLOW, $roleid, $categorycontext->id);
        role_assign($roleid, $staff->id, $categorycontext->id);
        $this->setUser($staff);
        $this->assertFalse(has_capability('moodle/cohort:view', \context_system::instance()));

        $html = profile_display::render_editable_badges($student->id);
        $this->assertStringContainsString('Faculty cohort', $html);
        $this->assertStringContainsString('data-action="remove"', $html);
    }
}
