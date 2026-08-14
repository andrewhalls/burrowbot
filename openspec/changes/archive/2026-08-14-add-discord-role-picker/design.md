## Context

See proposal.md - Why. Relevant existing pieces this design builds on:

- `add-discord-channel-picker` (already archived) established the exact sync pattern this reuses wholesale for roles: a full-list idempotent sync endpoint (`PUT /internal/guilds/{guild}/channels`), bot-side filtering before Laravel ever sees the data, guild join + real-time gateway events + a periodic fallback sweep, and a `discord_channels` table. Discord's gateway `GUILDS` intent (already the only intent this bot requests) already delivers `GUILD_ROLE_CREATE`/`GUILD_ROLE_UPDATE`/`GUILD_ROLE_DELETE` alongside the channel events it already handles - no new intent needed, exactly as was true for channels.
- `CreateStandardGiveaway.requiredRoleIdsInput` is a free-text `string`, split on commas/whitespace at save time into a `list<string>` of Discord role IDs, passed to `CreateStandardGiveawayAction`.
- `EventRole` (`event_roles` table) has a free-text `name`, `sort_order`, `capacity_mode` (`uncapped`/`capped`/`waitlisted`), and `capacity`. `CreateEventRoleSet` builds a whole new role set from an array of role rows (`{name, capacity_mode, capacity}`) in one submission; `ManageEventRoleSetRoles` adds one role at a time to an *existing* set via `newRoleName`/`newRoleCapacityMode`/`newRoleCapacity`, both defaulting new roles to uncapped.
- `CreateStandardGiveaway`'s prize-item picker (`prizeItemSearch` + `getSearchResultsProperty()` + `addPrizeItem()`/`removePrizeItem()` + a chip list) is this codebase's established pattern for "search and individually add items to a running selection," implemented as a plain Livewire round-trip (`wire:model.live.debounce`), not client-side JS - unlike the channel picker, which needed vanilla JS specifically because it only ever carries a *single* value back to a parent's existing `wire:model` binding (design.md Decision 3 there). A multi-select-with-presets is a different shape (a growing list of selections, not one value), so it follows the prize-item precedent instead: a Livewire-native searchable picker, not a new vanilla-JS component.

## Goals / Non-Goals

**Goals:**
- One synced source of truth for a guild's Discord roles, mirroring `discord_channels` exactly.
- One reusable role-selection pattern (search + presets + chip list) shared by Standard Giveaway's required-roles field and Event Role Set's role definitions.
- Self-healing sync, same guarantee as channel sync.

**Non-Goals:**
- No role management from Burrow (proposal.md).
- No backfill of `discord_role_id` onto pre-existing free-text `EventRole` rows (proposal.md).
- No new generic vanilla-JS "role-picker" component - reuses the existing Livewire search-and-chip-list pattern instead (see Decision 1).

## Decisions

### Decision 1: The role picker is a Livewire-native searchable multi-select, not a vanilla-JS component like the channel picker
Each of the three role-selecting components (`CreateStandardGiveaway`, `CreateEventRoleSet`, `ManageEventRoleSetRoles`) gets its own `roleSearch` string property, a `getRoleSearchResultsProperty()` computed property (`DiscordRole::query()->where('guild_id', ...)->whereLike('name', "%{$this->roleSearch}%")->limit(10)->get()`), and a shared Blade partial rendering the search input + results dropdown + a "presets" section listing the guild's `EventRoleSet`s above the individual role results. Each component implements its own `addDiscordRole(string $discordRoleId, string $discordRoleName)`/`addRoleSetPreset(int $roleSetId)` pair, because what "adding a role" *means* differs per call site:
- `CreateStandardGiveaway`: appends the ID to a flat `list<string> $selectedRoleIds` (mirrors `selectedPrizeItemIds` exactly).
- `CreateEventRoleSet`/`ManageEventRoleSetRoles`: appends/creates an `EventRole`-shaped row (`{discord_role_id, name, capacity_mode: 'uncapped', capacity: null}`), reusing the existing per-row capacity controls unchanged.

The *search and presets* half is shared (extracted into a small `App\Livewire\Concerns\SearchesDiscordRoles` trait providing `roleSearch`/`getRoleSearchResultsProperty()`/`getPresetRoleSetsProperty()`, plus a shared Blade partial `resources/views/partials/discord-role-search-results.blade.php` for the dropdown markup); the *selection-mutation* half stays per-component, same as how `addPrizeItem()` is CreateStandardGiveaway's own method today, not a shared abstraction.

