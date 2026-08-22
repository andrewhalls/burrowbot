## Purpose

Defines the title, message template, Discord channel, and optional recurrence rule for a broadcast series that `broadcast-occurrences` generates and posts instances of.

## ADDED Requirements

### Requirement: Broadcast creation
The system SHALL allow a guild admin to create a broadcast by specifying a title, a message template, a Discord channel, and either no recurrence (one-off) or a recurrence rule, scoped to their guild, and SHALL record which admin created it.

#### Scenario: Valid one-off broadcast created
- **WHEN** a guild admin submits a title, message template, channel, and no recurrence rule
- **THEN** the system creates the broadcast and generates exactly one occurrence for it, recorded as created by that admin

#### Scenario: Valid recurring broadcast created
- **WHEN** a guild admin submits a title, message template, channel, and a recurrence rule with a start time
- **THEN** the system creates the broadcast and begins generating occurrences per the recurrence rule (see `broadcast-occurrences`), recorded as created by that admin

#### Scenario: Missing required fields rejected
- **WHEN** a guild admin submits a broadcast without a title, message template, or channel
- **THEN** the system rejects the submission with a validation error and creates nothing

#### Scenario: Creator shown wherever the broadcast is displayed
- **WHEN** staff view a broadcast's list tile or detail view
- **THEN** the system shows which admin created it, when that information was recorded

### Requirement: Broadcast series status
The system SHALL support marking a broadcast series active, paused, or cancelled. Pausing or cancelling a recurring broadcast SHALL stop future occurrence generation without altering occurrences already generated.

#### Scenario: Cancelling stops future occurrences
- **WHEN** a guild admin cancels a recurring broadcast
- **THEN** no further occurrences are generated for it, and occurrences already generated (posted or not) are unaffected

#### Scenario: Pausing and resuming
- **WHEN** a guild admin pauses a recurring broadcast and later resumes it
- **THEN** occurrence generation stops while paused and resumes on the original recurrence schedule once reactivated

### Requirement: Editing a broadcast series only affects future occurrences
The system SHALL apply edits to a broadcast's title, message template, channel, or recurrence rule only to occurrences generated after the edit; occurrences already generated keep the values in effect when they were generated.

#### Scenario: Editing the message template of an ongoing recurring broadcast
- **WHEN** a guild admin changes the message template on a recurring broadcast that already has generated occurrences
- **THEN** occurrences generated before the change keep their original message template, and occurrences generated after the change use the new one

#### Scenario: Editing the channel of an ongoing recurring broadcast
- **WHEN** a guild admin changes the channel on a recurring broadcast that already has generated occurrences
- **THEN** occurrences generated before the change keep posting to their original channel, and occurrences generated after the change post to the new one

### Requirement: Deleting a broadcast series
The system SHALL allow a guild admin to permanently delete a broadcast series as long as none of its occurrences have been posted to Discord, and SHALL reject deletion otherwise.

#### Scenario: Deleting a series with no occurrences yet
- **WHEN** a guild admin deletes a broadcast series that has not yet generated any occurrence
- **THEN** the system permanently removes it

#### Scenario: Deleting a series with only scheduled occurrences
- **WHEN** a guild admin deletes a broadcast series whose occurrences are all still `scheduled`
- **THEN** the system permanently removes the series and its scheduled occurrences

#### Scenario: Deletion rejected once any occurrence has posted
- **WHEN** a guild admin attempts to delete a broadcast series that has at least one `posted` occurrence
- **THEN** the system rejects the deletion, so an already-posted Discord message is never left orphaned by a delete

### Requirement: Archiving a broadcast series
The system SHALL let a guild admin archive a broadcast series from any status, which SHALL set its status to `cancelled` (stopping future occurrence generation, same as the existing Cancel action) and SHALL mark it archived. The system SHALL let a guild admin unarchive an archived series, which SHALL clear only the archived marker, leaving its status unchanged.

#### Scenario: Archiving an active recurring broadcast
- **WHEN** a guild admin archives an active recurring broadcast
- **THEN** the system marks it cancelled and archived, and no further occurrences are generated for it

#### Scenario: Unarchiving leaves status untouched
- **WHEN** a guild admin unarchives a previously-archived broadcast
- **THEN** the system clears its archived marker, and its status remains `cancelled`

### Requirement: Archived broadcasts are hidden from the default list
The system SHALL exclude archived broadcast series from a guild's broadcast list by default, and SHALL let a guild admin toggle a "Show archived" control to include them alongside non-archived series.

#### Scenario: Archived broadcast hidden by default
- **WHEN** a guild admin views the broadcast list without the "Show archived" control enabled
- **THEN** archived broadcast series do not appear in the list

#### Scenario: Archived broadcast shown when toggled on
- **WHEN** a guild admin enables the "Show archived" control
- **THEN** archived broadcast series appear in the list alongside non-archived ones

### Requirement: Broadcasts dashboard navigation entry
The system SHALL show a "Broadcasts" entry in the guild dashboard's sidebar navigation, linking to the guild-scoped broadcast list screen, alongside the existing Events and Giveaways entries.

#### Scenario: Sidebar entry present for every guild
- **WHEN** a guild admin views the dashboard sidebar for any guild
- **THEN** a "Broadcasts" entry is shown that navigates to that guild's broadcast list

#### Scenario: Sidebar entry reflects the active screen
- **WHEN** a guild admin is on the broadcast list, create, or detail screen
- **THEN** the "Broadcasts" sidebar entry is shown in its active state, consistent with how other sections highlight themselves
