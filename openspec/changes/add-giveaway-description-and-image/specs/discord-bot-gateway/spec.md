## MODIFIED Requirements

### Requirement: Posting a giveaway message
The system SHALL, on request from Laravel, post a giveaway message to the specified Discord channel containing the collection theme name, duration/closing time, a description (the giveaway's own description if set, otherwise a default instructional line), the giveaway's image (if set), and a "Join Giveaway" interactive button, and SHALL report the resulting Discord message ID back to Laravel.

#### Scenario: Giveaway start triggers a post
- **WHEN** Laravel requests a giveaway be posted for a channel
- **THEN** the bot posts the message with a working "Join Giveaway" button and reports the Discord message ID so Laravel can edit it later

#### Scenario: Custom description shown when set
- **WHEN** Laravel requests a giveaway with a description be posted
- **THEN** the bot's post shows that description instead of the default instructional line

#### Scenario: Image shown when set
- **WHEN** Laravel requests a giveaway with an image be posted
- **THEN** the bot's post includes that image

### Requirement: Posting a standard giveaway occurrence
The system SHALL, on request from Laravel, post a standard giveaway occurrence to the specified Discord channel as either a new thread or a new plain message (per the giveaway's posting mode), containing the prize item(s), the eligibility restriction (if any), the end time, the giveaway's image (if set), and an "Enter" control, and SHALL report the resulting Discord thread or message ID back to Laravel.

#### Scenario: Occurrence posted as a thread
- **WHEN** Laravel requests a thread-mode standard giveaway occurrence be posted
- **THEN** the bot creates a new Discord thread in the configured channel with an "Enter" control and reports the thread ID so Laravel can associate it with the occurrence

#### Scenario: Occurrence posted as a message
- **WHEN** Laravel requests a message-mode standard giveaway occurrence be posted
- **THEN** the bot posts a new plain Discord message in the configured channel with an "Enter" control and reports the message ID so Laravel can associate it with the occurrence

#### Scenario: Image shown when set
- **WHEN** Laravel requests a standard giveaway occurrence with an image be posted
- **THEN** the bot's post includes that image
