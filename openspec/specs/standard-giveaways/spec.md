# standard-giveaways Specification

## Purpose

Defines the title, description, Discord channel, pre-set prize item(s), eligibility restriction, winner count, posting mode, and optional recurrence rule for a standard giveaway series that `standard-giveaway-occurrences` generates and posts instances of.

## Requirements

### Requirement: Standard giveaway creation
The system SHALL allow a guild admin to create a standard giveaway by specifying a title, a description, a Discord channel, an optional image, one or more pre-set prize items, an eligibility restriction, a winner count, a posting mode (new thread per occurrence, or new plain message per occurrence), and either no recurrence (one-off) or a recurrence rule, scoped to their guild, and SHALL record which admin created it.

#### Scenario: Valid one-off standard giveaway created
- **WHEN** a guild admin submits a title, description, channel, at least one prize item, and no recurrence rule
- **THEN** the system creates the standard giveaway and generates exactly one occurrence for it, recorded as created by that admin

#### Scenario: Valid recurring standard giveaway created
- **WHEN** a guild admin submits a title, description, channel, at least one prize item, and a recurrence rule with a start time
- **THEN** the system creates the standard giveaway and begins generating occurrences per the recurrence rule, recorded as created by that admin

#### Scenario: Missing required fields rejected
- **WHEN** a guild admin submits a standard giveaway without a title, description, or channel
- **THEN** the system rejects the submission with a validation error and creates nothing

#### Scenario: Image is optional
- **WHEN** a guild admin creates a standard giveaway without an image
- **THEN** the system creates it successfully with no image set

#### Scenario: Creator shown wherever the standard giveaway is displayed
- **WHEN** staff view a standard giveaway's list tile or detail view
- **THEN** the system shows which admin created it, when that information was recorded

#### Scenario: Pre-existing standard giveaways show no creator
- **WHEN** staff view a standard giveaway created before this capability existed
- **THEN** the system shows no creator for it, rather than guessing one

### Requirement: Prize items selected from existing collection theme items
The system SHALL let a guild admin select the giveaway's fixed prize pool by searching across all of the guild's collection theme items (not a whole theme), choosing one or more specific items.

#### Scenario: Selecting multiple specific items
- **WHEN** a guild admin searches across the guild's collection themes and selects two specific items as the prize pool
- **THEN** the standard giveaway is created referencing exactly those two items, independent of which themes they came from

#### Scenario: Selecting zero items rejected
- **WHEN** a guild admin submits a standard giveaway with no prize items selected
- **THEN** the system rejects the submission with a validation error and creates nothing

### Requirement: Eligibility restriction configuration
The system SHALL let a guild admin configure a standard giveaway as open to everyone, booster-only, restricted to one or more specific Discord roles, or both booster-only and role-restricted at once (a member must satisfy both to be eligible).

#### Scenario: Open giveaway
- **WHEN** a guild admin configures no restriction
- **THEN** every guild member is eligible to enter

#### Scenario: Booster-only giveaway
- **WHEN** a guild admin configures a booster-only restriction
- **THEN** only members currently boosting the guild are eligible to enter

#### Scenario: Role-restricted giveaway
- **WHEN** a guild admin configures one or more required roles
- **THEN** only members holding at least one of the configured roles are eligible to enter

#### Scenario: Combined booster and role restriction
- **WHEN** a guild admin configures both a booster-only restriction and one or more required roles
- **THEN** a member is eligible only if they are currently boosting AND hold at least one of the configured roles

### Requirement: Winner count
The system SHALL let a guild admin configure how many winners are drawn when an occurrence closes, defaulting to 1, and SHALL require it to be a positive integer.

#### Scenario: Default winner count
- **WHEN** a guild admin creates a standard giveaway without specifying a winner count
- **THEN** the giveaway is created with a winner count of 1

#### Scenario: Configured winner count greater than one
- **WHEN** a guild admin sets the winner count to 3
- **THEN** the giveaway is created with a winner count of 3

#### Scenario: Non-positive winner count rejected
- **WHEN** a guild admin submits a winner count of zero or negative
- **THEN** the system rejects the submission with a validation error

### Requirement: Standard giveaway series status
The system SHALL support marking a standard giveaway series active, paused, or cancelled. Pausing or cancelling a recurring giveaway SHALL stop future occurrence generation without altering occurrences already generated.

#### Scenario: Cancelling stops future occurrences
- **WHEN** a guild admin cancels a recurring standard giveaway
- **THEN** no further occurrences are generated for it, and occurrences already generated are unaffected

### Requirement: Editing a standard giveaway series only affects future occurrences
The system SHALL apply edits to a standard giveaway's title, description, image, channel, prize items, restrictions, winner count, posting mode, or recurrence rule only to occurrences generated after the edit; occurrences already generated keep the values in effect when they were generated.

#### Scenario: Editing prize items of an ongoing recurring giveaway
- **WHEN** a guild admin changes the prize items on a recurring standard giveaway that already has generated occurrences
- **THEN** occurrences generated before the change keep their original prize items, and occurrences generated after the change reference the new ones

#### Scenario: Editing the image of an ongoing recurring giveaway
- **WHEN** a guild admin changes the image on a recurring standard giveaway that already has generated occurrences
- **THEN** occurrences generated before the change keep their original image, and occurrences generated after the change use the new one

### Requirement: Deleting a standard giveaway series
The system SHALL allow a guild admin to permanently delete a standard giveaway series as long as none of its occurrences have been posted to Discord or closed, and SHALL reject deletion otherwise.

#### Scenario: Deleting a series with no occurrences yet
- **WHEN** a guild admin deletes a standard giveaway series that has not yet generated any occurrence
- **THEN** the system permanently removes it

#### Scenario: Deleting a series with only scheduled occurrences
- **WHEN** a guild admin deletes a standard giveaway series whose occurrences are all still `scheduled`
- **THEN** the system permanently removes the series and its scheduled occurrences

#### Scenario: Deletion rejected once any occurrence has posted
- **WHEN** a guild admin attempts to delete a standard giveaway series that has at least one `posted` or `closed` occurrence
- **THEN** the system rejects the deletion, so an already-posted Discord message is never left orphaned by a delete
