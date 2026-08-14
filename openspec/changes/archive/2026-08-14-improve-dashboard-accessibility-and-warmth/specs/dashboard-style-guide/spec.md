## ADDED Requirements

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
