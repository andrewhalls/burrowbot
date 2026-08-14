## 1. Event image schema

- [x] 1.1 Migration: nullable `image_path` on `events`
- [x] 1.2 Migration: nullable `image_path` on `event_occurrences`
- [x] 1.3 `Event`/`EventOccurrence` models: add `image_path` to `$fillable`, add `image_url` accessor to both (mirrors `StandardGiveaway`/`StandardGiveawayOccurrence` exactly)
- [x] 1.4 `EventFactory`/`EventOccurrenceFactory`: `withImage(string $path)` state on both

## 2. Event image wiring (create + posting)

- [x] 2.1 `CreateEventAction`: accept optional `imagePath`, store on the event and snapshot into the immediate one-off occurrence
- [x] 2.2 `CreateEvent` Livewire component: `WithFileUploads` image field (validated `image`, `max:5120`), stored via `Storage::disk('public')->putFile('event-images', ...)`; render in `create-event.blade.php`
- [x] 2.3 `GenerateEventOccurrences`: snapshot `image_path` into each generated occurrence
- [x] 2.4 `PostDueEventOccurrences`: outbound payload gains `image_url` (via the occurrence's accessor)
- [x] 2.5 `bot/src/eventOccurrenceMessage.js`: `.setImage(image_url)` when present
- [x] 2.6 Vitest coverage: `buildEventOccurrenceMessage` sets the embed image only when `image_url` is present
- [x] 2.7 Pest tests: creating an event with an image persists it and snapshots into its one-off occurrence; `generate-occurrences` snapshots the current image into new occurrences; `post-due-occurrences` includes `image_url` in the outbound payload when set
- [x] 2.8 Add `image_url` to the `post_event_occurrence_thread`/`post_event_occurrence_message` payload description in `openapi.yaml`, lint clean

## 3. Standard Giveaway prize item / required role re-sync

- [x] 3.1 `UpdateStandardGiveawayAction`: accept optional `prizeItemIds`/`requiredRoleIds`; when present, delete and recreate `StandardGiveawayPrizeItem`/`StandardGiveawayRequiredRole` rows for that giveaway from the new selection (design.md Decision 3)
- [x] 3.2 Pest tests: updating prize items replaces the set; updating required roles replaces the set; omitting either from the update attributes leaves the existing rows untouched; already-generated occurrences keep their original snapshotted prize items/roles

## 4. Edit Popup Giveaway

- [x] 4.1 `EditGiveaway` Livewire component (`app/Livewire/Giveaways/EditGiveaway.php`): mirrors `CreateGiveaway`'s fields (channel, theme, duration, description, image, scheduled start) pre-filled from the giveaway, `use ResolvesBrowserTimezone`, calls `UpdateGiveawayDraftAction` on save
- [x] 4.2 `edit-giveaway.blade.php`: mirrors `create-giveaway.blade.php`'s form
- [x] 4.3 `GiveawayDashboard`: "Edit" toggle next to "Start" (draft-only), swapping the entrant table for `<livewire:giveaways.edit-giveaway>` while active
- [x] 4.4 Pest/Livewire tests: editing a draft giveaway's fields persists the change; editing is rejected once the giveaway is active/closed; the Edit toggle is only offered while draft

## 5. Edit Standard Giveaway

- [x] 5.1 `EditStandardGiveaway` Livewire component: mirrors `CreateStandardGiveaway`'s fields (title, description, image, channel, posting mode, winner count, booster-only, required roles, prize items, recurrence) pre-filled from the giveaway, `use ResolvesBrowserTimezone, SearchesDiscordRoles`, calls the now-extended `UpdateStandardGiveawayAction`
- [x] 5.2 `edit-standard-giveaway.blade.php`: mirrors `create-standard-giveaway.blade.php`'s form
- [x] 5.3 `StandardGiveawayIndex` detail panel: "Edit series" toggle above the occurrence dashboard, swapping in `<livewire:standard-giveaways.edit-standard-giveaway>`
- [x] 5.4 Pest/Livewire tests: editing persists scalar fields, prize items, and required roles; already-generated occurrences are unaffected; a newly-generated occurrence after the edit reflects the new values

## 6. Edit Event

- [x] 6.1 `EditEvent` Livewire component: mirrors `CreateEvent`'s fields (title, description, image, channel, role set, posting mode, recurrence) pre-filled from the event, `use ResolvesBrowserTimezone`, calls `UpdateEventAction`
- [x] 6.2 `edit-event.blade.php`: mirrors `create-event.blade.php`'s form
- [x] 6.3 `event-summary.blade.php` / `EventIndex`: "Edit" toggle swapping the read-only summary for `<livewire:events.edit-event>`
- [x] 6.4 Pest/Livewire tests: editing persists scalar fields and image; already-generated occurrences are unaffected; a newly-generated occurrence after the edit reflects the new values

## 7. Verification

- [x] 7.1 Full Pest suite passes
- [x] 7.2 Full bot Vitest suite passes
- [x] 7.3 `openspec validate add-giveaway-and-event-editing --strict` passes
