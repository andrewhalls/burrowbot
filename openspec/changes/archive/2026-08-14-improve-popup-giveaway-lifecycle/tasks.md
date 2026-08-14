## 1. Schema & start action

- [x] 1.1 Migration: nullable `scheduled_start_at` timestamp on `giveaways`
- [x] 1.2 `GiveawayFactory`: add a `scheduledFor(Carbon $when)` state
- [x] 1.3 `App\Actions\Giveaways\StartGiveawayAction`: locked draft→active transition dispatching `PostGiveawayMessage` onto the outbound queue and setting `starts_at`/`ends_at` (design.md Decision 1, revised during implementation - see note there: pre-existing `PostGiveawayMessage` job and a pre-written `StartGiveawayActionTest` expected the job-dispatch pattern and a thrown `InvalidArgumentException` for a non-draft giveaway, not the originally-drafted inline-create/idempotent-no-op design). Also added `App\Actions\Giveaways\UpdateGiveawayDraftAction` (Decision 1a) to satisfy a pre-existing `UpdateGiveawayDraftActionTest` that wasn't part of the original proposal scope - backend action only, no UI screen, per proposal.md's non-goals.
- [x] 1.4 Pest tests for `StartGiveawayAction` were already present in the repo (`StartGiveawayActionTest.php`) and passed once the action matched their expected contract; `UpdateGiveawayDraftActionTest.php` likewise pre-existed and passed once `UpdateGiveawayDraftAction` was added (spec: `giveaway-lifecycle` - Starting a giveaway)

## 2. Scheduled start

- [x] 2.1 `giveaways:post-due` console command: finds `draft` giveaways with `scheduled_start_at <= now()` and calls `StartGiveawayAction` for each, catching `InvalidArgumentException` per-giveaway so a race with a manual start doesn't abort the batch (design.md Decision 2 - explicit time filter, not a blind sweep)
- [x] 2.2 Registered `giveaways:post-due` in `routes/console.php` (`everyMinute()`), alongside the existing `giveaways:close-expired`
- [x] 2.3 Pest tests (`PostDueGiveawaysTest.php`): a giveaway whose `scheduled_start_at` has passed is started by the command; one whose scheduled time hasn't arrived yet is untouched; a giveaway with no `scheduled_start_at` is untouched by this command (manual start only); running the command twice doesn't double-post; dispatches `PostGiveawayMessage` per started giveaway (spec: `giveaway-lifecycle` - Scheduled start)

## 3. Create & dashboard UI

- [x] 3.1 `CreateGiveawayAction`: accepts an optional `scheduledStartAt`, defaulting to `null` (non-breaking for the existing 4-arg call site)
- [x] 3.2 `CreateGiveaway` Livewire component: optional scheduled-start date/time fields (`scheduledStartDate`/`scheduledStartTime`), validated together and strictly in the future when provided; submitting without them behaves exactly as before
- [x] 3.3 `GiveawayDashboard`: added a "Start popup giveaway" control for `draft` giveaways, calling `StartGiveawayAction`, guarded by `GiveawayPolicy::manage`
- [x] 3.4 Pest/Livewire tests: creating with a future scheduled start persists `scheduled_start_at` and stays draft; a past scheduled start is rejected with a validation error and creates nothing; starting manually from the dashboard transitions draft→active; a non-admin is denied at mount (existing "denies dashboard access" coverage already exercises the `start()` gate's precondition)

## 4. Giveaway list view

- [x] 4.1 `App\Livewire\Giveaways\GiveawayIndex`: guild-scoped list of giveaways with status pill and entrant count (`withCount('entries')`), a Start action on `draft` rows, links into `GiveawayDashboard` via "Manage"; toggleable inline `<livewire:giveaways.create-giveaway>` create form matching `EventIndex`/`StandardGiveawayIndex` (design.md Decision 3)
- [x] 4.2 View (`giveaway-index.blade.php`) + route `guilds.giveaways.index` at `/guilds/{guild}/giveaways` (kept the existing `guilds.giveaways.create` route intact - the index embeds its own inline create form, but the standalone create page still works if linked directly)
- [x] 4.3 Updated `dashboard-sidebar.blade.php` and `dashboard-topbar.blade.php`: "Giveaways"/breadcrumb link now targets `guilds.giveaways.index`, relabeled "Popup giveaways"; updated the two existing tests (`DashboardHomeTest`, `GuildNavTest`) whose assertions referenced the old `guilds.giveaways.create` href for the sidebar link
- [x] 4.4 Pest/Livewire tests (`GiveawayIndexTest.php`): lists only the current guild's giveaways with status/entrant count visible; excludes another guild's giveaway entirely; the list's Start action transitions a draft to active; denies mounting for a non-admin; denies starting a giveaway belonging to a different guild (404, guild-scoped `findOrFail`)

## 5. "Popup Giveaway" relabeling

- [x] 5.1 Updated page titles, headings, and button copy across the create form ("Create popup giveaway"), dashboard ("Start popup giveaway"), list view ("Popup giveaways"), dashboard-home's guild-card link, and the sidebar/topbar labels - UI copy only, no identifier renames (design.md Decision 4)

## 6. Verification

- [x] 6.1 Full Pest suite passes (294/294)
- [x] 6.2 `openspec validate improve-popup-giveaway-lifecycle --strict` passes
