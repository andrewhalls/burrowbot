## 1. Popup Giveaway schema

- [x] 1.1 Migration: nullable `description` (text) + `image_path` (string) on `giveaways`
- [x] 1.2 `Giveaway` model: add both to `$fillable`, add `image_url` accessor (design.md Decision 1)
- [x] 1.3 `GiveawayFactory`: `withDescription(string $text)` and `withImage(string $path)` states

## 2. Popup Giveaway create UI

- [x] 2.1 `CreateGiveawayAction`: accept optional `description` and an already-stored `imagePath`
- [x] 2.2 `CreateGiveaway` Livewire component: description textarea; `WithFileUploads` image field (validated `image`, `max:5120`), stored via `Storage::disk('public')->putFile('giveaway-images', ...)` on save
- [x] 2.3 Pest/Livewire tests: creating with a description and image persists both; creating without either leaves both null; an oversized/non-image upload is rejected with a validation error (spec: `giveaway-lifecycle` - Giveaway creation)

## 3. Standard Giveaway schema

- [x] 3.1 Migration: nullable `image_path` on `standard_giveaways`
- [x] 3.2 Migration: nullable `image_path` on `standard_giveaway_occurrences`
- [x] 3.3 `StandardGiveaway`/`StandardGiveawayOccurrence` models: add `image_path` to `$fillable`, add `image_url` accessor to both
- [x] 3.4 `StandardGiveawayFactory`/`StandardGiveawayOccurrenceFactory`: `withImage(string $path)` states

## 4. Standard Giveaway create/edit UI + occurrence snapshotting

- [x] 4.1 `CreateStandardGiveaway` Livewire component: `WithFileUploads` image field (same validation as 2.2), stored via `Storage::disk('public')->putFile('standard-giveaway-images', ...)`
- [x] 4.2 `UpdateStandardGiveawayAction`: add `image_path` to its editable-fields whitelist; deletes the previous file from disk when replaced (design.md Decision 2, revised - only once no occurrence still references the old path)
- [x] 4.3 `standard-giveaways:generate-occurrences`: snapshot `image_path` into each generated occurrence, alongside every other already-snapshotted field (design.md Decision 4)
- [x] 4.4 Pest tests: creating a standard giveaway with an image persists it; editing the image on a series with existing occurrences leaves already-generated occurrences' images unchanged and deletes the old file from disk; newly-generated occurrences after the edit use the new image (spec: `standard-giveaways` - Standard giveaway creation, Editing a standard giveaway series only affects future occurrences)

## 5. List/dashboard display

- [x] 5.1 `GiveawayIndex`/`giveaway-index.blade.php`: show description and image (where set) per giveaway
- [x] 5.2 `StandardGiveawayIndex`/`standard-giveaway-index.blade.php`: show image (description already shown or easily added alongside it) per giveaway
- [x] 5.3 Pest/Livewire tests: a giveaway with a description/image shows both on its list view; one without either still renders cleanly (spec: `giveaway-admin-dashboard` - Giveaway list view)

## 6. Discord posting (bot process)

- [x] 6.1 `StartGiveawayAction`: outbound-action payload gains `description` (giveaway's own, or omitted so the bot falls back to its default line) and `image_url` (via the model's `image_url` accessor, when set) - implemented in `PostGiveawayMessage` job, which is where this payload is actually built
- [x] 6.2 `standard-giveaways:post-due-occurrences`: outbound-action payload gains `image_url` (via the occurrence's own `image_url` accessor, when set)
- [x] 6.3 `bot/src/discordAdapter.js`'s `postGiveawayMessage`: use `payload.description` when present (falling back to today's fixed line), `.setImage(payload.image_url)` when present (design.md Decision 3)
- [x] 6.4 `bot/src/standardGiveawayOccurrenceMessage.js`: `.setImage(image_url)` when present
- [x] 6.5 Vitest coverage: `postGiveawayMessage` uses the custom description when given and the default when not, and sets the embed image only when `image_url` is present; `buildStandardGiveawayOccurrenceMessage` sets the embed image only when `image_url` is present

## 7. Documentation

- [x] 7.1 Update `openapi.yaml`'s `OutboundAction` payload description to mention `description`/`image_url` for the two posting action types; lint clean

## 8. Verification

- [x] 8.1 Full Pest suite passes
- [x] 8.2 Full bot Vitest suite passes
- [x] 8.3 `openspec validate add-giveaway-description-and-image --strict` passes
