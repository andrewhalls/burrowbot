## MODIFIED Requirements

### Requirement: Posting an occurrence to Discord
The system SHALL post each occurrence to Discord according to its event's posting mode: as a new Discord thread in the configured channel, or as a new plain message in the configured channel. An occurrence SHALL NOT be posted before its scheduled time arrives.

#### Scenario: Thread-mode posting
- **WHEN** an occurrence belonging to a thread-mode event is due to be posted
- **THEN** the system requests the bot create a new Discord thread in the event's channel for that occurrence, distinct from any other occurrence's thread

#### Scenario: Message-mode posting
- **WHEN** an occurrence belonging to a message-mode event is due to be posted
- **THEN** the system requests the bot post a new plain Discord message in the event's channel for that occurrence, alongside other channel activity, without creating a thread

#### Scenario: Occurrence not yet due is left scheduled
- **WHEN** a `scheduled` occurrence's scheduled start time has not yet arrived
- **THEN** the system does not post it, and it remains `scheduled`
