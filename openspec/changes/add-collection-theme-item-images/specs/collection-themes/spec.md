## MODIFIED Requirements

### Requirement: Collection theme item management
The system SHALL allow items to be added to or removed from a collection theme's item list while that theme is not in use by an active giveaway, and SHALL let a guild admin attach an optional image to an item.

#### Scenario: Add item to existing collection theme
- **WHEN** a guild admin adds a new item to an existing collection theme that has no active giveaway using it
- **THEN** the item is appended to that collection theme's item list

#### Scenario: Editing items blocked while a giveaway is active
- **WHEN** a guild admin attempts to add or remove items on a collection theme that is currently referenced by an active (not yet closed) giveaway
- **THEN** the system rejects the edit, so the prize pool cannot change while entrants are joining

#### Scenario: Item image is optional
- **WHEN** a guild admin adds an item without an image
- **THEN** the item is saved successfully with no image set

#### Scenario: Item image recorded when provided
- **WHEN** a guild admin adds or edits an item with an uploaded image
- **THEN** the system records that image against the item
