## Why

Today `dashboard.blade.php` is a static stub ("Signed in as {name}" and a sign-out button) and the app has no navigation anywhere - every guild-scoped page (settings, collection themes, event role sets, events, giveaways, standard giveaways) only exists at a hand-typed URL. A user who logs in has no way to discover or reach any of their guilds' pages, and a first-time admin who hasn't invited the bot yet sees a blank page with no explanation of what to do next. The `auth` capability's spec already requires "WHEN an authenticated user has no admin authorization for any registered guild, THEN the dashboard shows no guilds" (`openspec/specs/auth/spec.md`), but this was never actually implemented - the dashboard doesn't query guilds at all today, for anyone, admin or not.

## What Changes

- Replace the static dashboard stub with a real home page that lists every guild the logged-in user administers (from existing `guild_admins` rows), each linking into that guild's existing pages.
- Add minimal per-guild navigation (a small nav/menu once inside a guild's context) linking between that guild's settings, collection themes, event role sets, events, giveaways, and standard giveaways pages - closing the "only reachable by hand-typed URL" gap for every existing capability.
- Add a zero-guild onboarding state: plain-language instructions aimed at a non-technical server admin, explaining that the bot must be invited to their Discord server and that they need the "Manage Server" permission there for it to appear, plus a ready-to-click "Invite bot to your server" button using a correctly-scoped Discord OAuth2 bot-invite URL.
- Add a way to re-check guild access after inviting the bot without a full sign-out/sign-in (re-runs the existing Discord OAuth + guild-admin-sync flow; see design.md for exactly how).

## Capabilities

### New Capabilities
- `dashboard-home`: the post-login landing page - lists the user's administered guilds with links into each guild's existing pages, or shows onboarding (bot-invite link + instructions) when the user administers none. Also covers the bot-invite URL construction and the minimal per-guild navigation used to reach existing pages.

### Modified Capabilities
(none - the `auth` capability's existing "No guild access" requirement is unchanged; this change is what finally implements it, and `dashboard-home`'s own spec carries the scenario coverage for the dashboard's observable behavior.)

## Impact

- **Affected code**: `resources/views/dashboard.blade.php` (replaced with a real view), `resources/views/components/layout.blade.php` (gains a minimal nav slot/partial for guild-scoped pages), `routes/web.php` (dashboard route may move to a Livewire component or stay a controller - decided in design.md), a new bot-invite-URL builder (config-driven from `DISCORD_CLIENT_ID` plus a fixed, minimal permissions set matching what the bot process actually uses today).
- **No schema changes**: this reads existing `guild_admins`/`guilds` data; no new migrations.
- **No bot-process changes**: the bot-invite link is a plain Discord URL constructed by Laravel; the bot process itself is untouched.
- **Multi-guild scoping**: the guild list is always derived from the authenticated user's own `guild_admins` rows (same query pattern as every existing guild-scoped policy) - a user only ever sees guilds they administer, never another admin's guilds. The per-guild nav is scoped to the single guild whose pages it links into (URL-driven, same as today), so it introduces no new cross-guild exposure.

## Non-goals

- No new Discord permissions/OAuth scopes beyond what's already requested (`identify`, `guilds` for login; a separate `bot` scope for the invite link only - no `applications.commands`, since no slash commands exist).
- No background job or poller that detects the bot joining a server in real time - guild-admin sync still only runs when the Discord OAuth flow runs (today: login; after this change: also the explicit "check again" action), per the existing `auth` capability. No webhook-driven or scheduled re-sync.
- No redesign of the existing guild-scoped pages themselves (giveaways, events, standard giveaways, settings, themes) - this change only adds a way to reach them and a landing page; their own UIs are out of scope.
- No guild-switcher/multi-tenant "current guild" session state - every guild-scoped page remains addressed by `{guild}` in the URL, as today; the dashboard home is just a list of entry points, not a persistent guild-context selector.
