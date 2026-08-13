## Purpose

Governs a Discord member entering an open standard giveaway occurrence: eligibility restriction enforcement, one entry per member, and the server-side entry cutoff once the occurrence has closed.

## ADDED Requirements

### Requirement: Entering an open occurrence
The system SHALL let an eligible member create exactly one entry in an occurrence that has not yet reached its end time.

#### Scenario: Eligible member enters
- **WHEN** an eligible member who has not yet entered clicks "Enter" on an open occurrence
- **THEN** the system records an entry for that member

### Requirement: One entry per member
The system SHALL prevent a member from creating more than one entry in the same occurrence.

#### Scenario: Duplicate entry attempt
- **WHEN** a member who already has an entry in an occurrence clicks "Enter" again
- **THEN** the system does not create a second entry, and the member is told they're already entered

### Requirement: Booster-only restriction enforcement
The system SHALL reject an entry attempt from a member who is not currently boosting the guild when the occurrence's giveaway requires it.

#### Scenario: Non-booster rejected
- **WHEN** a member who is not currently boosting the guild attempts to enter a booster-only occurrence
- **THEN** the system rejects the entry and the member is told why

#### Scenario: Booster accepted
- **WHEN** a member who is currently boosting the guild attempts to enter a booster-only occurrence
- **THEN** the entry is accepted

### Requirement: Role restriction enforcement
The system SHALL reject an entry attempt from a member holding none of the occurrence's giveaway's required roles, when at least one required role is configured.

#### Scenario: Member without a required role rejected
- **WHEN** a member holding none of the configured required roles attempts to enter a role-restricted occurrence
- **THEN** the system rejects the entry and the member is told why

#### Scenario: Member with a required role accepted
- **WHEN** a member holding at least one of the configured required roles attempts to enter a role-restricted occurrence
- **THEN** the entry is accepted

### Requirement: Entry rejected once closed, enforced server-side
The system SHALL reject any entry request received after the occurrence's end time, independent of whether the Discord message still shows the "Enter" control as clickable.

#### Scenario: Late entry after server-side close
- **WHEN** an entry request for an occurrence arrives at the system after that occurrence's end time has passed, whether or not the occurrence has been transitioned to closed yet by the scheduled process
- **THEN** the system rejects the entry
