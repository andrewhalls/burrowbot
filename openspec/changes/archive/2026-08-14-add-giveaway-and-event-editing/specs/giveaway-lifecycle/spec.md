## ADDED Requirements

### Requirement: Editing a draft giveaway
The system SHALL allow a guild admin to edit a `draft` giveaway's channel, collection theme, duration, description, and image before it is started.

#### Scenario: Editing a draft giveaway
- **WHEN** a guild admin edits a `draft` giveaway's channel, collection theme, duration, description, or image
- **THEN** the system saves the change, and the giveaway remains in `draft` state

#### Scenario: Editing rejected once no longer a draft
- **WHEN** a guild admin attempts to edit a giveaway that has already been started or closed
- **THEN** the system rejects the edit, per "Giveaway configuration immutability once started"
