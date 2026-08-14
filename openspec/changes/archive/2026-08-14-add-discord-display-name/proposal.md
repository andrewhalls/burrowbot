## Why

Every roster/entrant/winner list in the dashboard shows a member's raw Discord `username` (the `@handle`), because that's the only string `DiscordMember` ever stores or the bot ever captures. Discord's own UI shows a member's *display name* instead (their per-guild nickname if set, else their account-level "display name", else the username) - admins expect the dashboard to match what they see in Discord, not the raw handle.

## What Changes

- Add a nullable `display_name` column to `discord_members`, alongside the existing `username`.
- Bot: every interaction handler that currently reads `interaction.user.username` for a member-facing action (join giveaway, event signup/not-attending, standard giveaway entry, and the member sync they trigger) also resolves and sends `interaction.member`'s resolved display name (`member.displayName` - nickname, else global display name, else username), falling back to `user.globalName ?? user.username` for the rare case a full `GuildMember` isn't available.
- `SyncDiscordMemberAction` and the internal API endpoints/requests it's reached through (`PUT /internal/guilds/{guild}/members/{discordUserId}`, the giveaway-entry/event-signup/standard-giveaway-entry endpoints that opportunistically sync a member) all thread the new display name through and store it.
- `username` is kept as-is (still the raw handle, still used for search/uniqueness via `DiscordMember::scopeSearch()`) - `display_name` is additive, not a replacement of the identity key.
- Every dashboard display site currently showing a member's `username` (giveaway entrant table, standard giveaway occurrence dashboard's winners/entrants lists, event occurrence roster) instead shows `display_name` when set, falling back to `username` when not.

## Capabilities

### Modified Capabilities

- `member-directory`: member sync additionally captures and stores a display name (nickname/global-name-aware), distinct from the raw username already stored.

## Impact

- Multi-guild scoping: display name is per-guild-record just like the existing `username`/`avatar_url` fields (a member's nickname is itself per-guild, so this is *more* correct per-guild data than a single global name would be) - no new cross-guild concerns.
- Migration: `add_display_name_to_discord_members_table`.
- `App\Models\DiscordMember`, `App\Actions\Members\SyncDiscordMemberAction`, `App\Http\Requests\UpsertMemberRequest` (+ the join-giveaway/event-signup/standard-giveaway-entry requests that also reach `SyncDiscordMemberAction`).
- `bot/src/index.js` (`upsertObservedMember`, `handleEventSignupInteraction`, `handleStandardGiveawayEntryInteraction`, the join-giveaway handler), `bot/src/laravelClient.js` (`upsertMember`, `joinGiveaway`, `signUpForEventOccurrence`, `submitStandardGiveawayEntry`).
- `resources/views/livewire/giveaways/giveaway-dashboard.blade.php`, `resources/views/livewire/standard-giveaways/occurrence-dashboard.blade.php`, `resources/views/livewire/events/occurrence-roster.blade.php`.
- `openapi.yaml`: request body schemas for the affected internal endpoints gain a `discord_display_name`/`display_name` field.

## Non-goals

- No backfill for existing `DiscordMember` rows created before this change - they simply show `username` (their existing fallback) until next observed on Discord.
- No live-updating of display names outside an observed interaction (no new `GuildMemberUpdate` listener/sweep) - same "opportunistic sync on next interaction" model already used for `username`/`avatar_url`.
- No admin-facing UI to override or hide a member's display name.
