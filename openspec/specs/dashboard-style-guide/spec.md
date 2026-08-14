## Purpose

Documents the dashboard's durable visual design system - spacing, card/tile treatment, list density, and empty states - as a checkable reference so future dashboard screens stay visually consistent instead of drifting.

## Requirements

### Requirement: List items render as distinct cards, not divided rows
The system SHALL render every list of items (giveaways, events, occurrences, themes, role sets, and any future guild-scoped list) as visually distinct, spaced-apart cards - each with its own rounded border/background and shadow - rather than as rows separated only by a thin divider line.

#### Scenario: Viewing any guild-scoped list
- **WHEN** a guild admin views any list screen in the dashboard
- **THEN** each item in the list renders as its own visually distinct card with spacing between cards, not a thin-divider row sharing one continuous border

### Requirement: Consistent spacing scale
The system SHALL apply a consistent spacing scale (card padding, gap between cards, gap between major page sections) across every dashboard screen, defined once and reused rather than redeclared ad hoc per screen.

#### Scenario: Comparing two different list screens
- **WHEN** a guild admin views two different list screens (e.g. Events and Popup Giveaways)
- **THEN** card padding, inter-card spacing, and section spacing are visually identical between them

### Requirement: Empty states are explicit, not blank
The system SHALL show an explicit, styled empty-state message (not a blank area) wherever a list or a detail panel has nothing to display.

#### Scenario: A list has no items
- **WHEN** a guild admin views a list screen for a guild with zero items of that type
- **THEN** the system shows a styled message explaining there's nothing yet, not an empty card area

#### Scenario: No detail item selected
- **WHEN** a guild admin views a list-detail screen without having selected an item
- **THEN** the detail panel shows a styled placeholder inviting selection, not a blank area

### Requirement: Keyboard focus is visible
The system SHALL show a visible focus indicator on every natively-focusable element (links, buttons, form controls) when it receives keyboard focus, using the accent color so it reads as a deliberate part of the theme rather than a browser default.

#### Scenario: Tabbing through a screen
- **WHEN** a guild admin navigates a dashboard screen using the keyboard (Tab/Shift+Tab)
- **THEN** the currently-focused element shows a clearly visible outline in the accent color

#### Scenario: Mouse/touch interaction stays unchanged
- **WHEN** a guild admin clicks or taps an element with a mouse or touchscreen
- **THEN** no focus outline appears, matching the element's existing hover/active styling

### Requirement: Multi-column layouts adapt to narrow viewports
The system SHALL collapse any 2-column tile grid or paired form-field layout to a single column below the `sm` breakpoint, so phone-width viewports never show two cramped columns side by side.

#### Scenario: Viewing a list screen on a phone-width viewport
- **WHEN** a guild admin views any tile-based list screen (giveaways, standard giveaways, events, themes, role sets) on a viewport narrower than the `sm` breakpoint
- **THEN** tiles render one per row instead of two per row

#### Scenario: Viewing a create/edit form on a phone-width viewport
- **WHEN** a guild admin views a create or edit form containing paired fields (e.g. channel + role set, start date + start time) on a viewport narrower than the `sm` breakpoint
- **THEN** each field in the pair renders on its own row instead of side by side
