## ADDED Requirements

### Requirement: Posting a standard giveaway occurrence
The system SHALL, on request from Laravel, post a standard giveaway occurrence to the specified Discord channel as either a new thread or a new plain message (per the giveaway's posting mode), containing the prize item(s), the eligibility restriction (if any), the end time, and an "Enter" control, and SHALL report the resulting Discord thread or message ID back to Laravel.

#### Scenario: Occurrence posted as a thread
- **WHEN** Laravel requests a thread-mode standard giveaway occurrence be posted
- **THEN** the bot creates a new Discord thread in the configured channel with an "Enter" control and reports the thread ID so Laravel can associate it with the occurrence

#### Scenario: Occurrence posted as a message
- **WHEN** Laravel requests a message-mode standard giveaway occurrence be posted
- **THEN** the bot posts a new plain Discord message in the configured channel with an "Enter" control and reports the message ID so Laravel can associate it with the occurrence

### Requirement: Relaying standard giveaway entry interactions with eligibility data
The system SHALL, on receiving an "Enter" interaction, call Laravel's internal API with the member's Discord ID and their current Discord roles and boost status as reported by that interaction, and relay Laravel's result back to Discord as a response to that interaction.

#### Scenario: Entry relayed with role and boost snapshot
- **WHEN** a member clicks "Enter" on a standard giveaway occurrence
- **THEN** the bot calls Laravel's internal entry endpoint with the occurrence ID, the member's Discord ID, the member's current role IDs, and whether they are currently boosting, and replies to the Discord interaction with the outcome Laravel returned

### Requirement: Announcing drawn winners
The system SHALL, on request from Laravel after an occurrence closes, update or reply to that occurrence's Discord message/thread announcing the drawn winners and, when more than one prize item was configured, which item each winner received.

#### Scenario: Winners announced after a draw
- **WHEN** Laravel requests winners be announced for a closed occurrence
- **THEN** the bot posts the winner announcement in the occurrence's original channel/thread, naming each winner and their assigned item
