## ADDED Requirements

### Requirement: Editing a single upcoming occurrence
The system SHALL let a guild admin edit a single `scheduled` occurrence's description and prize items, independent of its series and every other occurrence, and SHALL reject editing once that occurrence is no longer `scheduled`.

#### Scenario: Editing a scheduled occurrence's description and prize items
- **WHEN** a guild admin edits a `scheduled` occurrence's description and/or prize items
- **THEN** the system saves the change against that occurrence only - the series' own template and every other occurrence (already generated or generated later) are unaffected

#### Scenario: Editing rejected once posted
- **WHEN** a guild admin attempts to edit an occurrence that is `posted` or `closed`
- **THEN** the system rejects the edit, so what already went to Discord for that occurrence never changes after the fact

#### Scenario: Browsing upcoming occurrences to edit
- **WHEN** a guild admin views a standard giveaway series with one or more `scheduled` occurrences
- **THEN** the system shows those upcoming occurrences, each reachable for editing
