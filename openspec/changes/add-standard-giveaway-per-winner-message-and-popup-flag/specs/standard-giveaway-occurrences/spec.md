## ADDED Requirements

### Requirement: Individual per-winner message sent alongside the combined announcement
The system SHALL, when a standard giveaway has both a per-winner message channel and template configured (`standard-giveaways` - "Per-winner message configuration"), send one new Discord message per drawn winner to that channel when the occurrence closes - built from the template with that winner's mention and prize name substituted in - independent of and in addition to the existing single combined winner announcement message (`Separate winner announcement message with claim details`). The system SHALL NOT send any per-winner messages when either field is unconfigured, and SHALL send none when the occurrence closes with zero winners.

#### Scenario: One message sent per winner when both fields are configured
- **WHEN** an occurrence whose giveaway has a per-winner message channel and template configured closes with two or more winners
- **THEN** the system requests the bot send one separate message per winner to that channel, each with that winner's mention and prize substituted in

#### Scenario: Runs alongside the existing combined announcement
- **WHEN** an occurrence closes with both the per-winner message fields and the existing congratulations message template configured
- **THEN** the system requests both the individual per-winner messages and the single combined announcement message, independently of each other

#### Scenario: Skipped when unconfigured
- **WHEN** an occurrence whose giveaway has no per-winner message channel or template configured closes with winners
- **THEN** the system does not request any per-winner messages

#### Scenario: Skipped with zero winners
- **WHEN** an occurrence closes with zero eligible entrants
- **THEN** the system does not request any per-winner messages, even if both fields are configured

#### Scenario: Template with no placeholders
- **WHEN** a per-winner message template contains neither `{winner}` nor `{prize}`
- **THEN** the system still sends it, unchanged, to each winner's message
