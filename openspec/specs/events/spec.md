# events Specification

## Purpose

Defines the title, description, Discord channel, role set, posting mode, and optional recurrence rule for an event series that `event-occurrences` generates and posts instances of.

## Requirements

### Requirement: Event creation
The system SHALL allow a guild admin to create an event by specifying a title, a description, a Discord channel, an optional image, a role set (from `event-role-sets`), a posting mode (new thread per occurrence, or new plain message per occurrence), and either no recurrence (one-off) or a recurrence rule, scoped to their guild, and SHALL record which admin created it.

#### Scenario: Valid one-off event created
- **WHEN** a guild admin submits a title, description, channel, role set, posting mode, and no recurrence rule
- **THEN** the system creates the event and generates exactly one occurrence for it, recorded as created by that admin

#### Scenario: Valid recurring event created
- **WHEN** a guild admin submits a title, description, channel, role set, posting mode, and a recurrence rule with a start time
- **THEN** the system creates the event and begins generating occurrences per the recurrence rule (see `event-occurrences`), recorded as created by that admin

#### Scenario: Missing required fields rejected
- **WHEN** a guild admin submits an event without a title, description, channel, or role set
- **THEN** the system rejects the submission with a validation error and creates nothing

#### Scenario: Image is optional
- **WHEN** a guild admin creates an event without an image
- **THEN** the system creates it successfully with no image set

#### Scenario: Creator shown wherever the event is displayed
- **WHEN** staff view an event's list tile or detail view
- **THEN** the system shows which admin created it, when that information was recorded

#### Scenario: Pre-existing events show no creator
- **WHEN** staff view an event created before this capability existed
- **THEN** the system shows no creator for it, rather than guessing one

### Requirement: Event series status
The system SHALL support marking an event series active, paused, or cancelled. Pausing or cancelling a recurring event SHALL stop future occurrence generation without altering occurrences already generated.

#### Scenario: Cancelling stops future occurrences
- **WHEN** a guild admin cancels a recurring event
- **THEN** no further occurrences are generated for it, and occurrences already generated (posted or not) are unaffected

#### Scenario: Pausing and resuming
- **WHEN** a guild admin pauses a recurring event and later resumes it
- **THEN** occurrence generation stops while paused and resumes on the original recurrence schedule once reactivated

### Requirement: Editing an event series only affects future occurrences
The system SHALL apply edits to an event's title, description, image, channel, role set, posting mode, or recurrence rule only to occurrences generated after the edit; occurrences already generated keep the values that were in effect when they were generated.

#### Scenario: Editing the role set of an ongoing recurring event
- **WHEN** a guild admin changes the role set on a recurring event that already has generated occurrences
- **THEN** occurrences generated before the change keep referencing the original role set, and occurrences generated after the change reference the new one

#### Scenario: Editing the image of an ongoing recurring event
- **WHEN** a guild admin changes the image on a recurring event that already has generated occurrences
- **THEN** occurrences generated before the change keep their original image, and occurrences generated after the change use the new one

### Requirement: Deleting an event series
The system SHALL allow a guild admin to permanently delete an event series as long as none of its occurrences have been posted to Discord, and SHALL reject deletion otherwise.

#### Scenario: Deleting a series with no occurrences yet
- **WHEN** a guild admin deletes an event series that has not yet generated any occurrence
- **THEN** the system permanently removes it

#### Scenario: Deleting a series with only scheduled occurrences
- **WHEN** a guild admin deletes an event series whose occurrences are all still `scheduled`
- **THEN** the system permanently removes the series and its scheduled occurrences

#### Scenario: Deletion rejected once any occurrence has posted
- **WHEN** a guild admin attempts to delete an event series that has at least one `posted` occurrence
- **THEN** the system rejects the deletion, so an already-posted Discord message is never left orphaned by a delete
