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
The system SHALL post each occurrence to Discord according to its giveaway's posting mode: as a new Discord thread in the configured channel, or as a new plain message in the configured channel, containing the prize item(s), the eligibility restriction (if any), the end time, the giveaway's image (if set), a winners section shown as pending, and an "Enter" control. If the giveaway has a banner image configured, the post SHALL also include it, positioned ahead of the giveaway's own content. An occurrence SHALL NOT be posted before its scheduled time arrives.

#### Scenario: Occurrence posted with prize and restriction visible
- **WHEN** an occurrence is due to be posted
- **THEN** the system requests the bot post the message/thread naming the prize item(s), stating any booster/role restriction, showing the end time, showing a pending winners section, and including an "Enter" control

#### Scenario: Occurrence posted with an image
- **WHEN** an occurrence whose giveaway has an image is due to be posted
- **THEN** the system requests the bot include that image in the posted message/thread

#### Scenario: Occurrence posted with a banner image
- **WHEN** an occurrence whose giveaway has a banner image is due to be posted
- **THEN** the system requests the bot include the banner image in the posted message, positioned ahead of the giveaway's own content

#### Scenario: Occurrence posted without a banner image
- **WHEN** an occurrence whose giveaway has no banner image configured is due to be posted
- **THEN** the system requests the bot post the message/thread without a banner, unaffected by its absence

#### Scenario: Occurrence not yet due is left scheduled
- **WHEN** a `scheduled` occurrence's scheduled post time has not yet arrived
- **THEN** the system does not post it, and it remains `scheduled`

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

### Requirement: Winners and claim details shown on the closed occurrence
The system SHALL, once an occurrence closes and winners are drawn, update the originally-posted Discord message to show the drawn winners and mark it as ended, so admins and members reading the original post see the outcome without needing a separate message.

#### Scenario: Original message updated with winners
- **WHEN** an occurrence with a winners section closes and winners are drawn
- **THEN** the system requests the bot update the original posted message's winners section to name the drawn winners and mark the giveaway as ended

#### Scenario: Original message updated with zero winners
- **WHEN** an occurrence closes with zero eligible entrants
- **THEN** the system requests the bot update the original posted message to show no winners and mark the giveaway as ended, without erroring

### Requirement: Separate winner announcement message with claim details
The system SHALL, once an occurrence closes and winners are drawn, post a new Discord message tagging the drawn winners, built from the giveaway's configured congratulations message template with the winners, prize name, claim link, and claim deadline substituted in - and SHALL skip posting this message if the giveaway has no congratulations message template configured.

#### Scenario: Announcement posted for a giveaway with a template configured
- **WHEN** an occurrence whose giveaway has a congratulations message template closes with at least one winner
- **THEN** the system requests the bot post a new message built from that template, tagging the drawn winners

#### Scenario: Announcement skipped for a giveaway with no template configured
- **WHEN** an occurrence whose giveaway has no congratulations message template closes
- **THEN** the system does not request any winner announcement message

#### Scenario: Claim deadline computed relative to announcement time
- **WHEN** a giveaway's claim deadline is configured as a number of hours and its occurrence closes with at least one winner
- **THEN** the claim deadline substituted into the announcement message is that many hours after the announcement is sent

#### Scenario: Announcement skipped with zero winners
- **WHEN** an occurrence closes with zero eligible entrants
- **THEN** the system does not request any winner announcement message, even if a congratulations message template is configured

### Requirement: Editing a single upcoming occurrence
The system SHALL let a guild admin edit a single `scheduled` occurrence's description, prize items, and image, independent of its series and every other occurrence, and SHALL reject editing once that occurrence is no longer `scheduled`.

#### Scenario: Editing a scheduled occurrence's description and prize items
- **WHEN** a guild admin edits a `scheduled` occurrence's description and/or prize items
- **THEN** the system saves the change against that occurrence only - the series' own template and every other occurrence (already generated or generated later) are unaffected

#### Scenario: Editing a scheduled occurrence's image
- **WHEN** a guild admin uploads a new image for a `scheduled` occurrence
- **THEN** the system records that image against that occurrence only

#### Scenario: Editing rejected once posted
- **WHEN** a guild admin attempts to edit an occurrence that is `posted` or `closed`
- **THEN** the system rejects the edit, so what already went to Discord for that occurrence never changes after the fact

#### Scenario: Browsing upcoming occurrences to edit
- **WHEN** a guild admin views a standard giveaway series with one or more `scheduled` occurrences
- **THEN** the system shows those upcoming occurrences, each reachable for editing
