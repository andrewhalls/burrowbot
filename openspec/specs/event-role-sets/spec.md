# event-role-sets Specification

## Purpose

Stores reusable, guild-scoped named sets of signup roles (e.g. "Raid Roles": Tank, Healer, DPS) that events reference, with each role independently configured for capacity and waitlist behavior.

## Requirements

### Requirement: Role set creation
The system SHALL allow a guild admin to create a role set with a name, a flag for whether a member may hold more than one of its roles at once, and an ordered list of at least one role, scoped to their guild.

#### Scenario: Valid role set created
- **WHEN** a guild admin submits a role set name, a multiple-roles-allowed flag, and one or more role names
- **THEN** the system saves the role set with its roles tied to that guild

#### Scenario: Role set with no roles rejected
- **WHEN** a guild admin submits a role set with zero roles
- **THEN** the system rejects the submission with a validation error and creates nothing

### Requirement: Per-role capacity mode
The system SHALL allow each role within a role set to be independently configured as uncapped, capped (blocking once full), or capped-with-waitlist, with a numeric capacity required for the two capped modes.

#### Scenario: Uncapped role
- **WHEN** a role is configured as uncapped
- **THEN** any number of members may hold that role on a given occurrence

#### Scenario: Capped-blocking role
- **WHEN** a role is configured as capped-blocking with a capacity of N
- **THEN** the (N+1)th signup attempt for that role on an occurrence is rejected rather than accepted

#### Scenario: Capped-with-waitlist role
- **WHEN** a role is configured as capped-with-waitlist with a capacity of N
- **THEN** the (N+1)th and later signup attempts for that role on an occurrence are accepted onto a waitlist rather than rejected

### Requirement: Role set item management
The system SHALL allow roles to be added to, removed from, or reconfigured (capacity mode) on a role set while that role set is not referenced by any occurrence that is currently posted and open for signups (i.e. not yet past its scheduled start time).

#### Scenario: Editing blocked while an open occurrence uses the role set
- **WHEN** a guild admin attempts to add, remove, or reconfigure a role on a role set that is referenced by an event occurrence which has been posted and has not yet reached its scheduled start time
- **THEN** the system rejects the edit

#### Scenario: Editing allowed once all occurrences using it have passed
- **WHEN** a guild admin edits a role set whose only referencing occurrences have already reached their scheduled start time
- **THEN** the system accepts the edit; future occurrences generated for events using this role set reflect the new configuration, and already-past occurrences keep their original roster unaffected

### Requirement: Role set reuse across events
The system SHALL allow the same role set to be selected by more than one event, including events created after the role set already has occurrence history.

#### Scenario: Reuse in a new event
- **WHEN** a guild admin creates a new event and selects a role set already used by another event
- **THEN** the new event is created referencing that role set, independent of the other event's occurrences or history
