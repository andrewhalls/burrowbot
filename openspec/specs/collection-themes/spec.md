# collection-themes Specification

## Purpose

Stores reusable, admin-managed themed collections of prize items — "collection themes" — as first-class, guild-scoped records that a giveaway draws its random prizes from, rather than being hardcoded per giveaway.

## Requirements

### Requirement: Collection theme creation
The system SHALL allow a guild admin to create a collection theme with a name and an ordered list of at least one item, scoped to their guild.

#### Scenario: Valid collection theme created
- **WHEN** a guild admin submits a collection theme name and one or more item names
- **THEN** the system saves a collection theme record with its items in the `collection_themes`/`collection_theme_items` tables tied to that guild

#### Scenario: Collection theme with no items rejected
- **WHEN** a guild admin submits a collection theme with zero items
- **THEN** the system rejects the submission with a validation error and creates nothing

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

### Requirement: Collection theme reuse
The system SHALL allow the same collection theme to be selected as the prize pool for more than one giveaway, including giveaways that ran previously.

#### Scenario: Reuse in a new giveaway
- **WHEN** a guild admin creates a new giveaway and selects a collection theme previously used by a closed giveaway
- **THEN** the new giveaway is created referencing that collection theme's current item list, independent of any prior giveaway's results
