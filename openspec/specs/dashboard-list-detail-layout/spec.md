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

### Requirement: Creating a new item uses the detail panel
The system SHALL show the "create new" form for a list-detail screen inside the detail panel - the same space used to view/edit a selected item - rather than as a separate block that pushes the list-detail area down the page. Opening the create form SHALL deselect any currently-selected item, and selecting a tile SHALL close an open create form, so the detail panel always shows exactly one thing.

#### Scenario: Opening the create form
- **WHEN** a guild admin activates "+ New X" on a list-detail screen
- **THEN** the detail panel shows the create form in place of whatever it was previously showing, and the list-detail area's position on the page does not shift

#### Scenario: Selecting a tile while creating
- **WHEN** a guild admin selects a tile while the create form is open
- **THEN** the create form closes and the detail panel shows the selected tile's detail content instead

#### Scenario: New item is selected after creation
- **WHEN** a guild admin successfully submits the create form
- **THEN** the newly created item is selected, and the detail panel shows its detail content

### Requirement: Selected item actions render in the header row
The system SHALL show a selected item's contextual actions (e.g. Edit, Edit series, Start, Delete) in the same header row as the screen's own "+ New X" action, not in a separate row inside the detail panel, so every list-detail screen has exactly one header row.

#### Scenario: Selecting an item reveals its actions in the header
- **WHEN** a guild admin selects a tile that has contextual actions available
- **THEN** those actions appear in the page's header row, alongside "+ New X", not inside the detail panel

#### Scenario: No item selected shows no contextual actions
- **WHEN** a guild admin has not selected a tile, or has deselected one
- **THEN** the header row shows only "+ New X"
