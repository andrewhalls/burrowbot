## Purpose

Governs generating, scheduling, and posting individual instances of an event to Discord, each with its own scheduled start time and its own independent signup roster - no carryover between occurrences of the same event.

## ADDED Requirements

### Requirement: Occurrence generation for recurring events
The system SHALL generate upcoming occurrences for an `active` recurring event within a rolling window, computing each occurrence's scheduled start time from the event's recurrence rule, without generating duplicate occurrences for the same computed start time.

#### Scenario: Weekly recurrence generates the next occurrence
- **WHEN** an active event has a weekly recurrence rule and its most recently generated occurrence's start time has passed
- **THEN** the system generates the next occurrence at the correct future date/time per the rule

#### Scenario: Regenerating does not duplicate
- **WHEN** occurrence generation runs again before a new occurrence is due
- **THEN** no additional occurrence is created

### Requirement: One-off events generate exactly one occurrence
The system SHALL generate exactly one occurrence for a non-recurring event, at creation time, and SHALL NOT generate any further occurrences for it.

#### Scenario: One-off event occurrence count
- **WHEN** a one-off event is created
- **THEN** exactly one occurrence exists for it, now and after any later occurrence-generation run

### Requirement: Posting an occurrence to Discord
The system SHALL post each occurrence to Discord according to its event's posting mode: as a new Discord thread in the configured channel, or as a new plain message in the configured channel.

#### Scenario: Thread-mode posting
- **WHEN** an occurrence belonging to a thread-mode event is due to be posted
- **THEN** the system requests the bot create a new Discord thread in the event's channel for that occurrence, distinct from any other occurrence's thread

#### Scenario: Message-mode posting
- **WHEN** an occurrence belonging to a message-mode event is due to be posted
- **THEN** the system requests the bot post a new plain Discord message in the event's channel for that occurrence, alongside other channel activity, without creating a thread

### Requirement: Independent roster per occurrence
The system SHALL treat each occurrence's set of signups as wholly independent of every other occurrence of the same event series.

#### Scenario: Signups do not carry over
- **WHEN** a member signs up for a role on one occurrence of a recurring event
- **THEN** that signup has no effect on any other occurrence of the same event - the member is not automatically signed up for future or past occurrences
