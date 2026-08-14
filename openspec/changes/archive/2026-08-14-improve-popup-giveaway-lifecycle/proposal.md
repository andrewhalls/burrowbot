## Why

The pop-up giveaway feature's own spec (`giveaway-lifecycle` - "Starting a giveaway") already requires a draft-to-active start action, but it was never implemented: `CreateGiveawayAction` only ever creates a giveaway in `draft`, and `GiveawayDashboard` has no status control at all. Every giveaway is permanently stuck in `draft` - it never posts to Discord and never accepts entries. There is also no way to see a guild's existing giveaways at all (only a creation form and a single-giveaway dashboard reachable by knowing its ID), and the feature's UI never labels itself distinctly from the newer "Standard Giveaway" feature, which is confusing now that both exist side by side.

## What Changes

- Implement the missing "Start" action (`giveaway-lifecycle` - "Starting a giveaway", already spec'd): a guild admin can start a `draft` giveaway immediately, posting it to Discord and transitioning it to `active`.
- Add a new, scheduled variant: a guild admin can instead set a future `scheduled_start_at` when creating a giveaway, and a scheduled process starts it automatically at that time - no admin action required at the scheduled moment. Reuses the same start logic (posting, `ends_at` calculation) as the manual action, just triggered on a timer instead of a click.
- Add a giveaway list view (`guilds.giveaways.index`) showing every giveaway for a guild - status, entrant count, and (for drafts) the Start action - since none exists today. The dashboard shell's "Giveaways" sidebar link (currently pointing at the creation form, since no index existed) now points here instead.
- Relabel the feature "Popup Giveaway" throughout the dashboard UI (page titles, buttons, sidebar link) to distinguish it from "Standard Giveaway." **UI copy only** - the `Giveaway` Eloquent model, `giveaways` table, route names, and the `giveaway-lifecycle`/`giveaway-entry`/`giveaway-admin-dashboard` capability names are unchanged, to avoid unnecessary churn across everything already built on those identifiers.

## Capabilities

### Modified Capabilities
- `giveaway-lifecycle`: adds a new "Scheduled start" requirement alongside the existing (already-specified, now finally implemented) "Starting a giveaway" requirement.
- `giveaway-admin-dashboard`: adds a new "Giveaway list view" requirement - a guild-scoped list of all giveaways, entry point for the Start action.

## Impact

- **Affected code**: `app/Actions/Giveaways/` (new `StartGiveawayAction`), `app/Livewire/Giveaways/` (new `GiveawayIndex`; `CreateGiveaway` gains a scheduled-start field; `GiveawayDashboard` gains a Start control for drafts), `app/Console/Commands/` (new `giveaways:post-due` scheduled command alongside the existing `giveaways:close-expired`), `database/migrations/` (nullable `scheduled_start_at` on `giveaways` - the existing `starts_at` column already records when a giveaway actually went live), `routes/web.php` (new `guilds.giveaways.index`), `resources/views/components/dashboard-sidebar.blade.php` (repoint the "Giveaways" link), giveaway-related view titles/copy.
- **No bot-process changes**: posting mechanism and payload shape are unchanged (`discord-bot-gateway` - "Posting a giveaway message") - only *when* Laravel requests the post changes (immediately vs. on a schedule), which is entirely a Laravel-side concern.
- **No changes to entry/join behavior** (`giveaway-entry`) or the existing entrant search/fulfilment dashboard (`giveaway-admin-dashboard` - existing requirements) - only a new list view is added alongside them.
- **Multi-guild scoping**: the list view uses the same `guild_id` scoping and `GiveawayPolicy` authorization every other giveaway screen already uses - no new exposure.

## Non-goals

- No recurring pop-up giveaways. Unlike Standard Giveaways (which reuse the Events RRULE engine), a pop-up giveaway remains a single one-off run; "scheduled start" means one future date/time, not a repeating schedule.
- No rename of the `Giveaway` model, `giveaways` table, any route name, or any `giveaway-*` spec capability path - "Popup Giveaway" is a UI label only.
- No change to how items are assigned to entrants, the entry flow itself, or the existing entrant fulfilment dashboard - this change only adds the missing start mechanism and a list view around them.
- No general "edit a draft giveaway" screen beyond what already exists - `giveaway-lifecycle`'s existing "configuration immutability once started" requirement is unchanged, and editing a still-draft giveaway's fields is out of scope unless already possible today.
