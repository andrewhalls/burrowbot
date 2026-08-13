# member-directory Specification

## Purpose

Maintains a per-guild directory of Discord members, kept in sync from Discord, so entries can be attributed to a real member and staff can search/filter by member on the dashboard.

## Requirements

### Requirement: Member record sync
The system SHALL keep a per-guild member record (Discord user ID, username/global name, avatar) up to date from Discord activity.

#### Scenario: New member interacts
- **WHEN** a Discord member not yet known for a guild joins a giveaway or is otherwise observed in that guild
- **THEN** the system creates a member record scoped to that guild with their current Discord username and avatar

#### Scenario: Member changes their username
- **WHEN** a known member's Discord username differs from the stored value on their next observed interaction
- **THEN** the system updates the stored username to match

### Requirement: Member search
The system SHALL support searching members by partial username or Discord ID, scoped to a single guild.

#### Scenario: Partial username search
- **WHEN** staff search for members in Guild A using a partial username
- **THEN** the system returns only Guild A members whose username contains the search text, case-insensitively

#### Scenario: Search does not leak other guilds
- **WHEN** staff search for a username that matches a member of Guild B but not of Guild A
- **THEN** a search scoped to Guild A SHALL NOT return that Guild B member
