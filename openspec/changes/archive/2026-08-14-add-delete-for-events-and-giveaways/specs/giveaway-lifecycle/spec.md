## ADDED Requirements

### Requirement: Deleting a draft giveaway
The system SHALL allow a guild admin to permanently delete a giveaway while it is still `draft`, and SHALL reject deletion once it has been started.

#### Scenario: Deleting a draft giveaway
- **WHEN** a guild admin deletes a `draft` giveaway
- **THEN** the system permanently removes it

#### Scenario: Deletion rejected once no longer a draft
- **WHEN** a guild admin attempts to delete a giveaway that has already been started or closed
- **THEN** the system rejects the deletion, so an already-posted Discord message is never left orphaned by a delete
