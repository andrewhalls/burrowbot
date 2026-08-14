## 1. Schema

- [x] 1.1 Migration: add nullable `created_by_user_id` (FK to `users`, `nullOnDelete()`) to `giveaways`
- [x] 1.2 Migration: add nullable `created_by_user_id` (FK to `users`, `nullOnDelete()`) to `standard_giveaways`
- [x] 1.3 Migration: add nullable `created_by_user_id` (FK to `users`, `nullOnDelete()`) to `events`
- [x] 1.4 Add `$fillable` entries and a `creator(): BelongsTo<User, $this>` relation to `Giveaway`, `StandardGiveaway`, `Event`
- [x] 1.5 Update the three factories with an optional `createdBy()` state

## 2. Recording the creator

- [x] 2.1 `CreateGiveawayAction::execute()` accepts `?User $createdBy` and stores it; `CreateGiveaway::save()` passes `Auth::user()`
- [x] 2.2 `CreateStandardGiveawayAction::execute()` accepts `?User $createdBy` and stores it; `CreateStandardGiveaway::save()` passes `Auth::user()`
- [x] 2.3 `CreateEventAction::execute()` accepts `?User $createdBy` and stores it; `CreateEvent::save()` passes `Auth::user()`
- [x] 2.4 Pest tests: each action records the passed creator; each Livewire `save()` test asserts the authenticated admin ends up as the creator

## 3. Display

- [x] 3.1 `giveaway-index.blade.php` tile and `giveaway-dashboard.blade.php` detail view: show "Created by {{ $giveaway->creator?->name }}" when set
- [x] 3.2 `standard-giveaway-index.blade.php` tile and its occurrence dashboard: show "Created by {{ $giveaway->creator?->name }}" when set
- [x] 3.3 `event-index.blade.php` tile and `event-summary.blade.php` detail view: show "Created by {{ $event->creator?->name }}" when set
- [x] 3.4 Pest/Livewire tests: a record with a creator shows the name; a record with none (nullable, unbackfilled) shows nothing

## 4. Verification

- [x] 4.1 Run the full Pest suite, confirm no regressions
- [x] 4.2 `npm run build`, confirm no Vite/Tailwind errors
