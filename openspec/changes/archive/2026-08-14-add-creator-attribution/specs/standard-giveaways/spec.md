## MODIFIED Requirements

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
