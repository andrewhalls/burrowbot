## MODIFIED Requirements

### Requirement: Per-winner templated message sent on a new win
The system SHALL, when a giveaway has both a winner-message channel and template configured AND the guild's popup giveaway winner-message feature flag (`guild-management`) is enabled, send a new Discord message to that channel each time a member wins - built from the template with the winning member's mention and the won item's name substituted in - independent of and in addition to the existing public win announcement. The system SHALL NOT send this message when either field is unconfigured, when the guild's flag is disabled, or for a rejected entry (duplicate or expired).

#### Scenario: Message sent when both fields are configured
- **WHEN** a member wins on a giveaway with both a winner-message channel and template configured, in a guild whose popup giveaway winner-message flag is enabled
- **THEN** the system requests the bot send a new message to that channel, with the template's placeholders substituted for that winner and their item

#### Scenario: Message skipped when unconfigured
- **WHEN** a member wins on a giveaway with no winner-message channel or template configured
- **THEN** the system does not request any per-winner message

#### Scenario: Message skipped when the guild's flag is disabled
- **WHEN** a member wins on a giveaway with both fields configured, in a guild whose popup giveaway winner-message flag is disabled
- **THEN** the system does not request any per-winner message, even though both fields are still configured

#### Scenario: Message skipped for a rejected entry
- **WHEN** a join attempt is rejected as a duplicate or as expired
- **THEN** the system does not request a per-winner message, even if the giveaway has both fields configured and the guild's flag is enabled

#### Scenario: Template with no placeholders
- **WHEN** a winner-message template contains neither `{winner}` nor `{prize}`
- **THEN** the system still sends it, unchanged, to the configured channel

#### Scenario: Existing public/private result replies are unaffected
- **WHEN** a member wins or is rejected on a giveaway with a winner-message channel and template configured
- **THEN** the existing public win announcement or private rejection reply in the giveaway's own channel still happens exactly as it does without this feature configured or enabled
