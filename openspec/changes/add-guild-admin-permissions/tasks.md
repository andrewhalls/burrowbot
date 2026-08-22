## 1. Schema & model changes

- [ ] 1.1 Migration: add `source` (string, default `discord_sync`), `sections` (nullable JSON), `discord_user_id` (nullable string) to `guild_admins`; relax `user_id` to nullable; add `unique(guild_id, discord_user_id)` alongside the existing `unique(guild_id, user_id)`
- [ ] 1.2 `GuildAdmin` model: `SOURCE_DISCORD_SYNC`/`SOURCE_GRANTED` constants, `sections` array cast, `hasSection(string $section): bool` (design.md Decision 1)
- [ ] 1.3 New `App\Support\GuildAdmins\GuildAdminSection` class: the fixed seven section-key list (`settings`, `themes`, `event-role-sets`, `events`, `giveaways`, `standard-giveaways`, `broadcasts`), reused by the sidebar, the Admins screen, and every Policy (design.md Decision 1)
- [ ] 1.4 `User::hasGuildAdminSection(int|Guild $guild, string $section): bool` (design.md Decision 1); `isAdminOfGuild()` left unchanged
- [ ] 1.5 `GuildAdminFactory` states: `discordSynced()` (default), `granted(array $sections)`, `pending(string $discordUserId, array $sections)` (no `user_id`)
- [ ] 1.6 Pest tests for `GuildAdmin::hasSection()` and `User::hasGuildAdminSection()` covering both tiers and the pending (`user_id` null) case

## 2. Login sync changes

- [ ] 2.1 Rewrite `SyncGuildAdminsForUserAction` per design.md Decision 5: backfill pending grants by `discord_user_id` first; create/update `discord_sync` rows from the full Discord membership map (not just the filtered admin subset); revoke `discord_sync` rows the user no longer qualifies for; revoke `granted` rows only for guilds the user has left entirely (spec: `auth` - Authorization revoked upstream, Scoped admin access ends when guild membership ends)
- [ ] 2.2 Handle the "user regains/gains full admin while holding a `granted` row" upgrade path: `source` flips to `discord_sync`, `sections` cleared to `null`, same row (design.md Decision 2)
- [ ] 2.3 Pest tests: full-admin sync unchanged for existing discord_sync-only users (regression); granted row survives a login where the user isn't a Discord admin but is still a guild member; granted row revoked when the user is no longer in the Discord guild's membership list at all; pending grant (`user_id` null) gets `user_id` backfilled on first login (spec: `auth` - all new/modified scenarios); upgrade path from granted to discord_sync

## 3. Policy enforcement

- [ ] 3.1 `GuildPolicy`: add `manageAdmins(User $user, Guild $guild): bool` checking `source = discord_sync` (design.md Decision 4); `view`/`manage` left unchanged
- [ ] 3.2 Update `CollectionThemePolicy`, `EventRoleSetPolicy`, `EventPolicy`, `GiveawayPolicy`, `StandardGiveawayPolicy`, `BroadcastPolicy`: `view`/`manage` switch from `isAdminOfGuild(...)` to `hasGuildAdminSection($guild, GuildAdminSection::<X>)` (design.md Decision 6, one section mapping per policy)
- [ ] 3.3 `GuildSettings` Livewire component: `mount()`/`save()` switch from `authorize('manage', $guild)` to a direct `hasGuildAdminSection($guild, GuildAdminSection::SETTINGS)` check (design.md Decision 6)
- [ ] 3.4 Pest tests per policy: full admin allowed (regression), scoped admin with the matching section allowed, scoped admin without it denied with 403, cross-guild denial unaffected (spec: `guild-admin-permissions` - Section-gated access to guarded dashboard content, Full admin access is unaffected, all scenarios)

## 4. Admins dashboard screen

- [ ] 4.1 Route `guilds.admins.index` -> `AdminIndex` Livewire component, guarded by `GuildPolicy::manageAdmins`
- [ ] 4.2 `AdminIndex`: lists a guild's admins (both tiers), each scoped admin's granted sections shown, each full admin shown with no Revoke control (spec: `guild-admin-permissions` - Discord-synced admins cannot be revoked from the dashboard)
- [ ] 4.3 Invite flow: search picker over the guild's `discord_members` (reusing `member-directory`'s existing search, no changes to that capability), section checkboxes, `GrantGuildAdminSectionsAction` creating a `granted` row keyed by `discord_user_id` (with `user_id` resolved immediately if that Discord user already has a `users` row) (spec: `guild-admin-permissions` - Granting a section-scoped admin, all scenarios)
- [ ] 4.4 Edit flow: `UpdateGuildAdminSectionsAction` replaces (not appends to) an existing granted row's `sections` (spec: `guild-admin-permissions` - Editing a scoped admin's sections, both scenarios)
- [ ] 4.5 Revoke flow: `RevokeGuildAdminAction`, only offered/effective on `granted` rows (spec: `guild-admin-permissions` - Revoking a scoped admin)
- [ ] 4.6 `AdminPolicy`-equivalent guard on every action (reuses `GuildPolicy::manageAdmins`) rejecting a scoped admin regardless of their own granted sections (spec: `guild-admin-permissions` - Admin management restricted to full admins, both scenarios)
- [ ] 4.7 Pest/Livewire tests: list rendering for both tiers, invite (including zero-sections-selected rejection and the "member not yet synced can't be found" case), edit, revoke, 403 for a scoped admin attempting any Admins-screen action, 403 cross-guild

## 5. Sidebar gating

- [ ] 5.1 `dashboard-sidebar.blade.php`: filter the existing `$links`/`$icons`/`$routeNameToActiveKey` down to `auth()->user()->hasGuildAdminSection($guild, $key)` for the seven section keys; guild switcher and dashboard-home entry point stay unfiltered (design.md Decision 6) (spec: `guild-admin-permissions` - Sidebar reflects granted sections, all scenarios)
- [ ] 5.2 Add an "Admins" sidebar entry, shown only when `Auth::user()->can('manageAdmins', $guild)` is true
- [ ] 5.3 Pest/Livewire or Blade-rendering tests: scoped admin's sidebar shows only granted sections plus no Admins entry; full admin's sidebar shows all seven plus Admins; guild switcher/dashboard home unaffected

## 6. Cross-cutting polish

- [ ] 6.1 Database seeder: a second demo admin user granted only the "Popup giveaways" section on the existing demo guild, for manual QA of the scoped-admin experience
- [ ] 6.2 README section documenting the two admin tiers and how to grant scoped access, alongside the existing auth/OAuth setup section
