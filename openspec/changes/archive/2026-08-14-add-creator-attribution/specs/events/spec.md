## MODIFIED Requirements

### Requirement: Event creation
The system SHALL allow a guild admin to create an event by specifying a title, a description, a Discord channel, a role set (from `event-role-sets`), a posting mode (new thread per occurrence, or new plain message per occurrence), and either no recurrence (one-off) or a recurrence rule, scoped to their guild, and SHALL record which admin created it.

#### Scenario: Valid one-off event created
- **WHEN** a guild admin submits a title, description, channel, role set, posting mode, and no recurrence rule
- **THEN** the system creates the event and generates exactly one occurrence for it, recorded as created by that admin

#### Scenario: Valid recurring event created
- **WHEN** a guild admin submits a title, description, channel, role set, posting mode, and a recurrence rule with a start time
- **THEN** the system creates the event and begins generating occurrences per the recurrence rule (see `event-occurrences`), recorded as created by that admin

#### Scenario: Missing required fields rejected
- **WHEN** a guild admin submits an event without a title, description, channel, or role set
- **THEN** the system rejects the submission with a validation error and creates nothing

#### Scenario: Creator shown wherever the event is displayed
- **WHEN** staff view an event's list tile or detail view
- **THEN** the system shows which admin created it, when that information was recorded

#### Scenario: Pre-existing events show no creator
- **WHEN** staff view an event created before this capability existed
- **THEN** the system shows no creator for it, rather than guessing one
