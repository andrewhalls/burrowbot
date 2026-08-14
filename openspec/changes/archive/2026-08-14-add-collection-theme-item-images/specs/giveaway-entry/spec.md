## MODIFIED Requirements

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
