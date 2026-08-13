# discord-bot-gateway Specification

## Purpose

Defines the contract and behavior of the persistent Discord gateway bot process that is the sole bridge between Discord's Gateway/REST API and Laravel's internal API, so all Discord-facing side effects are described independently of the bot's implementation.

## Requirements

### Requirement: Posting a giveaway message
The system SHALL, on request from Laravel, post a giveaway message to the specified Discord channel containing the collection theme name, duration/closing time, and a "Join Giveaway" interactive button, and SHALL report the resulting Discord message ID back to Laravel.

#### Scenario: Giveaway start triggers a post
- **WHEN** Laravel requests a giveaway be posted for a channel
- **THEN** the bot posts the message with a working "Join Giveaway" button and reports the Discord message ID so Laravel can edit it later

### Requirement: Relaying join interactions
The system SHALL, on receiving a "Join Giveaway" button interaction, call Laravel's internal API to process the join and relay Laravel's result back to Discord as a response to that interaction.

#### Scenario: Interaction relayed
- **WHEN** a member clicks "Join Giveaway"
- **THEN** the bot calls Laravel's internal join endpoint with the giveaway ID and the member's Discord ID, and replies to the Discord interaction with the outcome Laravel returned

### Requirement: Editing the message on close
The system SHALL, on request from Laravel, edit a giveaway's Discord message to indicate it has ended and remove or disable its join control.

#### Scenario: Giveaway closes
- **WHEN** Laravel requests a giveaway's message be closed out
- **THEN** the bot edits the original Discord message so the "Join Giveaway" button can no longer be clicked

### Requirement: Authenticated internal API access
The system SHALL require the bot process to authenticate to Laravel's internal API with a service credential on every request, and Laravel SHALL reject unauthenticated or invalid-credential requests.

#### Scenario: Missing or invalid credential
- **WHEN** a request to Laravel's internal API arrives without a valid service token
- **THEN** Laravel rejects it with a 401 response and performs no state change

### Requirement: Idempotent recovery on reconnect
The system SHALL NOT duplicate Discord-facing actions (re-posting an already-posted giveaway, re-closing an already-closed giveaway) after the bot process restarts or its gateway connection is re-established.

#### Scenario: Bot restarts mid-giveaway
- **WHEN** the bot process restarts while a giveaway is `active`
- **THEN** it does not repost the giveaway message, and resumes relaying join interactions and honoring the existing `ends_at` using state fetched from Laravel

### Requirement: Posting an event occurrence
The system SHALL, on request from Laravel, post an event occurrence to the specified Discord channel as either a new thread or a new plain message (per the event's posting mode), containing the event's title, description, scheduled start time, and role-selection controls for every role in the event's role set, and SHALL report the resulting Discord thread or message ID back to Laravel.

#### Scenario: Occurrence posted as a thread
- **WHEN** Laravel requests a thread-mode occurrence be posted
- **THEN** the bot creates a new Discord thread in the configured channel with role-selection controls and reports the thread ID so Laravel can associate it with the occurrence

#### Scenario: Occurrence posted as a message
- **WHEN** Laravel requests a message-mode occurrence be posted
- **THEN** the bot posts a new plain Discord message in the configured channel with role-selection controls and reports the message ID so Laravel can associate it with the occurrence

### Requirement: Relaying event-signup interactions
The system SHALL, on receiving a role-selection or Not-Attending interaction on an occurrence's post, call Laravel's internal API to process the signup and relay Laravel's result back to Discord as a response to that interaction.

#### Scenario: Role selection relayed
- **WHEN** a member selects a role on an occurrence's post
- **THEN** the bot calls Laravel's internal signup endpoint with the occurrence ID, the member's Discord ID, and the selected role, and replies to the Discord interaction with the outcome Laravel returned (confirmed, waitlisted, or rejected)

#### Scenario: Not Attending relayed
- **WHEN** a member selects Not Attending on an occurrence's post
- **THEN** the bot calls Laravel's internal signup endpoint marking that member Not Attending and replies to the Discord interaction with the outcome Laravel returned

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
