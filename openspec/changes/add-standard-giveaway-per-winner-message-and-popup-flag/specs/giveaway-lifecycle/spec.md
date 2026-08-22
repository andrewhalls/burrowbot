## MODIFIED Requirements

### Requirement: Per-winner message configuration
The system SHALL let a guild admin configure an optional Discord channel and an optional mail-merge message template on a giveaway, used to send a templated message each time a member wins - distinct from the giveaway's own posting channel. The system SHALL require both to be set together: configuring one without the other SHALL be rejected by validation. The system SHALL only offer this configuration when the guild's popup giveaway winner-message feature flag (`guild-management`) is enabled.

#### Scenario: Both fields configured together
- **WHEN** a guild admin sets both a winner-message channel and a winner-message template on a giveaway
- **THEN** the system saves both

#### Scenario: Neither field configured
- **WHEN** a guild admin creates or edits a giveaway without setting a winner-message channel or template
- **THEN** the giveaway is saved successfully with both left unset

#### Scenario: Setting only one of the pair is rejected
- **WHEN** a guild admin submits a winner-message channel without a template, or a template without a channel
- **THEN** the system rejects the submission with a validation error and saves neither

#### Scenario: Configuration unavailable when the guild's flag is disabled
- **WHEN** a guild admin views the Create or Edit Giveaway form for a guild whose popup giveaway winner-message flag is disabled
- **THEN** the system does not offer the winner-message channel/template fields
