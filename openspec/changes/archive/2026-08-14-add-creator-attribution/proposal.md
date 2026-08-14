## Why

Nothing in the dashboard records or shows which admin created a given pop-up giveaway, standard giveaway, or event - once created, a series is anonymous. With more than one admin working a guild, staff want to see who set something up, both on list tiles and detail views.

## What Changes

- Add a nullable `created_by_user_id` foreign key (to `users`) on `giveaways`, `standard_giveaways`, and `events`.
- `CreateGiveawayAction`, `CreateStandardGiveawayAction`, and `CreateEventAction` each accept the acting admin and record it at creation time; the three Livewire create components pass `Auth::user()` (already resolved and non-null at `save()` time, since each already calls `$this->authorize('manage', $this->guild)` before saving).
- List tiles and detail views for all three (giveaway index/dashboard, standard giveaway index/occurrence dashboard, event index/summary) show "Created by {name}" using the creator's dashboard account name.
- Nullable, not backfilled: existing rows created before this change simply show no creator (never fabricate an owner for pre-existing data).

## Capabilities

### Modified Capabilities

- `giveaway-lifecycle`: giveaway creation additionally records the creating admin.
- `standard-giveaways`: standard giveaway creation additionally records the creating admin.
- `events`: event creation additionally records the creating admin.

## Impact

- Multi-guild scoping: unaffected - `created_by_user_id` references a `users` row that is already implicitly guild-scoped via `GuildAdmin`/`isAdminOfGuild()`; no new cross-guild exposure, since only that guild's admins can view the record in the first place.
- Migrations: `add_created_by_user_id_to_giveaways_table`, `add_created_by_user_id_to_standard_giveaways_table`, `add_created_by_user_id_to_events_table`.
- `App\Models\Giveaway`, `App\Models\StandardGiveaway`, `App\Models\Event` (new `creator()` relation on each).
- `App\Actions\Giveaways\CreateGiveawayAction`, `App\Actions\StandardGiveaways\CreateStandardGiveawayAction`, `App\Actions\Events\CreateEventAction`.
- `App\Livewire\Giveaways\CreateGiveaway`, `App\Livewire\StandardGiveaways\CreateStandardGiveaway`, `App\Livewire\Events\CreateEvent`.
- List/detail Blade views: `giveaway-index.blade.php`, `giveaway-dashboard.blade.php`, `standard-giveaway-index.blade.php`, `occurrence-dashboard.blade.php` (or wherever the series-level summary lives), `event-index.blade.php`/`event-summary.blade.php`.

## Non-goals

- No backfill of `created_by_user_id` for rows that predate this change - they show no creator, not a guessed one.
- No "created by" tracking on lower-level records (individual occurrences, entries, signups, collection themes) - scoped to the three top-level series/giveaway records only, per the request.
- No editing/reassigning the creator after the fact - it's set once, at creation, same as a timestamp.
