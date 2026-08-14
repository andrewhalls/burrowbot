## MODIFIED Requirements

### Requirement: Member record sync
The system SHALL keep a per-guild member record (Discord user ID, username, display name, avatar) up to date from Discord activity.

#### Scenario: New member interacts
- **WHEN** a Discord member not yet known for a guild joins a giveaway or is otherwise observed in that guild
- **THEN** the system creates a member record scoped to that guild with their current Discord username, display name, and avatar

#### Scenario: Member changes their username
- **WHEN** a known member's Discord username differs from the stored value on their next observed interaction
- **THEN** the system updates the stored username to match

#### Scenario: Member changes their display name
- **WHEN** a known member's resolved display name (guild nickname, else account display name, else username) differs from the stored value on their next observed interaction
- **THEN** the system updates the stored display name to match

#### Scenario: Display name falls back to username where unset
- **WHEN** a member record has no display name recorded (never observed since this capability shipped, or Discord reported none)
- **THEN** anywhere the member's name is shown, the system displays their username instead
