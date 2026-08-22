# guild-admin-permissions Specification

## Purpose

Lets a full guild admin grant another Discord member access to a chosen subset of Burrow's dashboard sections (Settings, Collection themes, Event role sets, Events, Popup giveaways, Standard giveaways, Broadcasts) instead of full admin access, and enforces that grant everywhere the dashboard would otherwise assume any admin can do anything in the guild.

## Requirements

### Requirement: Granting a section-scoped admin
The system SHALL let a full (Discord-synced) admin grant another Discord member of the guild access to one or more of the seven dashboard sections, searched from the guild's synced member directory, and SHALL reject a grant with zero sections selected.

#### Scenario: Granting access to a single section
- **WHEN** a full admin selects a guild member and grants them only the "Popup giveaways" section
- **THEN** the system creates a scoped admin grant for that member limited to popup giveaways, and no other section

#### Scenario: Granting access to multiple sections
- **WHEN** a full admin grants a member both "Popup giveaways" and "Broadcasts"
- **THEN** the scoped admin can access exactly those two sections and no others

#### Scenario: Granting with no sections selected rejected
- **WHEN** a full admin attempts to submit a grant with no section selected
- **THEN** the system rejects the submission with a validation error and creates no grant

#### Scenario: Invite search is scoped to the guild's known members
- **WHEN** a full admin searches for someone to grant access to
- **THEN** the system searches only that guild's synced member directory, the same data source used elsewhere for member search, never another guild's members

#### Scenario: A member the bot has never observed cannot yet be found
- **WHEN** a full admin searches for a Discord member who has not yet been synced into the guild's member directory
- **THEN** the system does not offer them as a selectable option

### Requirement: Editing a scoped admin's sections
The system SHALL let a full admin change which sections a previously-granted scoped admin can access, replacing the grant's section list rather than adding to it.

#### Scenario: Adding a section to an existing grant
- **WHEN** a full admin edits a scoped admin who currently has only "Popup giveaways" and adds "Broadcasts"
- **THEN** that scoped admin can now access both sections

#### Scenario: Removing a section from an existing grant
- **WHEN** a full admin edits a scoped admin to remove a previously-granted section
- **THEN** that scoped admin immediately loses access to the removed section, keeping access to any sections still granted

### Requirement: Revoking a scoped admin
The system SHALL let a full admin permanently revoke a scoped admin's grant, removing all of their access to the guild.

#### Scenario: Revoking a scoped admin
- **WHEN** a full admin revokes a scoped admin's grant
- **THEN** that person immediately loses access to every section of the guild they previously held, and no longer appears as an admin of that guild

### Requirement: Discord-synced admins cannot be revoked from the dashboard
The system SHALL NOT offer a Revoke action for a full (Discord-synced) admin from the Admins screen; full-admin access can only be changed by changing that user's permissions in Discord itself.

#### Scenario: No revoke control for a full admin
- **WHEN** a full admin views the Admins screen's list of a guild's admins
- **THEN** entries for other full (Discord-synced) admins show no Revoke control, only entries for scoped (granted) admins do

### Requirement: Admin management restricted to full admins
The system SHALL restrict viewing and managing the Admins screen (inviting, editing sections, revoking) to full (Discord-synced) admins of the guild; a scoped admin SHALL NOT be able to reach it, regardless of which sections they hold.

#### Scenario: Scoped admin denied the Admins screen
- **WHEN** a scoped admin, holding any combination of granted sections, attempts to view or act on the Admins screen
- **THEN** the system denies the request with a 403 response

#### Scenario: Full admin can manage admins
- **WHEN** a full admin of the guild views the Admins screen
- **THEN** the system shows every admin of that guild (both tiers) and their access, with invite/edit/revoke controls available per Decision 4

### Requirement: Section-gated access to guarded dashboard content
The system SHALL deny a scoped admin's view or management of a dashboard section's resources (Settings, Collection themes, Event role sets, Events, Popup giveaways, Standard giveaways, Broadcasts) unless that section is included in their grant, both when navigating and when requesting that section's routes directly.

#### Scenario: Scoped admin denied an ungranted section's resources
- **WHEN** a scoped admin granted only "Popup giveaways" requests to view or manage an event, a standard giveaway, a broadcast, collection themes, event role sets, or guild settings
- **THEN** the system denies each request with a 403 response

#### Scenario: Scoped admin allowed their granted section's resources
- **WHEN** a scoped admin granted "Popup giveaways" views or manages a popup giveaway in their guild
- **THEN** the system allows the request exactly as it would for a full admin

#### Scenario: Direct URL access enforced the same as navigation
- **WHEN** a scoped admin without a section's grant follows a direct link to that section's page
- **THEN** the system denies the request with a 403 response, identical to what happens when that section's link isn't shown to them at all

### Requirement: Full admin access is unaffected
The system SHALL continue to let a full (Discord-synced) admin view and manage every one of the seven dashboard sections in their guild, exactly as before this capability existed.

#### Scenario: Full admin unaffected by the existence of scoped admins
- **WHEN** a full admin of a guild that also has one or more scoped admins views or manages any of the seven sections
- **THEN** the system allows every request exactly as it did before scoped admins existed

### Requirement: Sidebar reflects granted sections
The system SHALL show, in the dashboard sidebar, only the sections the current user holds access to for the currently-selected guild - all seven for a full admin, exactly the granted subset for a scoped admin.

#### Scenario: Scoped admin's sidebar shows only granted sections
- **WHEN** a scoped admin granted only "Popup giveaways" and "Broadcasts" views the dashboard sidebar for that guild
- **THEN** only those two section links are shown, and no others

#### Scenario: Full admin's sidebar is unaffected
- **WHEN** a full admin views the dashboard sidebar for a guild
- **THEN** all seven section links are shown, exactly as before this capability existed

#### Scenario: Guild switcher and dashboard home remain visible regardless of granted sections
- **WHEN** a scoped admin with any combination of granted sections views the guild switcher or the dashboard home/overview page
- **THEN** the system shows their guild and the overview page, independent of which specific sections they hold
