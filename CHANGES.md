# Change log

## 1.1.0-beta.4 - 2026-11-11

- Moved cohort membership controls directly into the user's standard Moodle profile; no separate management page opens for normal use.
- Added an inline available-cohorts picker that opens when the search field is focused and shows the first page without a search term.
- Added incremental search, paginated browsing, same-page addition and removal, inline removal confirmation and live membership refresh.
- Kept cohort-level permissions, session-key validation, server-side access checks and Moodle core cohort APIs.
- Retained the existing read-only course synchronisation list and all current settings.

## 1.1.0-beta.3 - 2026-10-19

- Show a read-only, collapsible list of courses that configure cohort sync for each cohort shown in the user profile.
- Report enabled and disabled enrolment instances, consolidating multiple instances per course.
- Protect course metadata with cohort-level view/assign and course-level enrol/cohort:config capabilities.
- Exclude hidden courses unless the viewer is authorised to view hidden courses.
- Query cohort enrolment instances in one batch to avoid per-cohort database queries.
- Added an administrator setting to control this display independently from cohort membership editing.

## 1.1.0-beta.2 - 2026-10-19

- Split the profile callback into focused rendering and permission helpers.
- Removed the boolean-flag argument from membership queries; visibility now follows the plugin setting.
- Migrated PHPUnit test coverage metadata from deprecated docblock annotations to PHP 8 attributes.
- Kept the original read-only display, optional write protections, and standard Moodle cohort API unchanged.

## 1.1.0-beta.1 - 2026-10-19

- Added optional, disabled-by-default profile entry point to cohort membership management.
- Added paginated search by cohort name, ID number, or numeric ID and add/remove actions using core Cohort API.
- Added a direct remove action alongside eligible profile badges (opens a confirmation page).
- Enforced context-specific `moodle/cohort:assign`, POST sesskey, and removal confirmation.
- Excluded externally managed cohorts from manual changes.
- Kept existing badge rendering and Privacy API, without custom database tables.
- Added regression tests for membership changes, permissions, and external cohorts.
- This is a beta candidate pending full GitHub Moodle CI checks.

## 1.0.7 - 2026-10-12

- Included the complete, unmodified GNU GPL v3 licence text in `LICENSE` to address Moodle Marketplace review MMR-293.
- Added direct public source repository and plugin-specific issue tracker links to the README.
- No runtime behaviour or database schema changes.

## 1.0.6 - 2026-09-17

- Prepared the plugin for public Marketplace release.
- Removed obsolete empty hook and external-service definition files.
- Removed bundled non-English language pack; translations should be maintained through Moodle AMOS after publication.
- Updated English privacy string to use a single supported language-string assignment.
- Expanded installation, configuration, privacy, permissions, and support documentation.

## 1.0.5 - 2026-09-17

- Removed cohort badges from the Course > Participants table.
- Removed the participant-table JavaScript enhancer.
- Removed the AJAX external function used by the participants table.
- Removed the output hook used to load participant-table code.
- Kept cohort membership display in the standard user profile.
- Updated plugin settings and language strings to match the reduced scope.

## 1.0.4 - 2026-09-17

- Fixed participant-table JavaScript initialisation through Moodle AMD footer handling.
