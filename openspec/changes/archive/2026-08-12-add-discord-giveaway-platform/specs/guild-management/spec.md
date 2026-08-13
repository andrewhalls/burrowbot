## Purpose

Tracks which Discord guilds (servers) Burrow's bot is installed in and holds each guild's Burrow-specific settings, as the top-level scope every other capability is partitioned by.

## ADDED Requirements

### Requirement: Guild registration on bot install
The system SHALL record a guild the moment the bot is added to it, and SHALL treat every giveaway-related record as belonging to exactly one registered guild.

#### Scenario: Bot added to a new guild
- **WHEN** the bot process reports it has joined a Discord guild not yet known to the system
- **THEN** the system creates a guild record with that guild's Discord ID and name, marked active

#### Scenario: Bot removed from a guild
- **WHEN** the bot process reports it has been removed from a guild
- **THEN** the system marks that guild's record inactive without deleting its historical giveaways, collection themes, or entries

### Requirement: Guild-scoped data isolation
The system SHALL NOT allow any query, dashboard view, or bot action to mix data across two different guilds.

#### Scenario: Listing giveaways
- **WHEN** any part of the system lists giveaways, collection themes, or members "for a guild"
- **THEN** the result set is filtered to that guild's `guild_id` only

### Requirement: Guild settings
The system SHALL allow an authorized guild admin to configure guild-level defaults used when creating giveaways.

#### Scenario: Setting a default giveaway channel
- **WHEN** a guild admin sets a default channel for that guild
- **THEN** new giveaway drafts for that guild pre-fill that channel, without preventing selection of a different channel
