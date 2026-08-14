## MODIFIED Requirements

### Requirement: Giveaway creation
The system SHALL allow a guild admin to create a giveaway by specifying a Discord channel, a collection theme (from `collection-themes`), a duration in whole minutes, and optionally a description and an image, scoped to their guild.

#### Scenario: Valid draft created
- **WHEN** a guild admin submits a channel, an existing collection theme belonging to their guild, and a duration of 1 or more minutes
- **THEN** the system creates a giveaway in `draft` state with those values, not yet visible in Discord

#### Scenario: Invalid duration rejected
- **WHEN** a guild admin submits a duration of zero, negative, or non-integer minutes
- **THEN** the system rejects the submission with a validation error

#### Scenario: Description and image are optional
- **WHEN** a guild admin creates a giveaway without a description or image
- **THEN** the system creates it successfully with both left unset

#### Scenario: Description and image recorded when provided
- **WHEN** a guild admin creates a giveaway with a description and an uploaded image
- **THEN** the system records both against the giveaway

### Requirement: Giveaway configuration immutability once started
The system SHALL NOT allow the channel, collection theme, duration, description, or image of a giveaway to be changed once it has left the `draft` state.

#### Scenario: Edit attempt on active giveaway
- **WHEN** a guild admin attempts to change the collection theme or duration of an `active` or `closed` giveaway
- **THEN** the system rejects the change