**Alternative considered**: a single generic `<x-discord-role-picker :guild="$guild" model="selectedRoleIds" />` Blade component like the channel picker, carrying selections through a hidden JSON-encoded input. Rejected - a hidden-input-plus-vanilla-JS approach works cleanly for the channel picker's *single* value, but forcing a growing multi-select list (plus the EventRoleSet-specific "expand into multiple EventRole rows with capacity defaults" behavior) through the same mechanism would mean parsing/serializing JSON in vanilla JS and re-deriving Livewire component state from it - meaningfully more complex than just letting Livewire's own round-trip handle it, for no real benefit (role lists per guild are small, same "no server-round-trip-per-keystroke pressure" reasoning the channel picker used, but here a full component round-trip per click, not per keystroke, is already the established, working pattern via the prize-item picker).

### Decision 2: Bot-side sync mirrors channel sync exactly - one idempotent full-list endpoint, filtered before Laravel sees it
`PUT /internal/guilds/{guild}/roles`, body `{ roles: [{ discord_role_id, name }, ...] }`, replacing the guild's entire synced role set (upsert present, delete absent) - identical shape and identical trigger set (`GuildCreate`, any `GuildRoleCreate`/`GuildRoleUpdate`/`GuildRoleDelete` event, and a periodic per-guild sweep on the same interval as channel resync) to `SyncGuildChannelsAction`/`syncGuildChannels()` in `bot/src/index.js`. A new pure `bot/src/discordRoles.js` (`postableRoles(guildRolesCache)`, mirroring `discordChannels.js`'s `postableChannels()`) filters to roles where `role.id !== guild.id` (excludes `@everyone`, whose ID always equals the guild's own ID) and `role.managed === false` (excludes bot-integration roles and the auto-created Server Booster role) before the bot ever calls Laravel.

**Alternative considered**: sync every role including `@everyone`/managed ones and filter in Laravel or in the picker's query. Rejected for the same reason channel sync rejected it for channel types: the bot already has to read `role.managed` to build the payload, so filtering there means Laravel's schema/queries never need to represent an irrelevant role type at all.

### Decision 3: A role set preset "adds" its roles, it doesn't link/reference the source set
Selecting an Event Role Set preset in the picker copies that set's *current* roles' Discord role IDs (and names) into the selection being built - a one-time bulk-add, not a live reference. Editing the *source* role set later never retroactively changes a role set that was bulk-populated from it earlier. This matches how every other "reuse" concept in this codebase already works (`collection-themes` - "Collection theme reuse," `standard-giveaway-occurrences`'s snapshot-at-generation-time pattern) - reuse means "copy now," never "link forever."

**Alternative considered**: a live foreign-key link from one role set to a "based on" source. Rejected - unrequested complexity (proposal.md's scope is "let someone pre-make them," i.e. a starting point to copy from and adjust, not an ongoing dependency between role sets), and every existing "reuse" precedent in this codebase is copy-based, not link-based.

## Risks / Trade-offs

- **[Risk]** A synced role deleted on Discord after being referenced by an existing `EventRole` leaves that `EventRole` with a `discord_role_id` that no longer resolves to any row in `discord_roles`. → **Mitigation**: `EventRole.name` is stored on the row itself (not looked up live from `discord_roles` at display time), so a since-deleted role still displays correctly using its last-known name; it simply can no longer be used as a preset-selection target going forward. Same non-issue class as `standard_giveaway_occurrences` already snapshotting fields that could later drift from their source.
- **[Risk]** Bulk-adding a large Event Role Set preset onto a role set already close to some future per-guild role-count limit could produce an oversized set in one click. → **Mitigation**: accepted - no such limit exists in this codebase today, and the existing manual one-row-at-a-time flow has the identical risk at a slower pace; not a new problem this change introduces.

## Migration Plan

Two additive migrations: `discord_roles` table (`guild_id`, `discord_role_id`, `name`, unique on `(guild_id, discord_role_id)`, cascade-delete with guild - identical shape to `discord_channels`), and a nullable `discord_role_id` column on `event_roles`. No backfill (proposal.md) - existing `EventRole` rows keep their free-text `name` and a null `discord_role_id`, functioning exactly as they do today; only newly added roles go through the picker and get a real synced reference.
