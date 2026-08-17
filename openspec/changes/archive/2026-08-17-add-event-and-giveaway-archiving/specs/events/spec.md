## ADDED Requirements

### Requirement: Archiving an event series
The system SHALL let a guild admin archive an event series from any status, which SHALL set its status to `cancelled` (stopping future occurrence generation, same as the existing Cancel action) and SHALL mark it archived. The system SHALL let a guild admin unarchive an archived series, which SHALL clear only the archived marker, leaving its status unchanged.

#### Scenario: Archiving an active recurring event
- **WHEN** a guild admin archives an active recurring event
- **THEN** the system marks it cancelled and archived, and no further occurrences are generated for it

#### Scenario: Archiving an already-cancelled event
- **WHEN** a guild admin archives an event that is already cancelled
- **THEN** the system marks it archived without changing its status

#### Scenario: Unarchiving leaves status untouched
- **WHEN** a guild admin unarchives a previously-archived event
- **THEN** the system clears its archived marker, and its status remains `cancelled`

### Requirement: Archived events are hidden from the default list
The system SHALL exclude archived event series from a guild's event list by default, and SHALL let a guild admin toggle a "Show archived" control to include them alongside non-archived series.

#### Scenario: Archived event hidden by default
- **WHEN** a guild admin views the event list without the "Show archived" control enabled
- **THEN** archived event series do not appear in the list

#### Scenario: Archived event shown when toggled on
- **WHEN** a guild admin enables the "Show archived" control
- **THEN** archived event series appear in the list alongside non-archived ones

#### Scenario: Archived events remain fully usable once shown
- **WHEN** a guild admin views an archived event series with "Show archived" enabled
- **THEN** the system offers the same Edit, Delete, Activate, Pause, Cancel, and Unarchive actions as any other event series
