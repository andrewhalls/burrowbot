## Context

See proposal.md - Why. Relevant existing pieces this design builds on or replaces:

- `resources/views/components/layout.blade.php` is the shared page shell, auto-applied to every full-page Livewire component via `config('livewire.component_layout') = 'components.layout'` (set in the `add-dashboard-home` change to fix a pre-existing bug where this config was missing entirely). It currently has no `<header>`.
- `resources/views/components/guild-nav.blade.php` is a Blade component currently included manually as `<x-guild-nav :guild="$guild" active="...">` at the top of each of 6 guild-scoped page views' own content div - this is exactly what's being replaced.
- `User::guildAdmins(): HasMany<GuildAdmin>` (`app/Models/User.php`) already gives the authenticated user's administered guilds with `->with('guild')` - the same query `DashboardHome` and the current `guild-nav` component build on.
- Every guild-scoped route follows the pattern `/guilds/{guild}/...`, and route-model binding resolves `{guild}` to a `Guild` instance before the view renders.
- Tailwind 4 is already installed (`@tailwindcss/vite`), configured via CSS (`@import "tailwindcss"` + `@theme`/`@custom-variant`), not a `tailwind.config.js` file.

## Goals / Non-Goals

**Goals:**
- A persistent shell - left sidebar for per-guild navigation, top bar for context + theme toggle + guild switcher - identical position on every page, replacing the per-page-included nav.
- **The shell is universal and automatic going forward**: every page, existing or added in any future change, inherits it with zero per-page opt-in - not a pattern each new page must remember to apply (see Decision 1).
- One consistent visual design language (rounded cards, soft elevation, generous spacing) shared by both themes, per the reference screenshots the user supplied: a light dashboard for shape/layout/spacing, a dark dashboard for the dark palette only - light's shapes win where the two would otherwise conflict.
- Dark/light theme with zero flash-of-wrong-theme on first paint, given dark is always the default.
- Guild switcher that works across every current and future guild-scoped route without a hand-maintained route-to-route mapping.

**Non-Goals:**
- No system-preference-based theme detection (proposal.md - Non-goals).
- No new database table/column for theme preference (see Decision 2).
- No change to any page's own business logic - this is shell/chrome only.
- Not a pixel-exact clone of either reference screenshot (neither is this product) - they set the shape/density/color language, not literal content to copy.

## Decisions

### Decision 1: The layout resolves "current guild" from the route, not from a prop every page must pass - so the shell applies automatically, forever
`layout.blade.php` is auto-applied by Livewire's page-layout mechanism (design.md of `add-dashboard-home`, Decision unchanged) - it is never invoked explicitly by each page's own template, so there's no natural place for a page to pass it a `guild` prop without reintroducing per-page wiring (defeating the point of this change). Instead, the layout resolves the current guild itself:

```php
$currentGuild = request()->route('guild');
```

Laravel's implicit route-model binding has already resolved `{guild}` to a `Guild` instance by the time the layout renders (it renders as part of the same request, after routing/binding). For `/dashboard` (no `{guild}` segment), this is `null`, and the shell renders without per-guild nav links or an "active" page - just branding in the sidebar, and the theme toggle plus guild switcher in the top bar (both still work with zero current guild, since neither needs to know the current page to populate its own guild list, only to know it when computing where a switcher click should navigate).

This is what makes the shell **universal and automatic**, per the Goals above: because nothing about it depends on a specific page passing in props, opting in, or extending a special base view, *every* full-page Livewire component - the 9 that exist today, the giveaway list view planned for the next change, and anything built after that - gets the identical sidebar, top bar, and theme with no extra work. A future page only has to follow the app's existing "full-page Livewire component" convention (already documented in AGENTS.md) to inherit it; there is no separate "add the shell" step to remember or skip.

