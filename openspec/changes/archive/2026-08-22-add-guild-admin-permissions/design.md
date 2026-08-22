## Context

See `proposal.md` for motivation and scope. Today's authorization model, in full, is:

- `SyncGuildAdminsForUserAction` runs on every login, reconciling `guild_admins` against `DiscordUserGuildsClient::administeredGuildIds()` - which, despite its name, already returns **every** Discord guild the user belongs to, each mapped to a boolean (`GuildPermissions::grantsGuildAdmin()` - the `ADMINISTRATOR`/`MANAGE_GUILD` bit). The sync currently only ever looks at the `true` entries (`array_filter`), throwing away the fact that it also knows about every guild the user is merely a *member* of.
- `User::isAdminOfGuild()` is a bare existence check on `guild_admins`, and every Policy (`Guild`, `CollectionTheme`, `EventRoleSet`, `Event`, `Giveaway`, `StandardGiveaway`, `Broadcast`) calls it identically for both `view` and `manage` - admin is binary and guild-wide, nothing finer exists.
- `guild_admins.role` is a string column, always hardcoded to `'admin'`, never read anywhere - a vestige of an earlier, never-finished design intent visible in the original proposal's schema note (`"guild_admins" (or role mapping)`).
- The dashboard sidebar (`dashboard-sidebar.blade.php`) renders a static, hardcoded 7-entry `$links` array unconditionally to any admin.

This change adds a second, Burrow-managed admin tier without disturbing any of the existing Discord-derived behavior for full admins - a `discord_sync` admin today behaves identically after this change.

## Goals / Non-Goals

**Goals:**
- Let a full admin grant another Discord member access to a specific subset of the seven dashboard sections, from inside the dashboard, without touching Discord's own role/permission system.
- Keep the existing Discord-derived full-admin behavior byte-for-byte unchanged - this is additive, not a replacement.
- Make the two tiers cleanly distinguishable in the data model and in every enforcement point (Policies, sidebar), so "what can this admin actually do" is answerable by reading one row.
- Ensure a scoped grant survives across logins (it's Burrow's own data, not re-derived from Discord) but is still revoked automatically if the invitee leaves the Discord guild entirely.

**Non-Goals:**
- Per-action (view vs. manage) granularity - see `proposal.md` Non-goals.
- A general-purpose roles/permissions engine, presets, or an audit log - v1 is exactly: seven fixed section keys, direct per-grant checkboxes, two tiers.

## Decisions

### 1. Two tiers on the same table: `source` + `sections`, not a parallel table

