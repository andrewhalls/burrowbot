## Why

The nav added in `add-dashboard-home` is included inline at the top of each guild-scoped page's own content div, not as persistent site-wide chrome - its vertical position drifts with each page's own padding/scroll, so it doesn't read as a real navigation bar ("the menu goes from middle to top"). There's also no visual theming system (no dark/light mode) and no way to switch which guild you're viewing without going back to the dashboard home guild list. This change fixes all three as one cohesive dashboard shell - and, critically, it is built as the app's one permanent, universal shell going forward: every current page and every page added in any future change inherits it automatically, with no per-page opt-in required (see design.md Decision 1).

## What Changes

- Move navigation out of individual page views and into `resources/views/components/layout.blade.php` as a persistent shell - a left sidebar for per-guild page navigation, and a top bar for context plus the theme toggle and guild switcher - present identically on every page (fixed position, not per-page content). Because `layout.blade.php` is already Laravel/Livewire's single auto-applied layout for every full-page component (fixed in `add-dashboard-home`), this shell is not a per-page pattern to repeat - it is inherited automatically by any page, present or future, that follows the app's existing "full-page Livewire component" convention. No future change needs to remember to "add the shell."
- Adopt a consistent visual design language across the whole dashboard, based on two reference screenshots the user provided: a light dashboard (rounded cards, soft shadows, generous spacing, pill badges - the shape/layout language) and a dark dashboard (near-black background, blue accent - the dark color palette). Light's shapes and layout take priority; only the dark reference's coloring is carried into dark mode, not its shapes.
- Add a dark/light theme system: CSS custom properties driven by a `data-theme` attribute, a toggle control top-right in the top bar, default dark, and the user's choice persisted (client-side) so it survives future visits.
- Add a guild-switcher dropdown top-right in the top bar, next to the theme toggle, listing every guild the authenticated user administers. Selecting a different guild navigates to the same page type for that guild when one exists (e.g. Events → Events), or falls back to that guild's dashboard/settings entry point when the current page has no per-guild equivalent (e.g. a specific occurrence roster).
- General visual polish of existing pages/components consistent with the new theme and design language (spacing, contrast, hover states, card/badge shapes) - no functional changes to any page's own behavior.
- **BREAKING** (internal only, no data/behavior impact): removes `resources/views/components/guild-nav.blade.php` and its inclusion in the 6 page views added by `add-dashboard-home`, replaced by the sidebar/top-bar shell.

## Capabilities

### New Capabilities
- `dashboard-theme`: the dark/light visual theme system - default, toggle, and persistence.

### Modified Capabilities
- `dashboard-home`: the existing "Per-guild navigation" requirement changes from per-page-included navigation to a persistent sidebar/top-bar shell; adds a new "Guild switcher" requirement for the top-right dropdown.

## Impact

- **Affected code**: `resources/views/components/layout.blade.php` (gains the sidebar + top bar), `resources/views/components/guild-nav.blade.php` (removed, split into new sidebar/top-bar partials), the 6 guild-scoped views that currently include `<x-guild-nav>` (drop that inclusion), `resources/views/livewire/dashboard/dashboard-home.blade.php` (visual polish only, no behavior change), Tailwind CSS entrypoint (theme tokens, dark-mode variant strategy).
- **No schema changes** - theme persistence is client-side, not a migration (see design.md).
- **No bot-process changes.**
- **Multi-guild scoping**: the guild-switcher dropdown is populated the same way the existing dashboard-home guild list already is (the authenticated user's own `guild_admins` rows) - no new exposure. Switching guilds only ever navigates to a route already guarded by that page's existing per-guild authorization policy; the dropdown itself grants no access.

## Non-goals

- No change to any guild-scoped page's own functionality or data (Events, Giveaways, Standard Giveaways, Themes, Event Role Sets, Settings) - visual/layout polish only.
- No system-preference-based theme detection - default is always dark regardless of OS/browser preference (confirmed with user), the toggle is the only way to switch.
- No persistent "current guild" server-side session state beyond what's needed to compute the switcher's navigation target - each guild-scoped page remains addressed by `{guild}` in the URL, unchanged.
- No redesign of the bot-invite onboarding flow or its content - only its visual chrome inherits the new theme.
