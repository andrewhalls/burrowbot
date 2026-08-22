# giveaway-entry Specification

## Purpose

Governs a Discord member joining an active giveaway by clicking "Join Giveaway" and being immediately, fairly assigned one random item from the giveaway's collection theme, with the result shown back to them.

## Requirements

### Requirement: Joining an active giveaway
The system SHALL let a Discord member create exactly one entry in a giveaway that is currently `active`, and SHALL assign an item to that entry at the moment it is created.

#### Scenario: First join succeeds
- **WHEN** a member who has not yet entered clicks "Join Giveaway" on an `active` giveaway
- **THEN** the system creates an entry for that member, assigns it a random item per the assignment rule below, and returns the assigned item to be shown to the member

### Requirement: One entry per member
The system SHALL prevent a member from receiving more than one entry, and therefore more than one prize, in the same giveaway.

#### Scenario: Duplicate join attempt
- **WHEN** a member who already has an entry in a giveaway clicks "Join Giveaway" again
- **THEN** the system does not create a second entry or re-roll an item, and instead returns that member's existing assigned item so they can see what they already won

### Requirement: Entry rejected once expired, enforced server-side
The system SHALL reject any join request received after the giveaway's `ends_at`, independent of whether the Discord button still appeared clickable to the member.

#### Scenario: Late click after server-side expiry
- **WHEN** a join request for a giveaway arrives at the system after that giveaway's `ends_at` has passed, whether or not the giveaway has been transitioned to `closed` yet by the scheduled process
- **THEN** the system rejects the entry and returns a "giveaway has ended" result, without assigning an item

#### Scenario: Click just before expiry
- **WHEN** a join request arrives strictly before `ends_at`
- **THEN** the system accepts and processes the entry even if `ends_at` passes microseconds later

### Requirement: Random item assignment without early repeats
The system SHALL assign each entrant a random item from the giveaway's collection theme, avoiding handing out an item that has already been won by another entrant in the same giveaway for as long as unwon items remain.

#### Scenario: More items than entrants
- **WHEN** a giveaway's collection theme has more items than the number of members who join
- **THEN** every entrant receives a distinct item and no item is awarded to two entrants

#### Scenario: Prize pool exhausted
- **WHEN** every item in the giveaway's collection theme has already been won by at least one entrant and another member joins
- **THEN** the system assigns that entrant a random item from the full collection theme's item list, allowing a repeat, rather than rejecting the entry

### Requirement: Entrant sees their result
The system SHALL announce a new win publicly, visible to everyone in the channel, naming the winner and the assigned item (including that item's image when it has one). The system SHALL cause a member who did not just win (duplicate entry, or the giveaway had already ended) to see a private reply, visible only to them, explaining the outcome.

#### Scenario: Successful entry feedback
- **WHEN** an entry is accepted and assigned an item
- **THEN** the system announces the win publicly in the channel, naming the member and the assigned item

#### Scenario: Rejected entry feedback
- **WHEN** an entry is rejected for being late or duplicate
- **THEN** the member receives a Discord reply, visible only to them, explaining why (giveaway ended, or showing their existing prize) - this is not announced publicly

#### Scenario: Assigned item has an image
- **WHEN** a new entry is accepted and the assigned item has an image
- **THEN** the public win announcement includes that image alongside the item's name

#### Scenario: Assigned item has no image
- **WHEN** a new entry is accepted and the assigned item has no image
- **THEN** the public win announcement still names the winner and item, with no image

### Requirement: Per-winner templated message sent on a new win
The system SHALL, when a giveaway has both a winner-message channel and template configured, send a new Discord message to that channel each time a member wins - built from the template with the winning member's mention and the won item's name substituted in - independent of and in addition to the existing public win announcement. The system SHALL NOT send this message when either field is unconfigured, and SHALL NOT send it for a rejected entry (duplicate or expired).

#### Scenario: Message sent when both fields are configured
- **WHEN** a member wins on a giveaway with both a winner-message channel and template configured
- **THEN** the system requests the bot send a new message to that channel, with the template's placeholders substituted for that winner and their item

#### Scenario: Message skipped when unconfigured
- **WHEN** a member wins on a giveaway with no winner-message channel or template configured
- **THEN** the system does not request any per-winner message

#### Scenario: Message skipped for a rejected entry
- **WHEN** a join attempt is rejected as a duplicate or as expired
- **THEN** the system does not request a per-winner message, even if the giveaway has both fields configured

#### Scenario: Template with no placeholders
- **WHEN** a winner-message template contains neither `{winner}` nor `{prize}`
- **THEN** the system still sends it, unchanged, to the configured channel

#### Scenario: Existing public/private result replies are unaffected
- **WHEN** a member wins or is rejected on a giveaway with a winner-message channel and template configured
- **THEN** the existing public win announcement or private rejection reply in the giveaway's own channel still happens exactly as it does without this feature configured
