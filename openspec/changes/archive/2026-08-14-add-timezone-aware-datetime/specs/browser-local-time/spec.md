## Purpose

Interprets every admin-entered date/time as the browser's own local timezone and converts it to UTC before it reaches any other capability's validation or business logic, and displays every persisted UTC timestamp back to an admin converted to that same local timezone - so admins never have to do UTC-offset math themselves.

## ADDED Requirements

### Requirement: Date/time input is interpreted in the browser's local timezone
The system SHALL detect the admin's browser-local IANA timezone automatically and interpret any date/time the admin enters in a scheduling field (Popup Giveaway's scheduled start, Standard Giveaway's or an Event's start date/time, or a recurrence end date) as that timezone, converting it to UTC before validating or persisting it.

#### Scenario: Admin schedules a start time
- **WHEN** an admin in a non-UTC browser timezone enters a date and time in a scheduling field and submits the form
- **THEN** the system converts that date/time from the admin's browser-local timezone to UTC before validating and saving it

#### Scenario: Browser timezone cannot be detected
- **WHEN** the admin's browser does not report a usable timezone (e.g. JavaScript disabled)
- **THEN** the system falls back to interpreting the input as UTC rather than rejecting the submission

### Requirement: No manual timezone selection
The system SHALL NOT expose a manual timezone selection field on any date/time-scheduling form; the browser-local timezone is always used automatically.

#### Scenario: Scheduling a Standard Giveaway or Event
- **WHEN** an admin opens the Standard Giveaway or Event creation form
- **THEN** no timezone field is present, and the start date/time and any recurrence end date entered are interpreted in the admin's browser-local timezone

### Requirement: Persisted timestamps are displayed in the browser's local timezone
The system SHALL display any already-persisted UTC timestamp shown to an admin converted to their browser's local timezone, not the raw stored UTC value.

#### Scenario: Viewing a scheduled giveaway
- **WHEN** an admin views a list showing a giveaway's scheduled start time
- **THEN** the displayed time reflects the admin's own browser-local timezone, not the server's or another admin's timezone

#### Scenario: Two admins in different timezones view the same scheduled time
- **WHEN** two admins of the same guild, in different timezones, view the same giveaway's scheduled start time
- **THEN** each sees that same underlying moment displayed in their own respective browser-local timezone
