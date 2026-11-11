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
 * English strings for Cohort badges.
 *
 * @package    local_cohortbadges
 * @copyright  2026 MIA Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['addtocohort'] = 'Add to a cohort';
$string['allowmanagement_desc'] = 'Allow authorised staff to add or remove existing users from manually managed cohorts directly from their profiles. Disabled by default. Changes may affect cohort-synchronised course enrolments.';
$string['allowmanagement'] = 'Enable cohort membership management';
$string['backtoprofile'] = 'Back to user profile';
$string['cohortadded'] = 'Student added to the cohort.';
$string['cohortbadges:view'] = 'View user cohort membership information';
$string['cohortcoursescount'] = 'Courses using cohort sync: {$a}';
$string['cohortremoved'] = 'Student removed from the cohort.';
$string['confirmremove'] = 'Remove {$a->user} from cohort {$a->cohort}? Cohort synchronisation may revoke their course enrolments.';
$string['confirmremoveinline'] = 'Remove from cohort {cohort}? This may affect course enrolments.';
$string['currentmemberships'] = 'Current editable memberships';
$string['displayformat_desc'] = 'Choose what is displayed for each cohort membership.';
$string['displayformat'] = 'Badge text';
$string['enabled_desc'] = 'Show cohort membership information in the user profile.';
$string['enabled'] = 'Enable cohort membership display';
$string['externallymanaged'] = 'This cohort is managed by another component and cannot be edited here.';
$string['format_idnumber'] = 'Cohort ID number';
$string['format_name'] = 'Cohort name';
$string['format_nameandidnumber'] = 'Name and ID number';
$string['loading'] = 'Loading cohorts...';
$string['managecohorts'] = 'Manage cohort memberships';
$string['managementdisabled'] = 'Cohort membership management is disabled in plugin settings.';
$string['membershipupdated'] = 'Cohort membership updated.';
$string['nameandidnumberformat'] = '{$a->name} ({$a->idnumber})';
$string['noavailablecohorts'] = 'No matching assignable cohorts were found.';
$string['nocohortcourses'] = 'No accessible courses with cohort sync configured for this cohort.';
$string['noeditablememberships'] = 'No manually managed cohort memberships can be edited with your permissions.';
$string['pluginname'] = 'Cohort memberships in profile';
$string['privacy:metadata'] = 'The plugin reads existing Moodle cohort membership data and may change membership using Moodle core when enabled. It stores no personal data itself.';
$string['profileheading'] = 'Cohort memberships';
$string['removefromcohort'] = 'Remove from cohort {$a}';
$string['requestfailed'] = 'Could not update cohort memberships. Check your permissions and try again.';
$string['searchcohorts'] = 'Find a cohort by name, ID number, or numeric ID';
$string['searchplaceholder'] = 'Click to see available cohorts, or start typing';
$string['showcohortcourses_desc'] = 'List courses with cohort synchronisation enrolment instances for each displayed cohort. Only staff with cohort viewing/assignment rights and permission to configure cohort enrolment on the course can see those courses. Disabled instances are shown separately from active ones.';
$string['showcohortcourses'] = 'Show cohort sync courses in user profiles';
$string['showhidden_desc'] = 'If enabled, hidden cohorts are also displayed to users who have permission to view this information.';
$string['showhidden'] = 'Show hidden cohorts';
$string['showinprofile_desc'] = 'Show cohort memberships in the standard Moodle user profile.';
$string['showinprofile'] = 'Show in user profile';
$string['showmore'] = 'Show more cohorts';
$string['syncdisabled'] = 'Cohort sync instance disabled';
$string['syncenabled'] = 'Cohort sync instance enabled';
