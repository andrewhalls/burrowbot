# giveaway-lifecycle Specification

## Purpose

Governs creating, starting, and time-bounding a single pop-up giveaway run in one guild's channel against one collection theme, including its automatic close after a configured duration.

## Requirements

### Requirement: Giveaway creation
The system SHALL allow a guild admin to create a giveaway by specifying a Discord channel, a collection theme (from `collection-themes`), and a duration in whole minutes, scoped to their guild.

#### Scenario: Valid draft created
- **WHEN** a guild admin submits a channel, an existing collection theme belonging to their guild, and a duration of 1 or more minutes
- **THEN** the system creates a giveaway in `draft` state with those values, not yet visible in Discord

#### Scenario: Invalid duration rejected
- **WHEN** a guild admin submits a duration of zero, negative, or non-integer minutes
- **THEN** the system rejects the submission with a validation error

### Requirement: Starting a giveaway
The system SHALL, when a draft giveaway is started, post it to Discord and record the exact moment it will close.

#### Scenario: Start posts to Discord
- **WHEN** a guild admin starts a `draft` giveaway
- **THEN** the system requests the bot post the giveaway message to the configured channel, transitions the giveaway to `active`, and sets `ends_at` to the start time plus the configured duration

### Requirement: Automatic closing at expiry
The system SHALL close an `active` giveaway automatically once `ends_at` has passed, without requiring any further entrant action.

#### Scenario: Giveaway expires with entrants
- **WHEN** the current time passes an active giveaway's `ends_at`
- **THEN** a scheduled process transitions the giveaway to `closed` and requests the bot edit the Discord message to show it has ended with the join control removed or disabled

#### Scenario: Giveaway expires with zero entrants
- **WHEN** an active giveaway's `ends_at` passes and no one joined
- **THEN** the giveaway still transitions to `closed` on schedule

### Requirement: Giveaway configuration immutability once started
The system SHALL NOT allow the channel, collection theme, or duration of a giveaway to be changed once it has left the `draft` state.

#### Scenario: Edit attempt on active giveaway
- **WHEN** a guild admin attempts to change the collection theme or duration of an `active` or `closed` giveaway
- **THEN** the system rejects the change

### Requirement: Scheduled start
The system SHALL allow a guild admin to set a future date/time when creating a giveaway, instead of starting it immediately, and SHALL automatically start it at that time without requiring further admin action.

#### Scenario: Giveaway created with a scheduled start
- **WHEN** a guild admin creates a giveaway with a future scheduled start date/time instead of starting it immediately
- **THEN** the system creates the giveaway in `draft` state with that scheduled start recorded, and does not post it to Discord yet

#### Scenario: Scheduled start fires automatically
- **WHEN** a draft giveaway's scheduled start time arrives
- **THEN** the system automatically posts it to Discord, transitions it to `active`, and sets `ends_at` to that moment plus the configured duration - identical to a manual start, just without an admin click

#### Scenario: Manual start still available before the scheduled time
- **WHEN** a guild admin manually starts a giveaway that has a future scheduled start time, before that time arrives
- **THEN** the system starts it immediately per the existing "Starting a giveaway" requirement, and the now-moot scheduled start never fires
