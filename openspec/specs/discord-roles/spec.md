## Purpose

Keeps Laravel's record of each guild's Discord roles in sync with Discord, and defines the searchable, preset-enabled multi-select-role-picker contract every role-selecting field in the dashboard uses instead of a raw text field.

## Requirements

### Requirement: Role sync on guild join
The system SHALL, when the bot joins a Discord guild, fetch and record that guild's current roles, excluding the `@everyone` role and any Discord-managed role (bot integration roles, the auto-created Server Booster role).

#### Scenario: Bot joins a new guild
- **WHEN** the bot joins a Discord guild for the first time
- **THEN** the system records every non-managed, non-`@everyone` role in that guild, each with its Discord role ID and name

### Requirement: Role sync stays current
The system SHALL keep a guild's synced roles current as roles are created, renamed, or deleted on Discord, and SHALL periodically re-sync as a fallback.

#### Scenario: Role created
- **WHEN** a non-managed role is created in a synced guild
- **THEN** the system adds it to that guild's synced role list

#### Scenario: Role renamed
- **WHEN** a synced role is renamed on Discord
- **THEN** the system updates its recorded name

#### Scenario: Role deleted
- **WHEN** a synced role is deleted on Discord
- **THEN** the system removes it from that guild's synced role list

### Requirement: Searchable multi-select role picker with presets
The system SHALL let a guild admin select one or more roles for any role-selecting field by searching and choosing from that guild's synced roles, by name, with the guild's existing Event Role Sets offered as one-click presets that bulk-select all of that set's underlying roles.

#### Scenario: Selecting individual roles
- **WHEN** a guild admin searches the role picker on any role-selecting form
- **THEN** the system shows matching synced roles from that guild by name, and selecting one or more adds them to the field's selection

#### Scenario: Selecting a preset
- **WHEN** a guild admin selects an existing Event Role Set shown as a preset at the top of the picker
- **THEN** the system adds every one of that role set's underlying Discord roles to the current selection

#### Scenario: Picker excludes managed and @everyone roles
- **WHEN** a guild admin opens the role picker
- **THEN** the `@everyone` role and Discord-managed roles do not appear as options

#### Scenario: Picker is guild-scoped
- **WHEN** a guild admin uses the role picker on a form for Guild A
- **THEN** only Guild A's synced roles and Event Role Sets appear, never another guild's
