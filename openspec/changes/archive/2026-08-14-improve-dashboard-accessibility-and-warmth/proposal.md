## Why

The dashboard's target admins skew toward hobbyist server owners running cozy/community games (the user's own example: Palia) - the existing monochrome near-black/near-white accent reads as neutral-corporate rather than warm and inviting. Separately, an accessibility audit found zero `:focus` styling anywhere in the app (keyboard/switch users have no visible indication of where they are) and no skip-to-content link, and several list/form layouts stayed a flat 2-column grid even on phone-width viewports.

## What Changes

- Replace the monochrome accent (`--color-accent`/`--color-accent-hover`/`--color-accent-ink`) with a warm amber (dark mode) / terracotta (light mode) accent, keeping every other token (canvas, surface, ink, status colors) unchanged. Chosen and verified to keep every accent/ink pairing, and accent-as-standalone-text-on-background, at or above WCAG AA's 4.5:1 contrast ratio.
- Add a global `:focus-visible` outline (using the new accent color) to every natively-focusable element, and a "Skip to content" link at the top of the authenticated layout - neither existed before.
- Fix a real (pre-existing, now more consequential) contrast bug: the login page's "Sign in with Discord" button set `bg-accent` without `text-accent-ink`, so its label used the default ink color instead of a color guaranteed to contrast against the accent fill.
- Collapse every 2-column tile grid (giveaway/standard-giveaway/event/theme/role-set list tiles) and paired form-field grid (channel+role-set, winner-count+duration, start-date+start-time, etc.) to a single column below the `sm` breakpoint, so phone-width viewports don't get cramped two-up layouts.
- `<main>` content padding becomes responsive (`p-4` on mobile, `p-6` from `sm:` up) instead of a flat `p-6`.

## Capabilities

### Modified Capabilities

- `dashboard-style-guide`: adds visible-keyboard-focus and narrow-viewport-collapse as durable conventions every screen must follow, alongside the existing spacing/card/empty-state conventions.

## Impact

- Multi-guild scoping: unaffected - purely visual/structural, no data or authorization changes.
- `resources/css/app.css`: accent token values (both themes), new global `:focus-visible` rule.
- `resources/views/components/layout.blade.php`: skip-to-content link, `id="main-content"`, responsive `<main>` padding.
- `resources/views/auth/login.blade.php`: contrast fix.
- 10 Blade views across events/giveaways/standard-giveaways/collection-themes/event-role-sets: `grid-cols-2` → `grid-cols-1 sm:grid-cols-2`.
- New test: `tests/Feature/Auth/LoginPageTest.php` (guards the login-button contrast fix).

## Non-goals

- No change to status colors (success/warning/danger), canvas/surface/ink neutrals, spacing scale, or card radius - only the accent hue and the accessibility/responsive gaps above.
- No sidebar/topbar structural redesign (e.g. a collapsing mobile hamburger menu) - out of scope for this pass; the existing icon-rail sidebar already has accessible names (`title` + `sr-only` text) and the list-detail shell already collapses to one column on narrow screens.
- No visual regression/screenshot testing - this environment has no browser access, so verification is via Pest/Vitest (structural/text assertions) and a manual color-contrast calculation recorded in design.md, not a rendered screenshot.
