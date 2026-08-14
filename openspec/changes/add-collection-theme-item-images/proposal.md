## Why

Collection theme items are text-only today - staff can't attach an image to an item, and entrants/winners never see what they actually won beyond a name. An image makes both the admin's item list and Discord winner reveals far more useful. Separately: a pop-up giveaway win is currently a reply only the winner ever sees, which wastes the moment - other members in the channel never see anyone winning, so there's no visible momentum/hype while a giveaway is running.

## What Changes

- Add an optional image to a collection theme item, set when adding or editing an item.
- Show it in the admin theme-management screen alongside each item.
- Show it in Discord when a member wins that item: the pop-up giveaway's win reveal, and the standard giveaway's "winners announced" post.
- **Behavior change**: a pop-up giveaway win is now announced publicly in the channel (naming the winner and item, with the item's image when it has one) instead of only the winner seeing it. A duplicate-entry reply ("you already won X") and a too-late reply stay private/ephemeral as today - only a genuine new win becomes public; this isn't tied to whether the item has an image.

## Capabilities

### Modified Capabilities
- `collection-themes`: item creation/management gains an optional image.
- `giveaway-entry`: "Entrant sees their result" - a new win is now announced publicly (not ephemeral) and includes the item's image when it has one; already-entered/expired results remain private, unchanged.
- `discord-bot-gateway`: "Announcing drawn winners" (standard giveaway) includes each winner's item's image when it has one.

## Impact

- **Affected code**: migration adding `image_path` to `collection_theme_items`; `ManageCollectionThemeItems` Livewire component gains a file upload; `bot/src/index.js`'s join-interaction handler (a new win replies non-ephemeral - embed with image when the item has one, plain public text otherwise; already-entered/expired stay ephemeral, unchanged); `bot/src/discordAdapter.js`'s `announceStandardGiveawayWinners` (standard giveaway winner announcement).
- **Reuses** the local-disk image storage approach from `add-giveaway-description-and-image` (same `public` disk, same relative-path-plus-accessor pattern) - no new storage mechanism.
- **No changes** to random item assignment, entry/join validation, or fulfilment tracking - purely additive display and reply-visibility.

## Non-goals

- No image on the giveaway-admin fulfilment dashboard's entrant list (`giveaway-admin-dashboard`) - that screen is out of scope for this change; only the theme-item-management screen and the two Discord winner-reveal moments are in scope.
- No per-item image in the standard giveaway occurrence's own *posting* (the "here's what you could win" pre-draw post) - only the *post-draw* winner announcement, where a specific member's specific won item is known. Showing prize images before the draw is a separate, larger change (the occurrence post already lists prize item names; adding images there touches `standard-giveaway-occurrences` - "Posting an occurrence to Discord," not scoped here).
- No bulk image upload/management tooling - one image per item, uploaded individually.
