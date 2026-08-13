## 1. Schema & start action

- [ ] 1.1 Migration: nullable `scheduled_start_at` timestamp on `giveaways`
- [ ] 1.2 `GiveawayFactory`: add a `scheduledFor(Carbon $when)` state
- [ ] 1.3 `App\Actions\Giveaways\StartGiveawayAction`: locked, idempotent draft→active transition posting to Discord via the existing `TYPE_POST_GIVEAWAY_MESSAGE` outbound action and setting `starts_at`/`ends_at` (design.md Decision 1)
- [ ] 1.4 Pest tests for `StartGiveawayAction`: transitions draft→active and sets `starts_at`/`ends_at`; enqueues the outbound action with the existing payload shape; calling it twice (or concurrently via two locked calls) only starts the giveaway once (spec: `giveaway-lifecycle` - Starting a giveaway)

## 2. Scheduled start

- [ ] 2.1 `giveaways:post-due` console command: finds `draft` giveaways with `scheduled_start_at <= now()` and calls `StartGiveawayAction` for each (design.md Decision 2 - explicit time filter, not a blind sweep)
- [ ] 2.2 Register `giveaways:post-due` in `routes/console.php` (`everyMinute()`), alongside the existing `giveaways:close-expired`
- [ ] 2.3 Pest tests: a giveaway whose `scheduled_start_at` has passed is started by the command; one whose scheduled time hasn't arrived yet is untouched; a giveaway with no `scheduled_start_at` is untouched by this command (manual start only); running the command twice doesn't double-post (spec: `giveaway-lifecycle` - Scheduled start)

## 3. Create & dashboard UI

- [ ] 3.1 `CreateGiveawayAction`: accept an optional `scheduledStartAt`, validated strictly in the future when provided
- [ ] 3.2 `CreateGiveaway` Livewire component: optional scheduled-start date/time field; submitting without one behaves exactly as today (plain draft, no scheduled start)
- [ ] 3.3 `GiveawayDashboard`: add a "Start" control for `draft` giveaways, calling `StartGiveawayAction`, guarded by `GiveawayPolicy::manage`
- [ ] 3.4 Pest/Livewire tests: creating with a future scheduled start persists it; creating without one is unaffected; starting manually from the dashboard works and is guild-scoped/policy-guarded; a non-future scheduled start is rejected with a validation error

## 4. Giveaway list view

- [ ] 4.1 `App\Livewire\Giveaways\GiveawayIndex`: guild-scoped list of giveaways with status and entrant count (`withCount('entries')`), a Start action on `draft` rows, links into `GiveawayDashboard`; toggleable inline create form matching `EventIndex`/`StandardGiveawayIndex` (design.md Decision 3)
- [ ] 4.2 View + route `guilds.giveaways.index` at `/guilds/{guild}/giveaways`
- [ ] 4.3 Update `resources/views/components/dashboard-sidebar.blade.php`: "Giveaways" link now targets `guilds.giveaways.index` instead of `guilds.giveaways.create`
- [ ] 4.4 Pest/Livewire tests: lists only the current guild's giveaways with correct status/entrant counts; 403 for a non-admin of that guild; the list's Start action works and updates the shown status (spec: `giveaway-admin-dashboard` - Giveaway list view)

## 5. "Popup Giveaway" relabeling

- [ ] 5.1 Update page titles, headings, and button copy across the create form, dashboard, list view, and the sidebar link label to say "Popup Giveaway" - UI copy only, no identifier renames (design.md Decision 4)

## 6. Verification

- [ ] 6.1 Full Pest suite passes
- [ ] 6.2 `openspec validate improve-popup-giveaway-lifecycle --strict` passes
