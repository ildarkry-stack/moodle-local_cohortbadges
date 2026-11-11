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

use local_cohortbadges\local\cohort_management;

/**
 * Tests for optional, permission-checked cohort management.
 *
 * @package    local_cohortbadges
 * @copyright  2026 MIA Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\local_cohortbadges\local\cohort_management::class)]
final class cohort_management_test extends \advanced_testcase {
    /**
     * Management is opt-in, including for administrators.
     *
     * @return void
     */
    public function test_management_defaults_to_disabled(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        unset_config('allowmanagement', 'local_cohortbadges');

        $this->assertFalse(cohort_management::enabled());
        $this->assertSame([], cohort_management::assignable_contextids());
    }

    /**
     * Users can be added and removed once, with no duplicate membership events.
     *
     * @return void
     */
    public function test_admin_can_search_add_and_remove_members(): void {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('allowmanagement', 1, 'local_cohortbadges');
        require_once($CFG->dirroot . '/cohort/lib.php');

        $userid = $this->getDataGenerator()->create_user()->id;
        $contextid = \context_system::instance()->id;
        $cohortid = cohort_add_cohort((object) [
            'name' => 'Test maths students',
            'idnumber' => 'MATH-25',
            'contextid' => $contextid,
        ]);

        $this->assertContains($contextid, cohort_management::assignable_contextids());
        $found = cohort_management::search_available($userid, [$contextid], 'MATH');
        $this->assertSame(1, $found['total']);
        $this->assertArrayHasKey($cohortid, $found['cohorts']);

        $this->assertTrue(cohort_management::change_membership($userid, $cohortid, 'add'));
        $this->assertTrue(cohort_is_member($cohortid, $userid));
        $this->assertFalse(cohort_management::change_membership($userid, $cohortid, 'add'));
        $this->assertSame(1, count(cohort_management::editable_memberships($userid, [$contextid])));

        $found = cohort_management::search_available($userid, [$contextid], (string) $cohortid);
        $this->assertSame(0, $found['total']);

        $this->assertTrue(cohort_management::change_membership($userid, $cohortid, 'remove'));
        $this->assertFalse(cohort_is_member($cohortid, $userid));
        $this->assertFalse(cohort_management::change_membership($userid, $cohortid, 'remove'));
    }

    /**
     * Cohort assignment cannot be used by an ordinary authenticated user.
     *
     * @return void
     */
    public function test_regular_user_cannot_manage_cohorts(): void {
        global $CFG;

        $this->resetAfterTest();
        set_config('allowmanagement', 1, 'local_cohortbadges');
        require_once($CFG->dirroot . '/cohort/lib.php');

        $userid = $this->getDataGenerator()->create_user()->id;
        $staff = $this->getDataGenerator()->create_user();
        $cohortid = cohort_add_cohort((object) [
            'name' => 'Protected cohort',
            'contextid' => \context_system::instance()->id,
        ]);

        $this->setUser($staff);
        $this->assertSame([], cohort_management::assignable_contextids());
        $this->expectException(\required_capability_exception::class);
        cohort_management::change_membership($userid, $cohortid, 'add');
    }

    /**
     * Manually editing external component cohorts is prohibited.
     *
     * @return void
     */
    public function test_external_component_cohorts_are_not_editable(): void {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('allowmanagement', 1, 'local_cohortbadges');
        require_once($CFG->dirroot . '/cohort/lib.php');

        $userid = $this->getDataGenerator()->create_user()->id;
        $contextid = \context_system::instance()->id;
        $cohortid = cohort_add_cohort((object) [
            'name' => 'Synced external cohort',
            'component' => 'local_external',
            'contextid' => $contextid,
        ]);

        $found = cohort_management::search_available($userid, [$contextid], 'Synced');
        $this->assertSame(0, $found['total']);

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('externallymanaged', 'local_cohortbadges'));
        cohort_management::change_membership($userid, $cohortid, 'add');
    }

    /**
     * Assignment permission inherited from a category is sufficient for that category.
     *
     * @return void
     */
    public function test_category_assignment_role_is_respected(): void {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('allowmanagement', 1, 'local_cohortbadges');
        require_once($CFG->dirroot . '/cohort/lib.php');

        $category = $this->getDataGenerator()->create_category();
        $categorycontext = \context_coursecat::instance($category->id);
        $cohortid = cohort_add_cohort((object) [
            'name' => 'Faculty cohort',
            'contextid' => $categorycontext->id,
        ]);
        $target = $this->getDataGenerator()->create_user();
        $staff = $this->getDataGenerator()->create_user();
        $roleid = create_role('Cohort enrolment manager', 'cohortenrolmanager', '');
        assign_capability('moodle/cohort:assign', CAP_ALLOW, $roleid, $categorycontext->id);
        role_assign($roleid, $staff->id, $categorycontext->id);

        $this->setUser($staff);
        $this->assertContains($categorycontext->id, cohort_management::assignable_contextids());
        $this->assertTrue(cohort_management::change_membership($target->id, $cohortid, 'add'));
        $this->assertTrue(cohort_is_member($cohortid, $target->id));
    }

    /**
     * Available cohorts are listed on an empty search, excluding existing memberships.
     *
     * @return void
     */
    public function test_empty_search_lists_assignable_cohorts(): void {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('allowmanagement', 1, 'local_cohortbadges');
        require_once($CFG->dirroot . '/cohort/lib.php');

        $userid = $this->getDataGenerator()->create_user()->id;
        $contextid = \context_system::instance()->id;
        $first = cohort_add_cohort((object) ['name' => 'First cohort', 'contextid' => $contextid]);
        $second = cohort_add_cohort((object) ['name' => 'Second cohort', 'contextid' => $contextid]);
        cohort_add_member($first, $userid);

        $listed = cohort_management::search_available($userid, [$contextid], '');
        $this->assertSame(1, $listed['total']);
        $this->assertArrayHasKey($second, $listed['cohorts']);
        $this->assertArrayNotHasKey($first, $listed['cohorts']);
    }

    /**
     * Available cohorts can be paged without loading the whole site into the UI.
     *
     * @return void
     */
    public function test_empty_search_is_paginated(): void {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('allowmanagement', 1, 'local_cohortbadges');
        require_once($CFG->dirroot . '/cohort/lib.php');

        $userid = $this->getDataGenerator()->create_user()->id;
        $contextid = \context_system::instance()->id;
        for ($i = 0; $i < 27; $i++) {
            cohort_add_cohort((object) ['name' => 'Browse ' . $i, 'contextid' => $contextid]);
        }

        $firstpage = cohort_management::search_available($userid, [$contextid], '', 0, 25);
        $secondpage = cohort_management::search_available($userid, [$contextid], '', 1, 25);
        $this->assertSame(27, $firstpage['total']);
        $this->assertCount(25, $firstpage['cohorts']);
        $this->assertSame(27, $secondpage['total']);
        $this->assertCount(2, $secondpage['cohorts']);
        $this->assertSame([], array_intersect(array_keys($firstpage['cohorts']), array_keys($secondpage['cohorts'])));
    }
}
