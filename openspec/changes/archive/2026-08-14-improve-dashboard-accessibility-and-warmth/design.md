## Context

Per the user's explicit choice (asked via a scoped question before starting): keep the existing neutral canvas/surface/ink tokens and status colors, swap only the accent from monochrome near-black/near-white to a warm amber (dark mode) / terracotta (light mode) - the smallest-risk option of the three offered, still reads as "cozy" through warmth + the app's existing generous rounding/spacing rather than a full palette rework.

The accent token is used in two distinct roles that pull against each other: (1) as a **fill**, with `--color-accent-ink` as the text/icon color drawn on top of it (buttons, active nav, pills), and (2) as **standalone text/border** color drawn directly on `--color-canvas`/`--color-surface` (links like "+ Add item", selected-tile borders). A single hue can't simultaneously be light enough to contrast against dark ink *and* dark enough to contrast against a light canvas - the original monochrome scheme sidestepped this by inverting which end (near-black vs near-white) is "the accent" per theme. This design keeps that same inversion, just recolored.

## Goals / Non-Goals

**Goals:**
- Every accent/ink pairing, and every standalone accent-as-text/border use, reaches WCAG AA contrast (>=4.5:1 for text, >=3:1 for UI components/borders) against the surface it sits on.
- Fix the accessibility gaps that exist regardless of color: no visible focus state anywhere, no skip link, no responsive collapse for several 2-column layouts.

**Non-Goals:**
- WCAG AAA (7:1) - not requested, and would force a much darker/less vibrant accent than "warm and inviting" calls for.
- A second accent shade split by role (fill-accent vs text-accent) - the existing one-token-per-color-role structure is simple and works once each mode's shade is chosen to satisfy both roles at once (see Decision 1).

## Decisions

### Decision 1: Invert brightness per theme, don't add a second accent token
Dark mode: bright accent (`#e8a548`) + deep-brown ink (`#241505`) - the bright fill pops against the dark canvas/surface (`#101114`/`#1a1b1f`) as standalone text too, and dark ink reads clearly on top of the bright fill.
Light mode: deep accent (`#b3591c`) + warm off-white ink (`#fff8f0`) - the deep fill is dark enough to read as standalone text/border on the white surface, and light ink reads clearly on top of the deep fill.

Approximate relative-luminance contrast ratios (WCAG formula, `(L_lighter + 0.05) / (L_darker + 0.05)`):

| Pairing | Ratio | Threshold |
|---|---|---|
| Dark: accent `#e8a548` vs canvas `#101114` (standalone text/border) | ~6.8:1 | 4.5:1 (text) / 3:1 (UI) |
| Dark: ink `#241505` vs accent `#e8a548` (button/pill fill) | ~6.8:1 | 4.5:1 |
| Light: accent `#b3591c` vs surface `#ffffff` (standalone text/border) | ~5.5:1 | 4.5:1 (text) / 3:1 (UI) |
| Light: ink `#fff8f0` vs accent `#b3591c` (button/pill fill) | ~5.2:1 | 4.5:1 |

All four pass AA with margin. **Alternative considered**: keep one accent shade per theme but let ink fall below 4.5:1 in exchange for a more saturated/vibrant color - rejected because the login page's actual pre-existing bug (accent fill with no `text-accent-ink` at all, ~2.6:1 in dark mode) is exactly the failure mode this exists to prevent; a config that's only accessible when every call site remembers to set ink explicitly is fragile.

### Decision 2: `:focus-visible`, not `:focus`
A blanket `:focus` rule would show the accent ring on every mouse/touch click too (most browsers still fire `:focus` on click for buttons/links), which reads as visual noise for pointer users and doesn't match how this app's own hover states already work (hover-only feedback for pointer interaction). `:focus-visible` is what modern browsers use to distinguish "focused via keyboard/programmatically" from "focused via pointer," so only keyboard/switch users see the ring - the group that actually needs it, per specs/dashboard-style-guide - "Keyboard focus is visible."

### Decision 3: `sm:` as the collapse breakpoint for 2-column layouts, matching the list-detail shell's own precedent
The list-detail shell already collapses list/detail from two panels to one below `lg:` (640-1023px shows one panel at a time). The 2-column *tile grids* and *paired form fields* addressed here are a level below that - they read fine down to phone-width even at tablet/narrow-desktop sizes, so `sm:` (640px) is the right cutoff: below it (actual phone widths) collapse to one column, at and above it stay two-up. Using the same `lg:` cutoff as the shell would collapse these unnecessarily on tablet-width screens where two columns still fit comfortably.

## Risks / Trade-offs

- [No rendered-screenshot verification - this environment has no browser access] → Verified via (a) the contrast math in Decision 1, (b) Pest/Vitest assertions that the right CSS classes/HTML are present, and (c) a full test-suite run confirming no existing assertion broke. The user should do a visual pass in a real browser and report back if anything reads wrong.
- [Warm accent is a visible change on every screen at once] → Scoped deliberately narrow (accent token only, everything else held constant) per the user's own chosen option, so the blast radius is "every `bg-accent`/`text-accent`/`border-accent` utility class picks up the new color automatically" rather than file-by-file edits - low risk of an inconsistent half-migrated look.
