## ADDED Requirements

### Requirement: Posting a broadcast message
The system SHALL, on request from Laravel, post a broadcast occurrence's fully-resolved message text as a new plain Discord message to the specified channel, and SHALL report the resulting Discord message ID back to Laravel.

#### Scenario: Broadcast occurrence triggers a post
- **WHEN** Laravel requests a broadcast occurrence be posted for a channel
- **THEN** the bot posts a new plain message containing the resolved text and reports the resulting Discord message ID back to Laravel

#### Scenario: Bot performs no placeholder resolution
- **WHEN** Laravel requests a broadcast occurrence be posted
- **THEN** the bot posts the message text exactly as supplied, performing no placeholder substitution or other transformation of its own
