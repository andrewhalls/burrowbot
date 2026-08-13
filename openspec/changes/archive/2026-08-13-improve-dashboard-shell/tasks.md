## 1. Theme system

- [x] 1.1 Add shared design tokens (colors, radius scale, elevation) as CSS custom properties in the Tailwind entrypoint - dark values as the unconditioned base, light values under a `light:` custom variant keyed off `[data-theme="light"]` (design.md Decision 2 & 5)
- [x] 1.2 Add the blocking inline no-flash `<script>` in `layout.blade.php`'s `<head>`, before the `@vite` tag, reading `localStorage.theme` and setting `data-theme="light"` on `<html>` before first paint when applicable (design.md Decision 2)
- [x] 1.3 Theme toggle control in the top bar (vanilla JS, no Alpine/Livewire dependency) that flips `localStorage.theme` and the `data-theme` attribute together (spec: `dashboard-theme` - Theme toggle, Theme choice persistence)
- [x] 1.4 Pest/HTTP test asserting the no-flash script and toggle control are present in a rendered page's HTML (spec: `dashboard-theme` - Default theme)

## 2. Dashboard shell structure

- [x] 2.1 `App\Support\Navigation\GuildSwitchTarget`: pure function implementing design.md Decision 3 - given the current route and a target guild, returns the same route name with the new guild id when the route's sole parameter is `guild`, else falls back to `guilds.settings` for the new guild
- [x] 2.2 Pest unit tests for `GuildSwitchTarget`: single-guild-param route (e.g. `guilds.events.index`) keeps the same route name; multi-param route (e.g. `guilds.event-occurrences.show`) falls back to `guilds.settings`; a non-`guilds.`-prefixed route falls back
- [x] 2.3 `resources/views/components/dashboard-sidebar.blade.php`: icon + label links to settings/themes/event-role-sets/events/giveaways/standard-giveaways, active-page highlighted, following design.md Decision 5's shape language
- [x] 2.4 `resources/views/components/dashboard-topbar.blade.php`: page/guild context on the left, theme toggle and guild switcher dropdown on the right
- [x] 2.5 Update `layout.blade.php`: resolve `$currentGuild` from `request()->route('guild')` (design.md Decision 1), load the authenticated user's administered guilds, include the sidebar and top bar exactly once each. Discovered and fixed during implementation: `request()->route('guild')` is only a resolved `Guild` instance when the current page's own `mount()` type-hints `Guild $guild` itself - pages that bind a more specific child model instead (`OccurrenceRoster`, `GiveawayDashboard`, `OccurrenceDashboard`) left it as the raw route-parameter string, causing a 500 ("Attempt to read property on string") on those three routes. Fixed with an explicit `Guild::find()` fallback when the route value isn't already a `Guild` instance; covered by the task 4.2 regression test across all of them.
- [x] 2.6 Delete `resources/views/components/guild-nav.blade.php` and remove its `<x-guild-nav>` inclusion from the 6 views that added it in `add-dashboard-home` (`guild-settings.blade.php`, `collection-theme-index.blade.php`, `event-role-set-index.blade.php`, `event-index.blade.php`, `create-giveaway.blade.php`, `standard-giveaway-index.blade.php`)
- [x] 2.7 Document the shell's automatic-inheritance guarantee in `AGENTS.md`: any new page built as a full-page Livewire component using the default `components.layout` gets the sidebar/top-bar/theme shell for free; explicitly warn against a future page overriding its layout (`#[Layout(...)]`) or being built as a plain Blade route, since either would silently bypass the shell (design.md Risk)

## 3. Guild switcher

- [x] 3.1 Wire the switcher dropdown's options (authenticated user's administered guilds) and each option's target URL (via `GuildSwitchTarget`) into `dashboard-topbar.blade.php` (spec: `dashboard-home` - Guild switcher)
- [x] 3.2 Pest/HTTP tests: switcher lists only guilds the user administers; selecting a guild from a page whose route has only `{guild}` as a parameter targets the same route name for the new guild; selecting a guild from a page with additional route parameters (e.g. an occurrence roster) targets `guilds.settings` for the new guild

## 4. Visual polish pass

- [x] 4.1 Apply the shared design tokens (rounded corners, elevation, spacing, pill badges) from design.md Decision 5 to `dashboard-home`'s guild list/onboarding view and existing guild-scoped page cards/buttons - visual only, no functional changes to any page. Applied systematically across all 17 view files still using the old literal `neutral-*`/`indigo-*`/status-color classes, replacing them with the new `canvas`/`surface`/`ink`/`muted`/`accent`/`danger`/`success`/`warning` tokens and the `rounded-card`/`rounded-control` radius scale.
- [x] 4.2 Regression test confirming every existing full-page route still returns 200 over real HTTP after the shell/layout changes (mirrors the guard added in `add-dashboard-home` for the Livewire page-layout bug, re-run here since `layout.blade.php` changes again) - this is exactly what caught the Decision 1 gap fixed in 2.5

## 5. Verification

- [x] 5.1 Full Pest suite passes
- [x] 5.2 `openspec validate improve-dashboard-shell --strict` passes
