## ADDED Requirements

### Requirement: Collection theme duplication
The system SHALL let a guild admin duplicate an existing collection theme, creating a new theme (scoped to the same guild) with a derived name, a copy of the source theme's own image, and a copy of every item (name, image, order).

#### Scenario: Duplicating a theme copies its items
- **WHEN** a guild admin duplicates a collection theme that has one or more items
- **THEN** the system creates a new theme with the same items (name, image, and relative order preserved), independent of the source theme - editing either afterward does not affect the other

#### Scenario: Duplicating a theme copies its own image
- **WHEN** a guild admin duplicates a collection theme that has its own image set
- **THEN** the new theme is created with that same image

#### Scenario: Duplicate name is derived, not identical
- **WHEN** a guild admin duplicates a collection theme named "Retro Arcade"
- **THEN** the new theme is created with a derived name (e.g. "Retro Arcade (Copy)"), not the identical name
