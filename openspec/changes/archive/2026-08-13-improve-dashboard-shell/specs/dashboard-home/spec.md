## MODIFIED Requirements

### Requirement: Per-guild navigation
The system SHALL, for a user viewing any page scoped to a specific guild, provide navigation to that guild's other pages (settings, collection themes, event role sets, events, giveaways, standard giveaways) as a persistent, consistently-positioned part of the dashboard shell present on every page, without requiring the user to know or type a URL.

#### Scenario: Navigating between a guild's pages
- **WHEN** an authenticated guild admin is viewing any page scoped to a guild they administer
- **THEN** the system shows links to that same guild's other pages

#### Scenario: Navigation respects guild scoping
- **WHEN** an authenticated guild admin is viewing pages scoped to Guild A
- **THEN** the navigation shown links only to Guild A's pages, never to another guild's pages

#### Scenario: Consistent position across pages
- **WHEN** a guild admin navigates between different guild-scoped pages
- **THEN** the navigation appears in the same position on every page, as persistent shell chrome rather than content embedded partway down each page

## ADDED Requirements

### Requirement: Shell applies automatically to every page, present and future
The system SHALL render the dashboard shell (sidebar, top bar, theme) for every authenticated page via the app's shared page layout, without requiring any page-specific configuration to opt in.

#### Scenario: A new page inherits the shell with no extra work
- **WHEN** a new authenticated page is added using the app's standard full-page component convention
- **THEN** it renders inside the same shell as every existing page, without that page's own code configuring or requesting it

### Requirement: Guild switcher
The system SHALL provide a control, visible on every authenticated page, for the user to switch which of their administered guilds they are viewing. Selecting a guild SHALL navigate to the same page type for that guild when the current page has a per-guild equivalent, or to that guild's dashboard/settings entry point otherwise.

#### Scenario: Switching to an equivalent page
- **WHEN** a guild admin viewing a page type that exists across guilds (e.g. Events) selects a different administered guild from the switcher
- **THEN** they land on that same page type for the newly selected guild

#### Scenario: Switching from a page with no per-guild equivalent
- **WHEN** a guild admin viewing a page scoped to one specific record (e.g. a single event occurrence's roster) selects a different guild from the switcher
- **THEN** they land on that guild's dashboard/settings entry point instead

#### Scenario: Switcher only lists administered guilds
- **WHEN** the guild switcher is shown
- **THEN** it lists only guilds the authenticated user administers, matching the dashboard home's own guild list
