## Purpose

Defines the contract and behavior of the persistent Discord gateway bot process that is the sole bridge between Discord's Gateway/REST API and Laravel's internal API, so all Discord-facing side effects are described independently of the bot's implementation.

## ADDED Requirements

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
