## 1. Role sync schema (Laravel)

- [x] 1.1 Migration + model + factory for `discord_roles` (`guild_id` FK cascade-delete, `discord_role_id`, `name`, unique on `(guild_id, discord_role_id)`) - mirrors `discord_channels` exactly
- [x] 1.2 `Guild::roles(): HasMany<DiscordRole>` relation
- [x] 1.3 `App\Actions\Discord\SyncGuildRolesAction`: given a guild and a list of `{discord_role_id, name}`, upserts each and deletes any synced role not in the given list (design.md Decision 2) - same shape as `SyncGuildChannelsAction`
- [x] 1.4 `PUT /internal/guilds/{guild}/roles` endpoint (Form Request validating the roles array) calling the sync Action
- [x] 1.5 Pest tests: syncing creates roles; re-syncing with a changed name updates it; re-syncing with a role removed from the list deletes it; syncing is guild-scoped; unauthenticated request rejected

## 2. Role sync (bot process)

- [x] 2.1 `laravelClient.syncGuildRoles(discordGuildId, roles)`: `PUT /internal/guilds/{id}/roles`
- [x] 2.2 Pure function `postableRoles(guildRolesCache)` in a new `bot/src/discordRoles.js`, filtering out `@everyone` (`role.id === guild.id`) and `role.managed === true`, mapping to `{discord_role_id, name}` (design.md Decision 2)
- [x] 2.3 Wire role sync into the existing `GuildCreate` handler (alongside channel sync), and add `GuildRoleCreate`/`GuildRoleUpdate`/`GuildRoleDelete` handlers that recompute and resend that guild's full role list
- [x] 2.4 Add role sync to the existing periodic per-guild resync sweep (same interval as channel resync)
- [x] 2.5 Vitest coverage for `postableRoles` (excludes `@everyone` and managed roles, includes everything else) and for the sync call payload shape on each trigger

## 3. Event Role schema change

- [x] 3.1 Migration: nullable `discord_role_id` on `event_roles`
- [x] 3.2 `EventRole` model: add `discord_role_id` to `$fillable`
- [x] 3.3 `EventRoleFactory`: `withDiscordRoleId(string $id)` state

## 4. Shared role-search infrastructure

- [x] 4.1 `App\Livewire\Concerns\SearchesDiscordRoles` trait: `roleSearch` property, `getRoleSearchResultsProperty()` (guild-scoped `DiscordRole` search), `getPresetRoleSetsProperty()` (guild's `EventRoleSet`s with their roles eager-loaded) (design.md Decision 1)
- [x] 4.2 Shared Blade partial rendering the search input + results dropdown (individual roles) + presets section (existing role sets, each showing its member role names) - included by all 3 call sites

## 5. Standard Giveaway's Required Roles

- [x] 5.1 `CreateStandardGiveaway`: remove `requiredRoleIdsInput`; `use SearchesDiscordRoles`; add `selectedRoleIds` (`list<string>`) + `addDiscordRole(string $id)`/`removeDiscordRole(string $id)`/`addRoleSetPreset(int $roleSetId)` (bulk-adds that set's roles' Discord IDs, de-duplicated) - mirrors `selectedPrizeItemIds`/`addPrizeItem()`/`removePrizeItem()` exactly
- [x] 5.2 `create-standard-giveaway.blade.php`: replace the free-text required-roles input with the shared search partial + a chip list of selected roles (same visual pattern as the prize-item chip list)
- [x] 5.3 Pest/Livewire tests: searching and selecting roles adds them to `selectedRoleIds`, scoped to the guild; selecting a preset role set bulk-adds its roles, de-duplicated against already-selected ones; removing a chip removes it; saving persists the same `StandardGiveawayRequiredRole` rows as before (no change to `CreateStandardGiveawayAction`'s contract)

## 6. Event Role Set creation

- [x] 6.1 `CreateEventRoleSet`: `use SearchesDiscordRoles`; replace the per-row free-text "Role name" input with `addDiscordRole(string $id, string $name)` (appends a new `{discord_role_id, name, capacity_mode: 'uncapped', capacity: null}` row to `$roles`) and `addRoleSetPreset(int $roleSetId)` (bulk-appends every role from that preset, each uncapped) - existing per-row capacity controls and remove-row button unchanged
- [x] 6.2 `create-event-role-set.blade.php`: replace the row's free-text name input with the shared search partial's compact form; keep capacity controls and remove-row button as-is
- [x] 6.3 `CreateEventRoleSetAction`: accept `discord_role_id` per role row, store it on the created `EventRole`
- [x] 6.4 Pest/Livewire tests: bulk-selecting a preset adds its roles as new uncapped rows; individually adding a synced role adds one row; saving persists `discord_role_id` on each created `EventRole`

## 7. Event Role Set management (existing sets)

- [x] 7.1 `ManageEventRoleSetRoles`: `use SearchesDiscordRoles`; replace `newRoleName` with `addDiscordRole(string $id, string $name)`/`addRoleSetPreset(int $roleSetId)`, reusing the existing `newRoleCapacityMode`/`newRoleCapacity` fields as the shared capacity applied to every role added in that action (design.md - Non-goals: no per-role capacity during a bulk add)
- [x] 7.2 `manage-event-role-set-roles.blade.php`: replace the free-text "New role" input with the shared search partial
- [x] 7.3 `ManageEventRoleSetRolesAction::addRole()`: accept a `discordRoleId` alongside the name, store it on the created `EventRole`
- [x] 7.4 Pest/Livewire tests: adding a synced role persists `discord_role_id`; selecting a preset bulk-adds multiple roles in one action, all with the form's currently-selected capacity settings; editing-blocked-while-active-occurrence behavior unchanged

## 8. Documentation

- [x] 8.1 Add `PUT /internal/guilds/{guildId}/roles` to `openapi.yaml`, lint clean

## 9. Verification

- [x] 9.1 Full Pest suite passes
- [x] 9.2 Full bot Vitest suite passes
- [x] 9.3 `openspec validate add-discord-role-picker --strict` passes
