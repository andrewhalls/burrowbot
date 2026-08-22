## ADDED Requirements

### Requirement: Per-winner message configuration
The system SHALL let a guild admin configure an optional Discord channel and an optional mail-merge message template on a giveaway, used to send a templated message each time a member wins - distinct from the giveaway's own posting channel. The system SHALL require both to be set together: configuring one without the other SHALL be rejected by validation.

#### Scenario: Both fields configured together
- **WHEN** a guild admin sets both a winner-message channel and a winner-message template on a giveaway
- **THEN** the system saves both

#### Scenario: Neither field configured
- **WHEN** a guild admin creates or edits a giveaway without setting a winner-message channel or template
- **THEN** the giveaway is saved successfully with both left unset

#### Scenario: Setting only one of the pair is rejected
- **WHEN** a guild admin submits a winner-message channel without a template, or a template without a channel
- **THEN** the system rejects the submission with a validation error and saves neither

### Requirement: Winner-message configuration stays editable regardless of giveaway status
Unlike the giveaway's channel, collection theme, duration, description, or image, the system SHALL allow the winner-message channel and template to be set or changed at any giveaway status (`draft`, `active`, or `closed`), since they affect only future win events and never the already-posted Discord message.

#### Scenario: Editing the winner-message fields on an active giveaway
- **WHEN** a guild admin changes the winner-message channel or template on an `active` giveaway
- **THEN** the system saves the change, and it takes effect for the next win, without being rejected by "Giveaway configuration immutability once started"

#### Scenario: Editing the winner-message fields on a closed giveaway
- **WHEN** a guild admin changes the winner-message channel or template on a `closed` giveaway
- **THEN** the system saves the change without rejecting it
