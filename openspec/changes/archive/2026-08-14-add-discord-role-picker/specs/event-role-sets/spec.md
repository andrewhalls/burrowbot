## MODIFIED Requirements

### Requirement: Role set creation
The system SHALL allow a guild admin to create a role set with a name, a flag for whether a member may hold more than one of its roles at once, and an ordered list of at least one role selected from the guild's synced Discord roles, scoped to their guild.

#### Scenario: Valid role set created
- **WHEN** a guild admin submits a role set name, a multiple-roles-allowed flag, and one or more roles selected from the guild's synced Discord roles
- **THEN** the system saves the role set with its roles tied to that guild, each referencing its selected Discord role

#### Scenario: Role set with no roles rejected
- **WHEN** a guild admin submits a role set with zero roles
- **THEN** the system rejects the submission with a validation error and creates nothing

#### Scenario: Bulk-selecting roles from an existing role set
- **WHEN** a guild admin selects an existing Event Role Set as a preset while creating a new role set
- **THEN** every role from that preset is added as a new uncapped role on the role set being created, each independently reconfigurable afterward

### Requirement: Role set item management
The system SHALL allow roles - selected from the guild's synced Discord roles - to be added to, removed from, or reconfigured (capacity mode) on a role set while that role set is not referenced by any occurrence that is currently posted and open for signups (i.e. not yet past its scheduled start time).

#### Scenario: Editing blocked while an open occurrence uses the role set
- **WHEN** a guild admin attempts to add, remove, or reconfigure a role on a role set that is referenced by an event occurrence which has been posted and has not yet reached its scheduled start time
- **THEN** the system rejects the edit

#### Scenario: Editing allowed once all occurrences using it have passed
- **WHEN** a guild admin edits a role set whose only referencing occurrences have already reached their scheduled start time
- **THEN** the system accepts the edit; future occurrences generated for events using this role set reflect the new configuration, and already-past occurrences keep their original roster unaffected

#### Scenario: Adding a role via the picker
- **WHEN** a guild admin adds a role to an existing, editable role set by selecting it from the synced-role picker
- **THEN** the system adds it as a new role on that set, defaulting to uncapped capacity
