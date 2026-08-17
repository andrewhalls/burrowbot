## ADDED Requirements

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
