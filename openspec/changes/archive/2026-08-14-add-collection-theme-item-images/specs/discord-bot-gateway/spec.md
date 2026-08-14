## MODIFIED Requirements

### Requirement: Announcing drawn winners
The system SHALL, on request from Laravel after an occurrence closes, update or reply to that occurrence's Discord message/thread announcing the drawn winners and, when more than one prize item was configured, which item each winner received, including each won item's image when it has one.

#### Scenario: Winners announced after a draw
- **WHEN** Laravel requests winners be announced for a closed occurrence
- **THEN** the bot posts the winner announcement in the occurrence's original channel/thread, naming each winner and their assigned item

#### Scenario: Winner's item has an image
- **WHEN** a winner's assigned item has an image
- **THEN** the winner announcement includes that image alongside that winner's name and item
