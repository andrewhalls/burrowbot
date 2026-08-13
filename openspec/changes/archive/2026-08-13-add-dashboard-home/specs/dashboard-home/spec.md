## Purpose

Gives a logged-in user a working landing page after Discord OAuth sign-in: a list of the guilds they administer with entry points into each guild's pages, or onboarding instructions (including a bot-invite link) when they administer none yet.

## ADDED Requirements

### Requirement: Dashboard lists the user's administered guilds
The system SHALL, on the dashboard home page, list every guild the authenticated user currently administers (per the `auth` capability's per-guild admin authorization), and SHALL NOT list any guild the user does not administer.

#### Scenario: User administers one or more guilds
- **WHEN** an authenticated user who administers at least one guild views the dashboard
- **THEN** the system lists each of those guilds, and each listed guild links into that guild's pages

#### Scenario: Guild list excludes non-administered guilds
- **WHEN** an authenticated user administers Guild A but not Guild B
- **THEN** Guild B does not appear anywhere in that user's dashboard, regardless of Guild B's existence in the system

### Requirement: Zero-guild onboarding
The system SHALL, when an authenticated user administers no guilds, show onboarding content instead of an empty list: an explanation that the Discord bot must be invited to their server and that they need the "Manage Server" permission on that server for it to appear, and a link to invite the bot.

#### Scenario: No administered guilds
- **WHEN** an authenticated user who administers no guilds views the dashboard
- **THEN** the system shows onboarding instructions and a bot-invite link instead of an empty or blank guild list

#### Scenario: Onboarding instructions are non-technical
- **WHEN** the onboarding content is shown
- **THEN** it explains, in plain language (no internal terms like "guild record" or "OAuth sync"), that the user needs to invite the bot to their Discord server and hold the "Manage Server" permission there

### Requirement: Scoped bot-invite link
The system SHALL construct the bot-invite link so that authorizing it only grants the bot the Discord permissions it actually uses (posting messages and embeds, creating and posting in threads, mentioning roles) and does not request any elevated, administrative, or unrelated permission, and does not request the `applications.commands` scope while the bot registers no slash commands.

#### Scenario: Invite link permission scope
- **WHEN** a user opens the bot-invite link shown in onboarding
- **THEN** Discord's authorization screen shows only the specific channel/message/thread permissions the bot needs, with no "Administrator" or "Manage Server" style permission requested

### Requirement: Re-check guild access without full sign-out
The system SHALL let a user who just invited the bot re-check their administered guilds without having to sign out and back in.

#### Scenario: Re-check after inviting the bot
- **WHEN** a user who was shown zero-guild onboarding invites the bot to their server and then uses the "check again" action
- **THEN** the system re-evaluates the user's administered guilds and shows the updated list (or continues showing onboarding if the guild still isn't registered or the user still lacks "Manage Server" there)

### Requirement: Per-guild navigation
The system SHALL, for a user viewing any page scoped to a specific guild, provide navigation to that guild's other pages (settings, collection themes, event role sets, events, giveaways, standard giveaways) without requiring the user to know or type a URL.

#### Scenario: Navigating between a guild's pages
- **WHEN** an authenticated guild admin is viewing any page scoped to a guild they administer
- **THEN** the system shows links to that same guild's other pages

#### Scenario: Navigation respects guild scoping
- **WHEN** an authenticated guild admin is viewing pages scoped to Guild A
- **THEN** the navigation shown links only to Guild A's pages, never to another guild's pages
