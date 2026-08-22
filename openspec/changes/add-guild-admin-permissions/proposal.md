## Why

Every guild admin today can do everything in that guild - settings, collection themes, event role sets, events, popup giveaways, standard giveaways, and broadcasts - because "admin" is a single, all-or-nothing Discord-derived tier: anyone Discord reports as holding the `ADMINISTRATOR` or `MANAGE_GUILD` permission bit gets synced into `guild_admins` on login, with no finer-grained say from within Burrow itself. A guild owner who wants to hand a trusted member just the popup-giveaway workflow - without also handing them settings, other giveaway types, or events - has no way to do that today; their only lever is Discord's own role/permission system, which is coarser than Burrow's own feature set and not guild-owner-controlled from inside the dashboard at all.

## What Changes

- Add a second admin tier alongside the existing Discord-synced (full-access) one: a **granted, section-scoped admin** - a specific Discord member given access to only a chosen subset of Burrow's seven dashboard sections (Settings, Collection themes, Event role sets, Events, Popup giveaways, Standard giveaways, Broadcasts), by an existing full admin, from inside the dashboard.
- Extend `guild_admins` with `source` (`discord_sync` | `granted`) and `sections` (a JSON list, meaningful only for `granted` rows - a `discord_sync` row always has full access, matching today's behavior unchanged) and a nullable `discord_user_id` so a scoped grant can be created for a Discord member who hasn't logged into Burrow yet.
- Change the login sync (`SyncGuildAdminsForUserAction`) so it only ever creates/revokes `discord_sync` rows from Discord's reported permissions - a `granted` row is never touched by that sync except being revoked if the user leaves the Discord guild entirely, and gains its `user_id` backfilled the first time its invitee actually logs in.
- Add a new **Admins** dashboard screen (full admins only) to invite a guild member (searched from the guild's already-synced `discord_members`), choose which sections to grant them, edit an existing grant's sections, and revoke a grant.
- Make the sidebar and every section-specific Policy (`Event`, `Broadcast`, `StandardGiveaway`, `Giveaway`, `EventRoleSet`, `CollectionTheme`, and the guild `Settings` page) consult the current user's granted sections for that guild, so both navigation and direct URL access respect the grant - a scoped admin sees and can reach only the sections they've been given.

## Capabilities

### New Capabilities
- `guild-admin-permissions`: the two-tier admin model, the Admins management screen (invite/edit/revoke a scoped grant), section-aware enforcement across every guild-scoped Policy, and the sidebar reflecting only a user's granted sections.

### Modified Capabilities
- `auth`: the login sync narrows to only manage `discord_sync` rows; a scoped (`granted`) admin can now authenticate and reach a guild's dashboard without holding Discord's `ADMINISTRATOR`/`MANAGE_GUILD` bit, as long as they remain a member of that Discord guild.

## Non-goals

- No per-action granularity within a section in v1 (e.g. "can view popup giveaways but not create them") - a granted section is full view+manage access to that section, matching how `view`/`manage` are already functionally identical on every existing Policy.
- No ability for a scoped admin to invite, edit, or revoke other admins, regardless of which sections they hold - admin management is restricted to `discord_sync` (full) admins only, to avoid a scoped admin bootstrapping broader access for themselves or others.
- No revoking a `discord_sync` admin from the dashboard - full access is Discord-derived, so it must be removed at the Discord side (role/permission change); the Admins screen only offers Revoke on `granted` rows.
- No named permission presets/templates (e.g. a reusable "Giveaway Moderator" role) - v1 is direct per-grant section checkboxes only.
- No search across all of Discord for someone to invite - the invite picker searches the guild's already-synced `discord_members` (per the existing `member-directory` capability); a person the bot has never observed in that guild can't yet be found/invited. This is an accepted limitation of reusing existing synced data rather than building a new Discord-wide user search.
- No notification (DM, email) to an invitee when they're granted or revoked access.
- No expiring/time-limited grants, and no audit log of who granted/revoked what - both are reasonable future additions if needed.
- The guild switcher and the dashboard home/overview page remain visible to any admin of a guild (either tier) regardless of granted sections - only the seven feature-section links/routes and their underlying Policies are gated.

## Impact

- **Multi-guild scoping**: `guild_admins` rows (both tiers) remain scoped by `guild_id` exactly as today; a scoped grant for Guild A never implies any access to Guild B. The Admins screen and its actions are guild-scoped the same way every other dashboard screen is.
- Schema change: `guild_admins` gains `source`, `sections`, and a nullable `discord_user_id` column; `user_id` becomes nullable (a pending grant for someone who hasn't logged in yet has no `users` row to reference until their first login backfills it) plus a new `unique(guild_id, discord_user_id)` constraint alongside the existing `unique(guild_id, user_id)`.
- Touches every existing guild-scoped Policy (`Guild`, `CollectionTheme`, `EventRoleSet`, `Event`, `Giveaway`, `StandardGiveaway`, `Broadcast`) to check a specific section instead of blanket `isAdminOfGuild()`, and `resources/views/components/dashboard-sidebar.blade.php` to filter its `$links`/`$icons` by the current user's granted sections.
- Reuses without modification: Discord OAuth login itself, `DiscordUserGuildsClient` (its response already includes every guild the user belongs to, admin or not - the sync action just needs to use more of what it already returns), and `member-directory`'s existing per-guild member search for the invite picker.
- New guild-scoped dashboard screen: Admins (list current admins and their access, invite, edit sections, revoke), visible only to full admins.