**Alternative considered**: keep a `guild-nav`-style component each page passes `$guild` into explicitly, but have the layout `@include` a nav partial that's blank when no such prop is set. Rejected - the exact bug this change fixes is nav rendered as page content instead of layout chrome, so the fix needs to live in the layout, not in each page; it would also mean every future page must remember to pass the prop, reintroducing the “growing forward” risk the user flagged.

### Decision 2: Theme persistence is client-side (localStorage), not a cookie or DB column
Dark is unconditionally the default per proposal.md, so the server-rendered HTML never needs to know the user's theme preference to pick the *initial* paint - it always renders as dark's un-suffixed base styling. A tiny, synchronous (non-`defer`/`async`) inline `<script>` in `<head>`, before any CSS paints, checks `localStorage.theme === 'light'` and if so adds `data-theme="light"` to `<html>` before first paint - so there is no flash-of-wrong-theme even though the preference lives entirely client-side. The toggle button just flips `localStorage.theme` and the `data-theme` attribute together.

Tailwind's `dark:`-variant convention is inverted here: since dark is the *unstyled default*, this design defines a `light:`-style custom variant (`@custom-variant light (&:where([data-theme="light"], [data-theme="light"] *));` in the CSS entrypoint) for the comparatively rare light-mode override styles, rather than gating dark-mode styles behind a variant.

**Alternative considered**: a `theme` column on `users` (server-authoritative, works across devices). Rejected - it's a personal browser/device display preference, not account data; a DB round-trip for every page load to read it is unwarranted complexity for this; and it would need a migration + a way to write it (an internal endpoint or Livewire action) where a same-page toggle click is simpler and instant.
**Alternative considered**: a cookie instead of localStorage (server could read it and set `data-theme` server-side, avoiding any client script). Rejected only for simplicity - localStorage needs no Laravel-side reading/writing code at all (no new route, no cookie config), and the blocking inline script achieves the same no-flash result with less surface area.

### Decision 3: Guild switcher navigation target is computed from the current route's declared parameter names, not a hand-maintained mapping
Every guild-scoped route's Laravel `Route` object exposes `parameterNames()` - the URI's declared parameter names in order, independent of whether they're currently resolved. The switcher's target URL for a newly selected guild is computed as:

```php
$route = request()->route();
$isSingleGuildParamRoute = str_starts_with((string) $route?->getName(), 'guilds.')
    && $route->parameterNames() === ['guild'];

$targetUrl = $isSingleGuildParamRoute
    ? route($route->getName(), ['guild' => $newGuild])
    : route('guilds.settings', ['guild' => $newGuild]); // fallback entry point
```

A route like `guilds.events.index` (`/guilds/{guild}/events`) has `parameterNames() === ['guild']`, so switching guild keeps the same route name with the new guild's ID - satisfying "same page type, new guild" (proposal.md). A route like `guilds.event-occurrences.show` (`/guilds/{guild}/event-occurrences/{occurrence}`) has `parameterNames() === ['guild', 'occurrence']` - it needs an occurrence ID that doesn't exist for a different guild, so it falls back to `guilds.settings` for the new guild, per the confirmed fallback behavior. This rule needs zero maintenance as new guild-scoped routes are added - a route only needs `{guild}` as its sole parameter to automatically get "switch and land on the same page" behavior for free.

**Alternative considered**: a hand-written table mapping each route name to its cross-guild equivalent (or `null` for fallback). Rejected - it would need updating every time a guild-scoped route is added or renamed (the proposal explicitly flags `guilds.giveaways.create` as likely to be renamed in a concurrent change), where the `parameterNames()` rule needs no updates ever.

