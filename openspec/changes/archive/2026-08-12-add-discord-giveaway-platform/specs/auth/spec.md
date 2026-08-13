## Purpose

Lets staff sign in with their Discord account and grants them dashboard access only for the specific guild(s) they administer.

## ADDED Requirements

### Requirement: Discord OAuth sign-in
The system SHALL authenticate dashboard users exclusively via Discord OAuth (no local password accounts).

#### Scenario: Successful login
- **WHEN** a user completes the Discord OAuth consent flow successfully
- **THEN** the system creates or updates a local user record from the returned Discord profile (Discord ID, username, avatar) and starts an authenticated session

#### Scenario: User denies consent
- **WHEN** a user cancels or denies the Discord OAuth consent screen
- **THEN** the system redirects back to the login page with an error message and does not create a session

### Requirement: Per-guild admin authorization
The system SHALL scope every dashboard authorization decision to a specific guild; a user authorized in one guild SHALL NOT gain access to another guild's data by virtue of that authorization.

#### Scenario: No guild access
- **WHEN** an authenticated user has no admin authorization for any registered guild
- **THEN** the dashboard shows no guilds and no giveaway data, rather than an empty-but-implied-authorized state

#### Scenario: Cross-guild access denied
- **WHEN** a user authorized as admin of Guild A requests a giveaway, collection theme, or member record belonging to Guild B
- **THEN** the system denies the request with a 403 response, regardless of the user's role in Guild A

#### Scenario: Authorization revoked upstream
- **WHEN** a user's admin role for a guild is removed in Discord and the user's authorization is next re-checked (login or periodic re-sync)
- **THEN** the system revokes that user's dashboard access to the guild
