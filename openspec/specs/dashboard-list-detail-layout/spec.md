## Purpose

Defines the reusable master-detail structure - a list of card tiles alongside a detail panel that opens when a tile is selected - used by every guild-scoped list screen in the dashboard, including its responsive collapse behavior on narrow screens.

## Requirements

### Requirement: Selecting a tile opens its detail in a side panel
The system SHALL, on a wide enough screen, show a list of card tiles alongside a detail panel, and SHALL populate that panel with the selected tile's detail content when a guild admin selects it, without a full page navigation.

#### Scenario: Selecting a tile
- **WHEN** a guild admin selects a tile in a list-detail screen on a wide screen
- **THEN** the detail panel updates to show that item's detail content, and the tile list remains visible alongside it

#### Scenario: Nothing selected yet
- **WHEN** a guild admin opens a list-detail screen without having selected a tile
- **THEN** the detail panel shows an empty-state placeholder rather than any item's content

### Requirement: Detail panel replaces the list on narrow screens
The system SHALL, below a responsive width threshold, show only one of the tile list or the selected item's detail panel at a time - never both simultaneously - with a control to return from the detail panel to the list.

#### Scenario: Selecting a tile on a narrow screen
- **WHEN** a guild admin selects a tile on a narrow screen
- **THEN** the tile list is replaced by that item's detail panel, shown full width, with a control to return to the list

#### Scenario: Returning to the list on a narrow screen
- **WHEN** a guild admin activates the return control while viewing a detail panel on a narrow screen
- **THEN** the tile list is shown again, full width, and no item is selected

### Requirement: Direct routes to a detail view remain reachable
The system SHALL continue to support navigating directly to a specific item's full detail view via its own URL, independent of the list-detail panel's in-page selection state.

#### Scenario: Following a direct link
- **WHEN** a guild admin follows a direct link to a specific giveaway's or occurrence's detail page
- **THEN** the system shows that item's detail content, whether or not it was reached via the list-detail panel
