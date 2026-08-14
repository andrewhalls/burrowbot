## MODIFIED Requirements

### Requirement: Giveaway creation
The system SHALL allow a guild admin to create a giveaway by specifying a Discord channel, a collection theme (from `collection-themes`), a duration in whole minutes, and optionally a description and an image, scoped to their guild, and SHALL record which admin created it.

#### Scenario: Valid draft created
- **WHEN** a guild admin submits a channel, an existing collection theme belonging to their guild, and a duration of 1 or more minutes
- **THEN** the system creates a giveaway in `draft` state with those values, not yet visible in Discord, recorded as created by that admin

#### Scenario: Invalid duration rejected
- **WHEN** a guild admin submits a duration of zero, negative, or non-integer minutes
- **THEN** the system rejects the submission with a validation error

#### Scenario: Description and image are optional
- **WHEN** a guild admin creates a giveaway without a description or image
- **THEN** the system creates it successfully with both left unset

#### Scenario: Description and image recorded when provided
- **WHEN** a guild admin creates a giveaway with a description and an uploaded image
- **THEN** the system records both against the giveaway

#### Scenario: Creator shown wherever the giveaway is displayed
- **WHEN** staff view a giveaway's list tile or detail view
- **THEN** the system shows which admin created it, when that information was recorded

#### Scenario: Pre-existing giveaways show no creator
- **WHEN** staff view a giveaway created before this capability existed
- **THEN** the system shows no creator for it, rather than guessing one
