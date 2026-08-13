## Context

See proposal.md - Why. Relevant existing pieces this design builds on:

- `User::guildAdmins(): HasMany<GuildAdmin>` and `User::isAdminOfGuild()` already exist (`app/Models/User.php`) - no new relation needed to list a user's guilds.
- `SyncGuildAdminsForUserAction` already reconciles `guild_admins` rows against Discord's live "administered guilds" response on every OAuth callback (`app/Http/Controllers/Auth/DiscordAuthController::callback`) - this already IS the "re-check" mechanism; it just isn't reachable except via a fresh login.
- Every existing guild-scoped page (`guilds.settings`, `guilds.themes.index`, `guilds.event-role-sets.index`, `guilds.events.index`, `guilds.giveaways.create`/`.show`, `guilds.standard-giveaways.index`) already exists and is already policy-guarded; this change only needs to make them reachable and add a landing page, not touch their own logic.
- `routes/web.php`'s `/dashboard` currently renders a static `dashboard.blade.php` view directly (`Route::view`), not a Livewire component.
- No Discord OAuth access token is persisted anywhere today (only used transiently inside the callback) - see Decision 3.

## Goals / Non-Goals

**Goals:**
- Replace the dashboard stub with a real guild list + zero-guild onboarding, per proposal.md.
- Add minimal cross-linking between an already-administered guild's pages.
- Reuse existing auth/sync machinery instead of adding new endpoints or storage.

**Non-Goals:**
- No new migrations/tables (see proposal.md - Impact).
- No persistent storage of Discord OAuth access/refresh tokens.
- No shared-layout rewrite of the 6 existing guild-scoped Livewire pages - just a small included nav component, per Decision 4.

## Decisions

### Decision 1: Dashboard home becomes a Livewire component
Replace `Route::view('/dashboard', 'dashboard')` with a `App\Livewire\Dashboard\DashboardHome` component, matching every other top-level page in the app (`EventIndex`, `StandardGiveawayIndex`, etc.) instead of being the one remaining plain Blade route. It queries `auth()->user()->guildAdmins()->with('guild')->get()` and renders either the guild list or the onboarding state depending on whether that collection is empty.

**Alternative considered**: keep it a plain Blade view backed by a controller. Rejected only for consistency - there's no interactivity requirement that strictly needs Livewire here, but every other page in the app follows this shape and a future "leave guild" or live-refresh action would need it anyway.

### Decision 2: Bot-invite URL is a plain, computed OAuth2 URL - no new service class needed beyond a tiny helper
Built as:
```
https://discord.com/oauth2/authorize?client_id={DISCORD_CLIENT_ID}&scope=bot&permissions={permissions}
```
`permissions` is a fixed integer covering exactly what `bot/src/discordAdapter.js` uses today and nothing else:

| Permission | Bit | Value |
|---|---|---|
| View Channel | 1<<10 | 1,024 |
| Send Messages | 1<<11 | 2,048 |
| Embed Links | 1<<14 | 16,384 |
| Read Message History | 1<<16 | 65,536 |
| Mention @everyone/roles (so eligibility role-mentions in standard giveaway posts actually notify) | 1<<17 | 131,072 |
| Create Public Threads | 1<<34 | 17,179,869,184 |
| Send Messages in Threads | 1<<38 | 274,877,906,944 |
| **Total** | | **292,057,992,192** |

Fits well within PHP's native 64-bit int range, so it's a plain `int` constant, no bcmath/GMP needed. Scope is `bot` only - no `applications.commands`, since the bot registers no slash commands (per proposal.md - Non-goals). If a future change adds slash commands, this constant and scope need revisiting; that's out of scope here.

Implemented as a small pure function (e.g. `App\Support\Discord\BotInviteUrl::build(): string`), not a class hierarchy - it's one string template with one constant, doesn't warrant more structure.

**Alternative considered**: let staff paste in a custom invite URL from Discord's own invite-link generator per-deployment. Rejected - the permission set is fixed by what the bot code actually calls, so hardcoding it keeps the invite link always in sync with the bot's real needs and removes a manual setup step for a non-technical admin.

### Decision 3: "Check again" reuses the existing OAuth redirect route - no new endpoint
The onboarding view's "I've invited the bot - check again" action is a plain link to `route('auth.discord.redirect')` - the exact route already used for initial login. Discord re-issues the OAuth grant (typically without re-prompting consent, since scopes are unchanged) and lands back on the existing `callback()` action, which already unconditionally re-runs `SyncGuildAdminsForUserAction` and redirects to `route('dashboard')`. No new controller action, no stored token, no polling.

**Alternative considered**: persist the Discord access token and add an AJAX "refresh" button that re-calls the Discord guilds API without a redirect. Rejected as unnecessary complexity and a wider security surface (long-lived token storage) for a one-time onboarding step a user hits rarely.

### Decision 4: Per-guild navigation is one included Blade component, not a shared layout
Add `resources/views/components/guild-nav.blade.php` (`<x-guild-nav :guild="$guild" active="events" />`) rendering links to the guild's settings/themes/event-role-sets/events/giveaways/standard-giveaways routes, with the `active` page visually marked. Each of the 6 existing guild-scoped Livewire views includes it near the top of their own template.

**Alternative considered**: introduce a shared `x-guild-layout` wrapping all guild-scoped pages (like the top-level `x-layout`). Rejected for this change - it would touch all 6 existing views' outer structure for a purely cosmetic win over "include one component," and several of those views are mid-page Livewire fragments rather than full-page shells today. Revisit if a future change needs guild-scoped pages to share more than nav (breadcrumbs, guild-switcher, etc.).

## Risks / Trade-offs

- **[Risk]** The invite-link permission set can drift from what the bot process actually calls if a future change adds a new Discord action (e.g. reactions, slash commands) without updating `BotInviteUrl`. → **Mitigation**: the design doc table above is the single source of truth; any change to `bot/src/discordAdapter.js`'s Discord API usage should include a quick check of whether `BotInviteUrl`'s permission constant still covers it. Not automated in this change - out of scope.
- **[Risk]** "Check again" relies on Discord re-issuing the grant near-instantly; if Discord always re-prompts full consent (behavior can vary), the "check again" click is a few seconds slower than an instant refresh would be. → **Mitigation**: acceptable - this is a rare, one-time onboarding action, not a hot path.

## Migration Plan

No data migration. Deploy is a normal code deploy (`deploy.sh`) - no new env vars required (`DISCORD_CLIENT_ID` already exists), no schema changes, no bot-process changes. Safe to roll forward/back like any other Livewire/routing change.
