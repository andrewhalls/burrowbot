## ADDED Requirements

### Requirement: Archiving a standard giveaway series
The system SHALL let a guild admin archive a standard giveaway series from any status, which SHALL set its status to `cancelled` (stopping future occurrence generation, same as the existing Cancel action) and SHALL mark it archived. The system SHALL let a guild admin unarchive an archived series, which SHALL clear only the archived marker, leaving its status unchanged.

#### Scenario: Archiving an active recurring giveaway
- **WHEN** a guild admin archives an active recurring standard giveaway
- **THEN** the system marks it cancelled and archived, and no further occurrences are generated for it

#### Scenario: Archiving an already-cancelled giveaway
- **WHEN** a guild admin archives a standard giveaway that is already cancelled
- **THEN** the system marks it archived without changing its status

#### Scenario: Unarchiving leaves status untouched
- **WHEN** a guild admin unarchives a previously-archived standard giveaway
- **THEN** the system clears its archived marker, and its status remains `cancelled`

### Requirement: Archived standard giveaways are hidden from the default list
The system SHALL exclude archived standard giveaway series from a guild's standard giveaway list by default, and SHALL let a guild admin toggle a "Show archived" control to include them alongside non-archived series.

#### Scenario: Archived giveaway hidden by default
- **WHEN** a guild admin views the standard giveaway list without the "Show archived" control enabled
- **THEN** archived standard giveaway series do not appear in the list

#### Scenario: Archived giveaway shown when toggled on
- **WHEN** a guild admin enables the "Show archived" control
- **THEN** archived standard giveaway series appear in the list alongside non-archived ones

#### Scenario: Archived giveaways remain fully usable once shown
- **WHEN** a guild admin views an archived standard giveaway series with "Show archived" enabled
- **THEN** the system offers the same Edit, Delete, Activate, Pause, Cancel, and Unarchive actions as any other standard giveaway series
