## 1. Bot-invite URL

- [x] 1.1 `App\Support\Discord\BotInviteUrl`: a pure `build(): string` method returning `https://discord.com/oauth2/authorize?client_id={DISCORD_CLIENT_ID}&scope=bot&permissions={permissions}`, with the permissions integer (`292057992192`, view channel/send messages/embed links/read message history/mention roles/create+send in public threads) as a documented class constant matching design.md's table (spec: `dashboard-home` - Scoped bot-invite link)
- [x] 1.2 Pest unit test: asserts the built URL's `client_id` matches `config('services.discord.client_id')`, `scope=bot` only (no `applications.commands`), and `permissions` equals the exact documented constant

## 2. Dashboard home

- [x] 2.1 `App\Livewire\Dashboard\DashboardHome` Livewire component: `mount()` requires auth (already guarded by the `auth` route group); `render()` loads `auth()->user()->guildAdmins()->with('guild')->get()` (spec: `dashboard-home` - Dashboard lists the user's administered guilds)
- [x] 2.2 View `livewire.dashboard.dashboard-home`: guild-list state (each administered guild as a card/row linking to its settings/themes/event-role-sets/events/giveaways(create)/standard-giveaways routes) OR, when the collection is empty, the onboarding state - plain-language explanation (no internal terms) + `BotInviteUrl::build()` link + a "Check again" link to `route('auth.discord.redirect')` (spec: `dashboard-home` - Zero-guild onboarding, Re-check guild access without full sign-out)
- [x] 2.3 `routes/web.php`: replace `Route::view('/dashboard', 'dashboard')` with `Route::get('/dashboard', DashboardHome::class)->name('dashboard')`. Also moved `auth.discord.redirect`/`auth.discord.callback` out of the `guest` middleware group - they were guest-only, which would have redirected an already-authenticated user away before "check again" (task 2.2) ever reached Discord; not previously caught since design.md's Decision 3 assumed the route was reachable while authenticated without checking its middleware.
- [x] 2.4 Delete the now-unused `resources/views/dashboard.blade.php` stub
- [x] 2.5 Pest/Livewire tests: guild-admin sees their guild(s) listed with links; a guild the user does NOT administer never appears (even when other guilds exist in the system); zero-guild user sees onboarding instead of an empty list; onboarding contains the bot-invite link and a link to `auth.discord.redirect`

## 3. Per-guild navigation

- [x] 3.1 `resources/views/components/guild-nav.blade.php`: a Blade component `<x-guild-nav :guild="$guild" active="..." />` rendering links to `guilds.settings`, `guilds.themes.index`, `guilds.event-role-sets.index`, `guilds.events.index`, `guilds.giveaways.create` (no giveaways index route exists today, so this is the correct link target for "Giveaways"), and `guilds.standard-giveaways.index`, all built from the given `$guild`; the `active` page is visually marked (spec: `dashboard-home` - Per-guild navigation)
- [x] 3.2 Include `<x-guild-nav>` in the 6 top-level guild-scoped views: `guild-settings.blade.php`, `collection-theme-index.blade.php`, `event-role-set-index.blade.php`, `event-index.blade.php`, `create-giveaway.blade.php`, `standard-giveaway-index.blade.php`. While doing this, discovered and fixed a pre-existing bug affecting every full-page Livewire route in the app (not scoped to this change): no `config/livewire.php` existed, so Livewire's default `component_layout` (`'layouts::app'`, a view namespace this app never registers) meant every full-page Livewire route 500'd on real HTTP with "No hint path defined for [layouts]" - invisible to the existing test suite because `Livewire::test()` bypasses route-level layout wrapping entirely. Added `config/livewire.php` with `component_layout` set to `'components.layout'` (the app's actual `<x-layout>` shell). Verified all 9 pre-existing guild-scoped routes plus the new dashboard route now return 200 over real HTTP.
- [x] 3.3 Pest/Livewire test: a guild admin viewing one guild-scoped page (e.g. `guilds.events.index`) sees links to that same guild's other pages, and none of the rendered links reference a different guild's ID (spec: `dashboard-home` - Navigating between a guild's pages, Navigation respects guild scoping)

## 4. Verification

- [x] 4.1 Full Pest suite passes
- [x] 4.2 `openspec validate add-dashboard-home --strict` passes
