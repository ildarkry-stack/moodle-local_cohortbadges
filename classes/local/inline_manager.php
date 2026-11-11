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
 * Render the in-profile cohort picker and accessible search controls.
 *
 * @package    local_cohortbadges
 * @copyright  2026 MIA Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class inline_manager {
    /**
     * Render a compact manager that opens inside the user's profile.
     *
     * @param int $userid Profile owner identifier.
     * @return string
     */
    public static function render(int $userid): string {
        $inputid = 'local-cohortbadges-search-' . $userid;
        $listid = 'local-cohortbadges-list-' . $userid;
        $panelid = 'local-cohortbadges-panel-' . $userid;

        $toggle = \html_writer::tag('button', get_string('addtocohort', 'local_cohortbadges'), [
            'type' => 'button',
            'class' => 'btn btn-secondary btn-sm local-cohortbadges-manager-toggle',
            'data-role' => 'toggle',
            'aria-expanded' => 'false',
            'aria-controls' => $panelid,
        ]);
        $label = \html_writer::tag('label', get_string('searchcohorts', 'local_cohortbadges'), [
            'for' => $inputid,
        ]);
        $search = \html_writer::empty_tag('input', [
            'type' => 'search',
            'id' => $inputid,
            'class' => 'form-control',
            'autocomplete' => 'off',
            'placeholder' => get_string('searchplaceholder', 'local_cohortbadges'),
            'data-role' => 'search',
            'role' => 'combobox',
            'aria-autocomplete' => 'list',
            'aria-controls' => $listid,
            'aria-expanded' => 'false',
        ]);
        $results = \html_writer::tag('div', '', [
            'id' => $listid,
            'class' => 'local-cohortbadges-picker-results',
            'role' => 'listbox',
            'data-role' => 'results',
            'hidden' => 'hidden',
        ]);
        $more = \html_writer::tag('button', get_string('showmore', 'local_cohortbadges'), [
            'type' => 'button',
            'class' => 'btn btn-link btn-sm',
            'data-role' => 'more',
            'hidden' => 'hidden',
        ]);
        $message = \html_writer::tag('div', '', [
            'class' => 'local-cohortbadges-feedback',
            'data-role' => 'feedback',
            'role' => 'status',
            'aria-live' => 'polite',
        ]);
        $panel = \html_writer::tag('div', $label . $search . $results . $more . $message, [
            'class' => 'local-cohortbadges-inline-panel',
            'id' => $panelid,
            'data-role' => 'panel',
            'hidden' => 'hidden',
        ]);
        return \html_writer::div($toggle . $panel, 'local-cohortbadges-profile-manage');
    }
}
