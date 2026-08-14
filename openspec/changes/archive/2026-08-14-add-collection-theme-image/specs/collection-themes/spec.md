## MODIFIED Requirements

### Requirement: Collection theme creation
The system SHALL allow a guild admin to create a collection theme with a name, an ordered list of at least one item, and an optional image, scoped to their guild.

#### Scenario: Valid collection theme created
- **WHEN** a guild admin submits a collection theme name and one or more item names
- **THEN** the system saves a collection theme record with its items in the `collection_themes`/`collection_theme_items` tables tied to that guild

#### Scenario: Collection theme with no items rejected
- **WHEN** a guild admin submits a collection theme with zero items
- **THEN** the system rejects the submission with a validation error and creates nothing

#### Scenario: Theme image is optional
- **WHEN** a guild admin creates a collection theme without an image
- **THEN** the theme is saved successfully with no image set

#### Scenario: Theme image recorded when provided
- **WHEN** a guild admin creates a collection theme with an uploaded image
- **THEN** the system records that image against the theme

## ADDED Requirements

### Requirement: Collection theme image management
The system SHALL let a guild admin set, replace, or remove a collection theme's own image at any time after creation, independent of that theme's per-item images.

#### Scenario: Set image on a theme with none
- **WHEN** a guild admin uploads an image for a collection theme that currently has no image
- **THEN** the system records that image against the theme and it is shown wherever the theme is displayed

#### Scenario: Replace an existing theme image
- **WHEN** a guild admin uploads a new image for a collection theme that already has one
- **THEN** the system replaces the stored image with the newly uploaded one

#### Scenario: Remove a theme image
- **WHEN** a guild admin removes a collection theme's image
- **THEN** the theme reverts to having no image, unaffected by any of its items' own images
