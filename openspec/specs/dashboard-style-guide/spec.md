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
