## Context

`DiscordMember` stores `username`/`avatar_url`, kept current via `SyncDiscordMemberAction`, reached two ways: directly from `PUT /internal/guilds/{guild}/members/{discordUserId}` (called by the bot's `upsertObservedMember` on every observed interaction), and opportunistically from inside `JoinGiveawayAction`/the event-signup action/the standard-giveaway-entry action, each of which receives a `discordUsername` string from the bot and re-syncs the member as part of the same transaction (belt-and-braces against a member never having triggered the dedicated upsert call). Entries/signups don't denormalize the username themselves - they only store `discord_user_id` and read the member's name through the `member()` relation - so a single `display_name` column update, plus updating the handful of Blade views that render `->username`, covers every display site.

The bot side: all four member-observing call sites live inside `client.on(Events.InteractionCreate, ...)`, where `interaction.member` (a `GuildMember`, with a `.displayName` getter resolving nickname -> global display name -> username) is available alongside `interaction.user`. `memberRoleIds()`/`memberIsBoosting()` already read from `interaction.member` for the standard-giveaway-entry path, so threading a resolved display name through the same object is a small, established pattern, not a new one.

## Goals / Non-Goals

**Goals:**
- Every member-sync path (the dedicated upsert call and the three opportunistic ones) captures and stores a resolved display name alongside the existing username.
- Every dashboard site currently showing `->username` shows `display_name ?? username` instead.

**Non-Goals:**
- No new Discord gateway listener (`GuildMemberUpdate`) - sync stays purely interaction-driven, matching the existing username/avatar behavior.
- No change to `scopeSearch()` - search stays username/Discord-ID only; searching by display name is a possible future enhancement, not required here.

## Decisions

**Decision 1: Resolve display name bot-side via `interaction.member`, not `interaction.user`.**
`interaction.user.globalName` is the account-level display name (same across every server), but a member's *guild nickname* takes precedence over that in Discord's own UI, and only `interaction.member.displayName` (a `GuildMember` getter) accounts for both, falling back through nickname -> globalName -> username automatically. A small helper `resolveDisplayName(member, user)` in `bot/src/index.js` does `member?.displayName ?? user.globalName ?? user.username`, covering the rare case discord.js hands back a partial/raw member payload without a working `.displayName` getter.

**Decision 2: Thread `displayName` through every path `username` already travels, not just the dedicated upsert.**
Since `JoinGiveawayAction`/event-signup/standard-giveaway-entry each independently call `SyncDiscordMemberAction` with their own `discordUsername` argument, adding display name only to the dedicated `upsertMember` endpoint would leave those three paths writing `display_name = null` on every sync after (each call is an `updateOrCreate`, so a later call without the field would null out an earlier value depending on write order). All four bot->Laravel calls, all four request classes/controllers, and `SyncDiscordMemberAction` itself gain the new field together, kept in sync the same way `username` already is across all four.

**Decision 3: Additive column, not a replacement.**
`username` stays the identity/search key (`scopeSearch`, `unique(['guild_id', 'discord_user_id'])` is unaffected since it's keyed on Discord user ID, not username anyway). `display_name` is purely a nicer label for display. Alternative considered - renaming/repurposing `username` to hold the display name and dropping the raw handle - rejected because the raw handle is still what `scopeSearch()` matches against and is unambiguous (a display name is not unique, isn't stable, and can contain characters awkward for search-as-you-type).

## Risks / Trade-offs

- [`SyncDiscordMemberAction::execute()` gains a 5th parameter] → Keep it nullable/optional (`?string $displayName = null`) so a caller that genuinely has no member context (none exist today, but keeps the action robust) still works.
- [Four call sites to update in lockstep (bot + 4 Laravel request/controller pairs) is easy to partially miss] → Task list enumerates each one explicitly; Pest tests cover each endpoint storing `display_name`, not just the dedicated upsert endpoint.

This change does not touch giveaway expiry, random item assignment, or recurrence rules - member identity is a cross-cutting concern read by those features, not part of their logic.
