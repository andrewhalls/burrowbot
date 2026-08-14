## Purpose

Keeps Laravel's record of each guild's postable Discord channels in sync with Discord, and defines the searchable-picker contract every channel-selecting form in the dashboard uses instead of a raw text field.

## Requirements

### Requirement: Channel sync on guild join
The system SHALL, when the bot joins a Discord guild, fetch and record that guild's current postable channels (text and announcement channels; not voice, category, forum, or thread channels).

#### Scenario: Bot joins a new guild
- **WHEN** the bot joins a Discord guild for the first time
- **THEN** the system records every postable channel in that guild, each with its Discord channel ID and name

### Requirement: Channel sync stays current
The system SHALL keep a guild's synced channels current as channels are created, renamed, or deleted on Discord, and SHALL periodically re-sync as a fallback.

#### Scenario: Channel created
- **WHEN** a postable channel is created in a synced guild
- **THEN** the system adds it to that guild's synced channel list

#### Scenario: Channel renamed
- **WHEN** a synced channel is renamed on Discord
- **THEN** the system updates its recorded name

#### Scenario: Channel deleted
- **WHEN** a synced channel is deleted on Discord
- **THEN** the system removes it from that guild's synced channel list

### Requirement: Searchable channel picker
The system SHALL let a guild admin select a channel for any channel-selecting field by searching and choosing from that guild's synced postable channels, by name, rather than typing a raw channel ID.

#### Scenario: Selecting a channel
- **WHEN** a guild admin searches the channel picker on any channel-selecting form
- **THEN** the system shows matching postable channels from that guild by name, and selecting one sets the field to that channel's Discord ID

#### Scenario: Picker excludes non-postable channels
- **WHEN** a guild admin opens the channel picker
- **THEN** voice channels, categories, forums, and threads do not appear as options

#### Scenario: Picker is guild-scoped
- **WHEN** a guild admin uses the channel picker on a form for Guild A
- **THEN** only Guild A's synced channels appear, never another guild's
