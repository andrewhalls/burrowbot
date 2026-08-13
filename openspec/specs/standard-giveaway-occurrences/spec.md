# standard-giveaway-occurrences Specification

## Purpose

Governs generating, posting, and auto-closing-with-draw of individual runs of a standard giveaway, each with its own independent entrant list and end time.

## Requirements

### Requirement: Occurrence generation for recurring standard giveaways
The system SHALL generate upcoming occurrences for an `active` recurring standard giveaway within a rolling window, without generating duplicate occurrences for the same computed start time - the same generation mechanism as `event-occurrences`.

#### Scenario: Weekly recurrence generates the next occurrence
- **WHEN** an active standard giveaway has a weekly recurrence rule and its most recently generated occurrence's start time has passed
- **THEN** the system generates the next occurrence at the correct future date/time per the rule

### Requirement: One-off standard giveaways generate exactly one occurrence
The system SHALL generate exactly one occurrence for a non-recurring standard giveaway, at creation time, and SHALL NOT generate any further occurrences for it.

#### Scenario: One-off giveaway occurrence count
- **WHEN** a one-off standard giveaway is created
- **THEN** exactly one occurrence exists for it, now and after any later occurrence-generation run

### Requirement: Posting an occurrence to Discord
The system SHALL post each occurrence to Discord according to its giveaway's posting mode: as a new Discord thread in the configured channel, or as a new plain message in the configured channel, containing the prize item(s), the eligibility restriction (if any), the end time, and an "Enter" control.

#### Scenario: Occurrence posted with prize and restriction visible
- **WHEN** an occurrence is due to be posted
- **THEN** the system requests the bot post the message/thread naming the prize item(s), stating any booster/role restriction, showing the end time, and including an "Enter" control

### Requirement: Independent entrant list per occurrence
The system SHALL treat each occurrence's entrants as wholly independent of every other occurrence of the same standard giveaway series.

#### Scenario: Entries do not carry over
- **WHEN** a member enters one occurrence of a recurring standard giveaway
- **THEN** that entry has no effect on any other occurrence of the same giveaway

### Requirement: Automatic closing and drawing at end time
The system SHALL, once an occurrence's end time passes, close entries and randomly draw the giveaway's configured number of winners from that occurrence's eligible entrants, without requiring any further staff action.

#### Scenario: Draw with enough eligible entrants
- **WHEN** an occurrence's end time passes and at least as many eligible entrants exist as the configured winner count
- **THEN** exactly that many distinct winners are drawn at random from the eligible entrants

#### Scenario: Draw with fewer eligible entrants than winner count
- **WHEN** an occurrence's end time passes with fewer eligible entrants than the configured winner count
- **THEN** every eligible entrant is drawn as a winner and the occurrence closes without error

#### Scenario: Draw with zero eligible entrants
- **WHEN** an occurrence's end time passes with no entrants
- **THEN** the occurrence closes with zero winners

### Requirement: Fair prize assignment across multiple winners
The system SHALL, when a standard giveaway has more than one prize item, assign each drawn winner a distinct item for as long as unassigned items remain, falling back to a repeat once every item has been assigned to at least one winner - the same rule `giveaway-entry` uses for the pop-up giveaway.

#### Scenario: More prize items than winners
- **WHEN** an occurrence has more configured prize items than winners drawn
- **THEN** every winner receives a distinct item

#### Scenario: More winners than prize items
- **WHEN** an occurrence draws more winners than it has configured prize items
- **THEN** every winner still receives an item, with items repeating once the pool is exhausted
