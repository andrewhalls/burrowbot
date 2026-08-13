# dashboard-theme Specification

## Purpose

Governs the dashboard's visual theme: a dark and a light mode, a user-facing toggle, and persistence of the user's choice across visits.

## Requirements

### Requirement: Default theme
The system SHALL render the dashboard in dark mode by default for every user, regardless of system or browser preference.

#### Scenario: First visit defaults to dark
- **WHEN** a user without a previously saved theme preference views any dashboard page
- **THEN** the page renders in dark mode

### Requirement: Theme toggle
The system SHALL provide a control, visible on every authenticated page, positioned top-right, that switches between dark and light mode.

#### Scenario: Toggling switches the visible theme
- **WHEN** a user activates the theme toggle
- **THEN** the page's visible colors switch from dark mode to light mode, or from light mode to dark mode

### Requirement: Theme choice persistence
The system SHALL remember a user's theme choice once changed, and apply it on their subsequent visits without requiring them to toggle again.

#### Scenario: Choice persists across visits
- **WHEN** a user switches to light mode and later returns in a new browsing session
- **THEN** the dashboard renders in light mode without the user needing to toggle again
