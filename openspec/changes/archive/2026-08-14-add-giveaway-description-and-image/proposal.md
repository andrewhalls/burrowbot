## Why

Neither giveaway type gives staff a way to add their own description or image to a giveaway post. Standard Giveaway already has a `description` field but never shows it in the Discord post; Popup Giveaway has neither field at all, and posts with a fixed generic title/description every time. Staff want giveaway posts to look distinct and on-brand.

## What Changes

- Add `description` (nullable) and an uploaded `image` to Popup Giveaway, set when creating it.
- Add an uploaded `image` to Standard Giveaway (it already has `description`), set when creating or editing it.
- Both Discord post types show the image (as the embed's image) and the description (Popup Giveaway: replaces the generic canned text when set, falls back to it when not, so existing behavior is unchanged for a giveaway with no description; Standard Giveaway: already shows its description today - unaffected).
- Standard Giveaway occurrences snapshot the image path at generation time, exactly like every other occurrence field (`standard-giveaway-occurrences` - existing snapshot-isolation requirement) - editing a series' image never changes an already-generated occurrence.

## Capabilities

### Modified Capabilities
- `giveaway-lifecycle`: "Giveaway creation" gains optional description and image fields.
- `giveaway-admin-dashboard`: giveaway list/dashboard views show the description/image where a giveaway is displayed.
- `standard-giveaways`: creation/editing gains an optional image field.
- `standard-giveaway-occurrences`: occurrence generation snapshots the image, same as every other series field.
- `discord-bot-gateway`: "Posting a giveaway message" and "Posting a standard giveaway occurrence" gain the image (and, for Popup Giveaway, the description) in the posted embed.

## Impact

- **Affected code**: migrations adding `description`/`image_path` to `giveaways`, `image_path` to `standard_giveaways` and `standard_giveaway_occurrences`; `CreateGiveaway`/`CreateStandardGiveaway` Livewire components gain a description field (Popup only) and a Livewire file upload; `GiveawayIndex`/`StandardGiveawayIndex`/dashboards show the image where a giveaway is listed; `bot/src/discordAdapter.js` and `bot/src/standardGiveawayOccurrenceMessage.js` add `.setImage()`; outbound-action payloads for both posting types gain an `image_url`.
- **First file-upload feature in this app** - no existing upload pattern to extend; see design.md for the storage approach.
- **No changes** to entry/join behavior, random item assignment, or any non-posting requirement.

## Non-goals

- No image editing/cropping UI - upload as-is, Discord scales embed images itself.
- No image on the entry-result reply or the winner announcement for *giveaways themselves* (image only appears on the initial giveaway post) - a theme item's own image in the winner announcement is a separate change (`add-collection-theme-item-images`).
- No retroactive backfill or bulk-edit tooling for existing giveaways created before this change - they simply have no description/image until an admin sets one (Popup Giveaway: not possible after creation, matching its existing no-edit-UI non-goal; Standard Giveaway: via its existing edit action).
