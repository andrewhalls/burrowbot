# broadcast-occurrences Specification

## Purpose

Governs generating, scheduling, and posting individual instances of a broadcast to Discord, with message template placeholders resolved at the moment each occurrence is actually posted rather than when it was generated.

## Requirements

### Requirement: Occurrence generation for recurring broadcasts
The system SHALL generate upcoming occurrences for an `active` recurring broadcast within a rolling window, computing each occurrence's scheduled post time from the broadcast's recurrence rule, without generating duplicate occurrences for the same computed post time.

#### Scenario: Weekly recurrence generates the next occurrence
- **WHEN** an active broadcast has a weekly recurrence rule and its most recently generated occurrence's post time has passed
- **THEN** the system generates the next occurrence at the correct future date/time per the rule

#### Scenario: Regenerating does not duplicate
- **WHEN** occurrence generation runs again before a new occurrence is due
- **THEN** no additional occurrence is created

### Requirement: One-off broadcasts generate exactly one occurrence
The system SHALL generate exactly one occurrence for a non-recurring broadcast, at creation time, and SHALL NOT generate any further occurrences for it.

#### Scenario: One-off broadcast occurrence count
- **WHEN** a one-off broadcast is created
- **THEN** exactly one occurrence exists for it, now and after any later occurrence-generation run

### Requirement: Posting an occurrence to Discord
The system SHALL post each occurrence as a new plain Discord message in the broadcast's configured channel, with its message template's placeholders resolved. An occurrence SHALL NOT be posted before its scheduled post time arrives.

#### Scenario: Due occurrence is posted
- **WHEN** a `scheduled` occurrence's scheduled post time has arrived
- **THEN** the system requests the bot post a new plain Discord message in the broadcast's channel with the occurrence's placeholders resolved, and records the resulting Discord message ID and post time

#### Scenario: Occurrence not yet due is left scheduled
- **WHEN** a `scheduled` occurrence's scheduled post time has not yet arrived
- **THEN** the system does not post it, and it remains `scheduled`

### Requirement: Message template placeholders
The system SHALL let a guild admin write a message template containing any subset (including none) of the following placeholders, and SHALL resolve them when an occurrence is posted, using values as of that moment rather than when the occurrence was generated or the template was written: the target channel (`{{channel}}`), the guild's name (`{{guild_name}}`), the post date (`{{date}}`), the post time (`{{time}}`), and, for a recurring broadcast, the next scheduled occurrence's date (`{{next_occurrence_date}}`).

#### Scenario: Template using all placeholders
- **WHEN** a guild admin saves a message template referencing the channel, guild name, date, time, and next-occurrence-date placeholders, and an occurrence is posted
- **THEN** the posted message substitutes all five with values current as of the moment it was posted

#### Scenario: Template using no placeholders
- **WHEN** a guild admin saves a message template with plain text and no placeholders
- **THEN** the system stores and later posts it unchanged, with nothing substituted

#### Scenario: Date and time reflect the actual post moment, not generation time
- **WHEN** a recurring broadcast's occurrence is generated well ahead of its scheduled post time
- **THEN** the `{{date}}` and `{{time}}` placeholders in the posted message reflect the moment the message was actually posted, not the moment the occurrence was generated

#### Scenario: Next occurrence date reflects the upcoming recurrence
- **WHEN** a recurring broadcast's occurrence is posted and another occurrence is scheduled to follow it
- **THEN** the `{{next_occurrence_date}}` placeholder resolves to that next occurrence's scheduled date

#### Scenario: Next occurrence date is empty for a one-off broadcast
- **WHEN** a one-off broadcast's occurrence is posted
- **THEN** the `{{next_occurrence_date}}` placeholder resolves to an empty string

#### Scenario: Next occurrence date is empty for the final occurrence of a bounded recurrence
- **WHEN** a recurring broadcast's recurrence rule ends (by date or count) and its last occurrence is posted
- **THEN** the `{{next_occurrence_date}}` placeholder resolves to an empty string

#### Scenario: Unrecognized placeholder left as literal text
- **WHEN** a guild admin's message template contains a `{{...}}` token that is not one of the supported placeholders
- **THEN** the system posts the message with that token left unchanged, rather than rejecting the template or failing to post