**Decision:** Extend `guild_admins` in place rather than introduce a separate `guild_admin_grants` table:
- `source` (string, default `'discord_sync'`): `'discord_sync'` for rows created/maintained by the login sync (today's behavior, unchanged), `'granted'` for rows created via the new Admins screen.
- `sections` (nullable JSON array of section keys): **NULL for every `discord_sync` row** (full access to all seven sections is implicit and never needs to be listed), and a **non-empty array for every `granted` row** (the specific sections that row's admin can access).
- `discord_user_id` (nullable string): set on every `granted` row at grant time (copied from the picked `discord_members` record), used to resolve the row to a real `users` row the first time that Discord user logs in. Left null on `discord_sync` rows - they're always created with a resolved `user_id` already, since the sync only ever runs for a user who has just completed login.
- `user_id` becomes **nullable** (was `NOT NULL`) - a fresh `granted` row for someone who has never logged into Burrow has no `users` row to point at yet.

The seven section keys are exactly the existing sidebar keys (`settings`, `themes`, `event-role-sets`, `events`, `giveaways`, `standard-giveaways`, `broadcasts`) from `dashboard-sidebar.blade.php`'s `$links` array - reused as the single source of truth for "what sections exist," defined as a `GuildAdminSection` enum-like class (`App\Support\GuildAdmins\GuildAdminSection`) so the sidebar, the Admins screen's checkboxes, and every Policy check the same fixed list.

**A single helper decides access for both tiers:**
```php
// GuildAdmin model
public function hasSection(string $section): bool
{
    return $this->source === self::SOURCE_DISCORD_SYNC
        || in_array($section, $this->sections ?? [], true);
}
```
```php
// User model
public function hasGuildAdminSection(int|Guild $guild, string $section): bool
{
    $guildId = $guild instanceof Guild ? $guild->id : $guild;

    return $this->guildAdmins()
        ->where('guild_id', $guildId)
        ->get()
        ->contains(fn (GuildAdmin $admin) => $admin->hasSection($section));
}
```
`isAdminOfGuild()` is untouched and keeps its current meaning ("is this user an admin of this guild in *any* capacity, at *any* tier") - it's still exactly right for `GuildPolicy::view/manage` (entering the guild at all, appearing in the guild switcher, seeing dashboard home) and for the "is this at least a `discord_sync` admin" check the Admins screen itself needs (see Decision 4).

**Alternatives considered:**
- *Separate `guild_admin_grants` table, `guild_admins` stays discord-only* - rejected: every enforcement point would need to check two tables and merge the result, doubling the query surface for no real benefit, since a `discord_sync` row and a `granted` row for the same `(guild, user)` pair can't coexist anyway (see Decision 2) - one table with a tier discriminator is simpler and matches how the schema already half-anticipated this (`role` column) without following through.
- *A generic permissions/roles package (e.g. spatie/laravel-permission)* - rejected as scope creep for a fixed, seven-item, per-guild list; would add a dependency and a much larger surface (roles, permissions, model morphs) to solve a problem that's fully expressible as a JSON array on one existing table.

### 2. A user has at most one `guild_admins` row per guild - `discord_sync` always wins

**Decision:** The unique constraint stays `(guild_id, user_id)` (now also `(guild_id, discord_user_id)` for pre-login rows - see Decision 3), so a user can't simultaneously hold both a `discord_sync` and a `granted` row for the same guild. If someone who was previously granted a scoped set of sections later *also* becomes a real Discord admin (gains `ADMINISTRATOR`/`MANAGE_GUILD` in Discord), the next login's sync upgrades their existing row in place: `source` flips to `discord_sync`, `sections` is cleared to `null`, `discord_user_id` is left as-is. They keep one row, now with full access - the earlier grant is superseded, not stacked.

**Rationale:** Avoids ever having to reason about "which of two rows wins" anywhere - every read site (`hasSection`, the Admins list, the sidebar) only ever looks at one row per `(guild, user)`.

### 3. Inviting someone who hasn't logged into Burrow yet

**Decision:** The Admins screen's "Invite" flow searches the target guild's `discord_members` (the existing, already-synced, per-guild member directory - reused as-is, see `member-directory` spec, no changes to that capability) rather than requiring the invitee to have already logged into Burrow. Selecting a member creates a `granted` `guild_admins` row with `discord_user_id` set from that member's Discord ID and `user_id` left `null`.

`SyncGuildAdminsForUserAction` gains one new step, run before its existing logic, on every login:
```php
GuildAdmin::query()
    ->where('discord_user_id', $user->discord_user_id)
    ->whereNull('user_id')
    ->update(['user_id' => $user->id]);
```
This backfills any pending grant(s) for that Discord user across every guild they were invited to, the moment they first sign in - cheap (indexed lookup, typically zero or one row) and idempotent.

**Rationale for reusing `discord_members` instead of a new lookup:** `discord_members` already exists precisely to let staff find a real member to attribute something to (originally for giveaway entries); the Admins invite picker is the same shape of problem ("find a specific guild member by name") the `member-directory` capability's search requirement already solves. Building a second, Discord-API-backed "search everyone in the server" lookup just for this would duplicate that capability and add a new external-call dependency in the request path, for a problem the existing data mostly already covers (see `proposal.md` Non-goals for the accepted limitation: someone the bot has never observed can't yet be found).

### 4. Only `discord_sync` admins can manage other admins

**Decision:** A new `GuildPolicy::manageAdmins()` ability, checked by the Admins screen's `mount()` and every action on it (invite, edit sections, revoke):
```php
public function manageAdmins(User $user, Guild $guild): bool
{
    return $user->guildAdmins()
        ->where('guild_id', $guild->id)
        ->where('source', GuildAdmin::SOURCE_DISCORD_SYNC)
        ->exists();
}
```
A `granted` admin - even one granted every one of the seven sections individually - cannot reach the Admins screen at all, regardless of section grants, because sections don't include an "admins" entry (see `proposal.md` Non-goals) and this check doesn't consult `sections` in the first place.

**Rationale:** Prevents privilege escalation through the feature itself - a scoped admin inviting/re-scoping other admins would let them work around the very restriction they were given. Anchoring on `discord_sync` rather than introducing an "owner" concept (which doesn't exist in Burrow today - see the research that prompted this change) keeps this consistent with how Burrow already treats every Discord-permission holder as an equal peer.

### 5. Revocation: `discord_sync` rows stay sync-only; `granted` rows are Burrow's own to revoke

**Decision:** `SyncGuildAdminsForUserAction`'s deletion logic changes from "delete every row not in the still-admin set" to two separate, tier-aware rules, using the *full* guild-membership map `DiscordUserGuildsClient` already returns (not just the filtered admin subset):

```php
public function execute(User $user, array $discordGuildAdminFlags): void
{
    // Backfill pending grants for this Discord user (Decision 3) - runs first.
    GuildAdmin::query()
        ->where('discord_user_id', $user->discord_user_id)
        ->whereNull('user_id')
        ->update(['user_id' => $user->id]);

    $memberGuildIds = Guild::query()
        ->whereIn('discord_guild_id', array_keys($discordGuildAdminFlags))
        ->pluck('id', 'discord_guild_id');

    $fullAdminGuildIds = $memberGuildIds->only(
        array_keys(array_filter($discordGuildAdminFlags)),
    )->values();

    foreach ($fullAdminGuildIds as $guildId) {
        GuildAdmin::query()->updateOrCreate(
            ['guild_id' => $guildId, 'user_id' => $user->id],
            ['source' => GuildAdmin::SOURCE_DISCORD_SYNC, 'sections' => null],
        );
    }

    // Revoke discord_sync access the user no longer holds in Discord.
    GuildAdmin::query()
        ->where('user_id', $user->id)
        ->where('source', GuildAdmin::SOURCE_DISCORD_SYNC)
        ->whereNotIn('guild_id', $fullAdminGuildIds)
        ->delete();

    // Revoke granted access only for guilds the user has left entirely
    // (not merely lost the Discord admin bit for).
    GuildAdmin::query()
        ->where('user_id', $user->id)
        ->where('source', GuildAdmin::SOURCE_GRANTED)
        ->whereNotIn('guild_id', $memberGuildIds->values())
        ->delete();
}
```

A `granted` row is otherwise only ever deleted by an explicit Revoke action on the Admins screen (Decision 4's `manageAdmins` gate applies there too).

**Alternatives considered:**
- *Granted access also expires if the user loses the Discord `MANAGE_GUILD`/`ADMINISTRATOR` bit* - rejected: that's precisely the scenario this feature exists to support (someone who was never a Discord admin, by design) - reusing the admin-bit as a revocation trigger for `granted` rows would make the feature unable to do the one thing it's for.
- *No automatic revocation at all for `granted` rows (manual-only, forever)* - rejected: leaving the Discord guild should still end their Burrow access; the guild's own member list is authoritative for "does this person still even have any relationship to this server."

### 6. Enforcement: Policies check a section, the sidebar filters by section, direct URLs 403 the same as today

**Decision:** Every Policy that isn't `GuildPolicy` (`CollectionThemePolicy`, `EventRoleSetPolicy`, `EventPolicy`, `GiveawayPolicy`, `StandardGiveawayPolicy`, `BroadcastPolicy`) changes its `view`/`manage` body from `isAdminOfGuild(...)` to `hasGuildAdminSection($guild, GuildAdminSection::<X>)`, one line each, section mapping: `CollectionTheme` → `themes`, `EventRoleSet` → `event-role-sets`, `Event` → `events`, `Giveaway` → `giveaways`, `StandardGiveaway` → `standard-giveaways`, `Broadcast` → `broadcasts`. `GuildSettings`'s own `mount()`/`save()` (currently `$this->authorize('manage', $guild)` against `GuildPolicy`) switches to a direct `hasGuildAdminSection($guild, 'settings')` check instead, since Settings is itself one of the seven grantable sections, distinct from "is admin of this guild at all."

`GuildPolicy::view/manage` (guild switcher, dashboard home) is **unchanged** - still "any admin of this guild, either tier" - per `proposal.md` Non-goals.

`dashboard-sidebar.blade.php` filters its existing `$links`/`$icons` arrays down to the keys `auth()->user()->hasGuildAdminSection($guild, $key)` returns true for, computed once per render alongside the component's existing `$active` logic - no new props threaded through `layout.blade.php`, since the sidebar already runs inside `@auth` and already has `$guild` available as a prop.

A scoped admin who navigates directly to a URL for a section they don't hold still gets the existing 403 behavior (unchanged Policy-driven guard) - the sidebar filtering is a UX nicety, not the enforcement boundary; the boundary is always the Policy.

## Risks / Trade-offs

- **A `granted` admin who is later independently invited again would just have their existing row's `sections` replaced** (edit, not stack) - matches "one row per (guild, user)" (Decision 2); accepted, and matches how the Admins screen's Edit action is expected to work anyway.
- **No audit trail** (`proposal.md` Non-goals) - if "who granted/revoked what, when" ever matters (e.g. a dispute), this change doesn't provide it; a future change could add a simple `guild_admin_grant_events` log without touching this design.
- **Settings becoming a grantable/deniable section** is a real behavior change worth calling out explicitly: today every admin can always reach guild settings; after this change, a `granted` admin without the `settings` section cannot. This is intentional (the user's own framing - "toggle menu items... based on permissions" - and the "per dashboard section" choice covers all seven sections uniformly, Settings included) but is the one part of this change that narrows access for a hypothetical future scoped admin relative to what a full admin could always do, so it's worth the inviter consciously ticking the box rather than it being implicit.

## Migration Plan

Additive-only schema change to `guild_admins`: `source` (string, default `'discord_sync'` - every existing row is correctly classified by the default, since every row that exists today was created by the login sync), `sections` (nullable JSON, defaults `null` - correct for every existing row), `discord_user_id` (nullable string, backfillable later but not required for existing rows since they already have `user_id`), `user_id` relaxed to nullable, plus the new `unique(guild_id, discord_user_id)` constraint. No data migration/backfill script needed - the column defaults alone make every pre-existing row behave identically to today. Deploy order: migrate, deploy Laravel (Policies + sidebar + `SyncGuildAdminsForUserAction` all change together, since the sidebar and Policies must agree with the sync's classification from the same deploy), no bot-process changes at all - this feature is entirely dashboard/Laravel-side.