### Decision 4: `guild-nav.blade.php` is removed; its markup splits into a sidebar partial and a top-bar partial, both included by the layout once
Delete the per-page `<x-guild-nav>` inclusion from the 6 views added in `add-dashboard-home`. Its link list moves into a new `resources/views/components/dashboard-sidebar.blade.php` (icon + label per link, the "active" page highlighted, per Decision 5's shape language); a separate new `resources/views/components/dashboard-topbar.blade.php` holds the current-page context, the theme toggle, and the guild switcher (top-right, per the user's explicit placement request). `layout.blade.php` includes both exactly once, passing each the resolved `$currentGuild` (Decision 1) and, for the switcher, the authenticated user's administered-guilds list (Decision 3) - no page needs to pass either in.

### Decision 5: Visual design language - light reference's shapes/spacing, dark reference's palette, one shared system
Per the two screenshots the user provided (a light dashboard and a dark dashboard) and their explicit instruction that light's styles and shapes take priority when combining them:

- **Shape**: generously rounded corners on cards, buttons, inputs, and badges (large radius, not sharp/boxy) - taken from the light reference, and used identically in both themes. The dark reference's tighter/boxier card corners are *not* carried over; only its color palette is.
- **Elevation**: light mode uses soft drop shadows on card surfaces (as in the light reference) to separate them from the page background. Dark mode achieves the same separation via a card surface color slightly lighter than the page background (as in the dark reference), since shadows read poorly on dark backgrounds - not via heavier borders.
- **Density**: generous padding/whitespace, large bold numerals for key stats, pill-shaped status/filter badges - from the light reference's density, kept in both themes rather than the dark reference's slightly denser layout.
- **Color**: light mode uses a light neutral background with white/near-white card surfaces and the light reference's accent-color variety for stat highlight cards. Dark mode uses the dark reference's near-black page background, a dark-but-lighter-than-background card surface, and a single accent color (blue, matching the dark reference) for primary actions, active nav state, and highlights - not the light reference's multi-color accent card treatment, which doesn't read well against a near-black background.
- **Navigation chrome**: an icon-driven sidebar (both references use this pattern) for the per-guild page links, plus a top bar carrying page/guild context on the left and the theme toggle + guild switcher on the right (matching the user's explicit "toggle top right," "guild dropdown top right" placement).

These are implemented as CSS custom properties (surface/background/border/accent colors, a shared radius scale) in the Tailwind 4 CSS entrypoint, switched by the `data-theme` attribute from Decision 2 - not duplicated per-component Tailwind class lists, so every card/button/badge stays visually consistent by construction rather than by convention.

## Risks / Trade-offs

- **[Risk]** The "automatic for every future page" guarantee (Decision 1) only holds as long as new pages are built as full-page Livewire components using the shared `components.layout` default - a page that overrides its layout (e.g. a `#[Layout(...)]` attribute pointing elsewhere) or is built as a plain Blade route instead would silently bypass the shell. → **Mitigation**: document the convention explicitly in `AGENTS.md` (tasks.md 2.7) so this is a known, written-down constraint for any future change, not just an emergent property of today's code.
- **[Risk]** A route whose sole parameter happens to be named `guild` but isn't actually guild-scoped in the `dashboard-home` sense (none exist today) would incorrectly qualify for "same page, new guild" switching. → **Mitigation**: the `guilds.` route-name prefix check in Decision 3 excludes anything outside the established naming convention every guild-scoped route already follows.
- **[Risk]** localStorage-based theme (Decision 2) means the preference doesn't follow a user across devices/browsers, unlike a DB column. → **Mitigation**: accepted trade-off per proposal.md - this is a personal display preference, not account data; explicitly decided against DB storage.
- **[Risk]** The inline no-flash script (Decision 2) runs on every page load before Livewire/Alpine initialize - it must stay dependency-free vanilla JS, not use Alpine's `x-data`, or it will run too late and flash. → **Mitigation**: keep it a small, self-contained `<script>` with no framework dependency, placed before the CSS `<link>`/`@vite` tag in `<head>`.

## Migration Plan

No data migration. Deploy is a normal code deploy (`deploy.sh`) - no new env vars, no schema changes, no bot-process changes. Safe to roll forward/back like any other Blade/CSS change.
