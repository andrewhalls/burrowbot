## MODIFIED Requirements

### Requirement: Authorization revoked upstream
The system SHALL, on every login or periodic re-sync, revoke a user's **full (Discord-synced)** dashboard access to a guild once their Discord admin permission (`ADMINISTRATOR` or `MANAGE_GUILD`) for that guild is removed. This revocation SHALL NOT affect a separately-granted, section-scoped admin access the same user may hold for that guild (see `guild-admin-permissions`) - that access is revoked only by an explicit revoke action or by the user leaving the guild entirely, never by a Discord permission change alone.

#### Scenario: Full admin role revoked in Discord
- **WHEN** a user's admin role for a guild is removed in Discord and the user's authorization is next re-checked (login or periodic re-sync)
- **THEN** the system revokes that user's full dashboard access to the guild

#### Scenario: Losing the Discord admin bit does not affect a separate scoped grant
- **WHEN** a user who also holds a section-scoped admin grant for a guild loses their Discord `ADMINISTRATOR`/`MANAGE_GUILD` permission for that guild, and their authorization is next re-checked
- **THEN** the system revokes only their full-admin status, and they retain access to exactly the sections their scoped grant covers

## ADDED Requirements

### Requirement: A pending scoped admin grant resolves on first login
The system SHALL, when a user completes Discord OAuth login, resolve any section-scoped admin grant that was created for their Discord account before they had ever logged in, associating it with their now-created user record.

#### Scenario: First login resolves a pending grant
- **WHEN** a Discord member who was granted scoped admin access before ever visiting Burrow completes Discord OAuth login for the first time
- **THEN** the system links their new user record to that grant, and they can immediately access the granted section(s) for that guild

### Requirement: Scoped admin access ends when guild membership ends
The system SHALL revoke a user's section-scoped admin grant for a guild once Discord reports that user is no longer a member of that guild at all, checked at the same login/re-sync points as full-admin revocation.

#### Scenario: Leaving the guild revokes a scoped grant
- **WHEN** a scoped admin leaves the Discord guild entirely, and their authorization is next re-checked
- **THEN** the system revokes their scoped admin grant for that guild, distinct from merely losing an elevated Discord permission while remaining a member
