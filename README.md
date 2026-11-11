# Cohort memberships in profile (`local_cohortbadges`)

Cohort memberships is a lightweight local plugin for Moodle 5.x that displays a user's existing cohort memberships in the standard Moodle user profile.

The plugin displays cohort memberships without modifying Moodle core. An **optional, disabled-by-default** management feature lets authorised staff change membership of manually managed cohorts from a link in the user's standard profile. When management is disabled, the original read-only behaviour is preserved.

## Features

- Displays cohort memberships in the standard user profile.
- Shows a collapsible read-only list of linked courses for each cohort, based on configured Cohort sync enrolment instances (enabled or disabled), when the viewing staff member has the appropriate capabilities.
- Optionally shows an inline cohort picker in user profiles, with immediate listing on focus, incremental search and same-page add/remove actions with inline confirmation.
- Allows authorised staff to search for cohorts and add/remove an existing user via Moodle core Cohort API.
- Configurable label format: cohort name, cohort ID number, or both.
- Hidden cohorts are excluded by default.
- Users can view their own cohort memberships.
- Staff access follows Moodle capabilities and course context.
- No external services, API keys, subscriptions, or scheduled tasks are required.
- No custom database tables are created.
- No personal data is stored by the plugin.

## Requirements

- Moodle 5.0, 5.1, or 5.2.
- PHP version supported by the corresponding Moodle release.

## Installation

### From ZIP

1. Download the release ZIP.
2. In Moodle, go to **Site administration > Plugins > Install plugins**.
3. Upload the ZIP and complete the installation.
4. Visit **Site administration > Notifications** if prompted.

### Manual installation

Copy the `cohortbadges` directory to:

```text
local/cohortbadges
```

Then visit **Site administration > Notifications**.

## Configuration

Go to:

**Site administration > Plugins > Local plugins > Cohort memberships**

Available settings:

- Enable or disable cohort membership display.
- Choose the displayed label format: cohort name, ID number, or both.
- Choose whether hidden cohorts may be included.
- Enable or disable display in user profiles.
- Enable or disable cohort membership management (off by default).

## Permissions

The plugin defines the capability:

```text
local/cohortbadges:view
```

It is available at course context and is allowed by default for managers, editing teachers, and teachers. Users can always see their own cohort memberships. When a profile is viewed outside a course context, Moodle's standard `moodle/cohort:view` capability is used.

**Write permissions:** When enabled by a site administrator, the management link opens a page that requires the built-in `moodle/cohort:assign` capability **in each cohort's own context**. Category-level cohort managers are supported. The plugin does not grant any role additional permissions. Cohorts managed by external components (non-empty `component`) cannot be edited. All changes require a session-key-checked POST; removal also requires confirmation. The plugin does not create, delete, or rename cohorts.

**Important:** Adding/removing a cohort member can affect course access if cohort enrolment synchronisation is configured. Membership changes use Moodle core events and APIs, not direct database writes.

## Privacy

The plugin does not create its own personal-data tables and does not send data to external services. It reads existing Moodle cohort membership information and optionally changes Moodle core cohort memberships. No personal data is stored in plugin-owned tables; standard Moodle events record membership changes. A Moodle Privacy API null provider is included.

## Dependencies

None beyond Moodle core.

## Support and issues

Report bugs and suggest improvements in the public, plugin-specific issue tracker:

https://github.com/ildarkry-stack/local_cohortbadges/issues

Public source code repository:

https://github.com/ildarkry-stack/local_cohortbadges

## License

GNU GPL v3 or later. See the full licence text in [LICENSE](LICENSE).
