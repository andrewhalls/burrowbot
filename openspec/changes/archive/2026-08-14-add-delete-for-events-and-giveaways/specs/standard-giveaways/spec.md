## ADDED Requirements

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
