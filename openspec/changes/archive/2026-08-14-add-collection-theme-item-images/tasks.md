## 1. Schema & model

- [x] 1.1 Migration: nullable `image_path` on `collection_theme_items`
- [x] 1.2 `CollectionThemeItem`: add `image_path` to `$fillable`, add `image_url` accessor (design.md - reusing `add-giveaway-description-and-image`'s pattern)
- [x] 1.3 `CollectionThemeItemFactory`: `withImage(string $path)` state

## 2. Admin UI

- [x] 2.1 `ManageCollectionThemeItems`: `WithFileUploads` image field on the add-item form, stored via `Storage::disk('public')->putFile('theme-item-images', ...)`
- [x] 2.2 `ManageCollectionThemeItemsAction::add()`: accept an optional stored image path
- [x] 2.3 `manage-collection-theme-items.blade.php`: show each item's image (where set) alongside its name
- [x] 2.4 Pest/Livewire tests: adding an item with an image persists it; adding without one leaves it null; the item list view shows the image when set (spec: `collection-themes` - Collection theme item management)

## 3. Popup giveaway public win announcement (bot)

- [x] 3.1 `App\Support\Giveaways\JoinResult::toArray()`: `item` payload gains `image_url` (via the model accessor, when set)
- [x] 3.2 `bot/src/index.js`'s join-interaction handler: branch on `result.status`, not on `image_url` - a `won` result replies `ephemeral: false` (public), prepending `<@discordUserId>`, as an embed with `.setImage()` when the item has one or plain public content when it doesn't; `already_entered`/`expired` stay `ephemeral: true`, byte-for-byte unchanged from today (design.md Decision 1) - `joinResultReply.js` itself is unchanged; the payload-building logic was extracted into a new pure `bot/src/joinInteractionReply.js` so it's independently testable, matching this codebase's existing pure-message-builder pattern
- [x] 3.3 Pest test: `JoinResult::toArray()` includes `image_url` when the assigned item has one, omits/nulls it otherwise
- [x] 3.4 Vitest coverage for the branch added in 3.2: a `won` result replies non-ephemeral (embed+image, and plain-public-text for no image); `already_entered`/`expired` still reply ephemeral, using a mocked interaction

## 4. Standard giveaway winner announcement (bot)

- [x] 4.1 `CloseAndDrawStandardGiveawayOccurrenceAction`: each `winners` payload entry gains `item_image_url`
- [x] 4.2 `bot/src/discordAdapter.js`'s `announceStandardGiveawayWinners`: one embed per winner (with that winner's item image) when `winners.length` is between 1 and 10; today's single combined embed otherwise (design.md Decision 2)
- [x] 4.3 Pest test: the announce-winners outbound payload includes `item_image_url` per winner when set
- [x] 4.4 Vitest coverage: per-winner embeds sent for a small winner count with images; falls back to the combined embed for zero winners and for a winner count over 10

## 5. Verification

- [x] 5.1 Full Pest suite passes
- [x] 5.2 Full bot Vitest suite passes
- [x] 5.3 `openspec validate add-collection-theme-item-images --strict` passes
