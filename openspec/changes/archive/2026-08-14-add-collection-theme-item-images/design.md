## Context

See proposal.md - Why. Relevant existing pieces this design builds on:

- `add-giveaway-description-and-image` establishes this app's image-storage pattern (local `public` disk, `image_path` column + a computed `image_url` accessor, `Storage::disk('public')->putFile(...)`). This change reuses that exact approach for `collection_theme_items` - no new storage mechanism.
- `App\Support\Giveaways\JoinResult::toArray()` already shapes the `item` payload (`{id, name}`) the bot's `joinResultReply.js` consumes for the pop-up giveaway's instant ephemeral reply.
- `CloseAndDrawStandardGiveawayOccurrenceAction` already builds a `winners` array (`discord_user_id`, `username`, `item_id`, `item_name`) for the outbound "announce winners" action, consumed by `bot/src/discordAdapter.js`'s `announceStandardGiveawayWinners`.
- `ManageCollectionThemeItems`/`ManageCollectionThemeItemsAction` already handle adding/removing items one at a time.

## Goals / Non-Goals

**Goals:**
- An item can have an image, shown in the admin UI and both Discord winner-reveal moments.
- A pop-up giveaway win is announced publicly in the channel, not just to the winner.

**Non-Goals:**
- No image in the pre-draw occurrence post or the fulfilment dashboard (proposal.md).
- No new storage mechanism - reuses `add-giveaway-description-and-image`'s approach.
- No change to *what* triggers a win (entry/assignment logic untouched) - only how it's revealed.

## Decisions

### Decision 1: A new win replies publicly (mentioning the winner); already-entered/expired stay exactly as they are today
`JoinResult::toArray()`'s `item` array gains `'image_url' => $this->item->image_url` (via the new accessor) alongside `id`/`name`. `joinResultReply.js` itself is **unchanged** - still a pure `{status, item} -> text` mapper, still independently tested as-is; its existing `won` text ("🎉 You won **X**! ...") reads correctly whether said privately or publicly, since it already addresses the reader directly.

The branch is on `result.status`, not on `image_url`, in `bot/src/index.js`'s interaction handler:
- `status === 'won'`: reply **non-ephemeral** (`ephemeral: false`), visible to the whole channel. Prepend the winner's mention (`<@${interaction.user.id}>`) so it's clear who won even though the interaction itself is anonymous-looking in the channel. Uses an embed with `.setImage(result.item.image_url)` when the item has one; otherwise a plain public `content` string - no behavior difference for items without an image beyond visibility.
- `status === 'already_entered'` or `'expired'`: unchanged - ephemeral, exactly as today.

**Implementation note**: the reply-options-building logic (the status branch, the mention prefix, the embed-vs-plain-content choice) was pulled into a new pure `bot/src/joinInteractionReply.js` (`buildJoinInteractionReplyOptions(result, discordUserId)`) rather than left inline in `index.js`'s event handler - matches this codebase's existing pattern of pure, independently-testable message builders (`joinResultReply.js`, `eventOccurrenceMessage.js`, `standardGiveawayOccurrenceMessage.js`) and is what makes task 3.4's Vitest coverage possible without a live interaction/client.

**Alternative considered**: post the public win announcement as a separate `channel.send(...)` in addition to an ephemeral acknowledgment reply. Rejected - Discord requires *some* reply to the interaction within its timeout regardless, so a single non-ephemeral `interaction.reply()` already satisfies that requirement and posts publicly in one action; a second message would be redundant.
**Alternative considered**: rewrite `joinResultReply`'s `won` text into third person for a public audience. Rejected - unnecessary churn to a tested pure function; prepending the winner's mention to the existing first/second-person text already reads naturally as a public announcement (a very common Discord bot pattern: "@user, you won X!").

### Decision 2: Winner announcement uses one embed per winner (with that winner's item image) when the winner count is small enough for one Discord message; falls back to today's single combined embed otherwise
Discord allows up to 10 embeds in one message. `CloseAndDrawStandardGiveawayOccurrenceAction`'s `winners` payload entries gain `item_image_url`. `announceStandardGiveawayWinners` in `bot/src/discordAdapter.js`:
- When `winners.length` is 0 or exceeds 10: keep today's single embed (name + item per line in the description, no images) - unchanged behavior for a zero-winner close or an unusually large winner count.
- Otherwise: send one embed per winner (title naming the winner, description naming their item, `.setImage(item_image_url)` when present), all in the same `channel.send({ embeds: [...] })` call.

**Alternative considered**: always use a single embed's `.setImage()` for just the first winner's item. Rejected - misleading when winners received different items; one-embed-per-winner is unambiguous and Discord natively supports multiple embeds in one message for exactly this kind of case.

### Decision 3: `ManageCollectionThemeItemsAction::add()` accepts an already-stored image path, same shape as the giveaway image upload
`ManageCollectionThemeItems` gains a `WithFileUploads` field on its add-item form, validated and stored (`Storage::disk('public')->putFile('theme-item-images', ...)`) the same way as `add-giveaway-description-and-image`'s giveaway image fields, before being passed to the Action.

## Risks / Trade-offs

- **[Risk]** A theme with many items each carrying an image increases storage use over time, with no image-removal UI in this pass. → **Mitigation**: accepted for now - images are optional and admin-uploaded deliberately; a management/cleanup screen is a reasonable future addition, not blocking this change.
- **[Risk]** A popular giveaway with many entrants means many public "X won Y" messages in a short window - this is the explicit goal (hype), but it does mean more channel noise than today's silent-to-everyone-else joins. → **Mitigation**: accepted - this is the requested behavior change, not a side effect; if it turns out to be too noisy in practice for very large giveaways, that's a follow-up tuning decision (e.g. a per-giveaway opt-out), not something to guess at and build speculatively now.

## Migration Plan

One additive migration: nullable `image_path` on `collection_theme_items`. No backfill - existing items simply have none, identical to today's behavior everywhere an image isn't set.
