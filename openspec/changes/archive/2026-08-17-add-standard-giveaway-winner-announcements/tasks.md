## 1. Data model

- [x] 1.1 Create a migration adding nullable `banner_image_path` (string), `claim_link` (string), `claim_deadline_hours` (unsigned smallint), `congrats_message_template` (text) columns to both `standard_giveaways` and `standard_giveaway_occurrences`
- [x] 1.2 Add the four new fields to `StandardGiveaway`'s `$fillable`/casts and add a `banner_image_url` accessor alongside the existing `image_url` accessor
- [x] 1.3 Add the four new fields to `StandardGiveawayOccurrence`'s `$fillable`/casts and add its own `banner_image_url` accessor
- [x] 1.4 Update `StandardGiveawayFactory` and `StandardGiveawayOccurrenceFactory` with states/defaults for the new fields (default null, matching existing optional-field patterns)

## 2. Series configuration (create/edit)

- [x] 2.1 Extend `CreateStandardGiveawayAction` to accept and persist `banner_image_path`, `claim_link`, `claim_deadline_hours`, `congrats_message_template`, and to snapshot them onto the immediate occurrence it creates for one-off giveaways
- [x] 2.2 Extend `UpdateStandardGiveawayAction` to accept the same four fields, reusing the existing orphan-safe delete pattern (`image_path`) for `banner_image_path` when replaced
- [x] 2.3 Add banner image upload, claim link, claim deadline (hours), and congrats message template fields to `CreateStandardGiveaway` Livewire component + `create-standard-giveaway.blade.php`, with `nullable` validation rules matching the optional nature of these fields
- [x] 2.4 Add the same fields to `EditStandardGiveaway` Livewire component + `edit-standard-giveaway.blade.php`
- [x] 2.5 Extend `GenerateStandardGiveawayOccurrences` to snapshot the four new fields from the series onto each generated occurrence
- [x] 2.6 Pest tests: creating/editing a series with each new field set and unset; editing an ongoing recurring series' banner image and congrats template only affects occurrences generated after the edit (mirrors existing `image_path`/`description` tests)

## 3. Congrats message rendering

- [x] 3.1 Create `App\Support\StandardGiveaways\RenderCongratsMessage`: given a template string, a list of winner Discord user ids, a prize name string, a claim link, and a claim-deadline `Carbon` instant, substitutes `{winners}` (comma-joined `<@id>` mentions), `{prize}`, `{claim_link}`, `{claim_deadline}` (as a Discord relative timestamp `<t:...:R>`), leaving any unrecognized token or a template with none of these placeholders unchanged
- [x] 3.2 Pest unit tests: all four placeholders present; a subset present; no placeholders present; an unrecognized `{other}` token left literal; empty winners list

## 4. Live post: banner + pending winners + footer

- [x] 4.1 Extend `PostDueStandardGiveawayOccurrences`'s outbound payload with `banner_image_url` (from the occurrence's new accessor)
- [x] 4.2 Rework `bot/src/standardGiveawayOccurrenceMessage.js`'s `buildStandardGiveawayOccurrenceMessage` to: prepend a banner embed (image-only) when `banner_image_url` is present; add a "Winners" field (pending placeholder text when no `winners`/`ended` state is passed); accept an optional `winners` array + `ended` flag that, when provided, render the Winners field from that array (or "No winners this time." when empty), drop the Enter button, and add a footer showing the occurrence id (`ID: {occurrence_id}`)
- [x] 4.3 Update the two call sites in `discordAdapter.js` (`postStandardGiveawayThread`, `postStandardGiveawayMessage`) if the function signature changes - confirmed no change needed, the new second parameter defaults to the pending/live state; `postStandardGiveawayThread` was separately updated to also capture and return the thread's message id (needed for task 5)
- [x] 4.4 Bot-side tests confirming: no banner when unset, banner embed present and first when set, pending Winners field present, footer absent on the live post (vitest, `bot/tests/standardGiveawayOccurrenceMessage.test.js`)

## 5. Close/draw: edit original message + send congrats message

- [x] 5.1 Extend `CloseAndDrawStandardGiveawayOccurrenceAction`'s outbound payload (still `TYPE_ANNOUNCE_STANDARD_GIVEAWAY_WINNERS`) with: the same content fields `buildStandardGiveawayOccurrenceMessage` needs (title, description, image_url, banner_image_url, requires_booster, required_role_ids, prize_item_names, ends_at) so the bot can rebuild the message from scratch; an absolute `claim_deadline_at` ISO8601 string computed as `now()->addHours($occurrence->claim_deadline_hours)` when set, else `null`; a `congrats_message` string rendered via `RenderCongratsMessage` when `$occurrence->congrats_message_template` is set, else `null`
- [x] 5.2 In `bot/src/discordAdapter.js`'s `announceStandardGiveawayWinners`: fetch and edit the original message (`discord_message_id`, falling back to the existing per-winner-embed message.send logic only if `discord_message_id` is absent) using `buildStandardGiveawayOccurrenceMessage(..., { winners, ended: true })` for the new embeds/components; then, only when payload `congrats_message` is non-null, send it as a new plain-content message (not an embed) in the same channel/thread, mentioning the winners
- [x] 5.3 Confirm/update `CloseAndDrawStandardGiveawayOccurrenceAction`'s existing idempotency guard still applies cleanly (a retried call is still a no-op before any outbound action is created) - unchanged, verified via the existing "is idempotent" test still passing
- [x] 5.4 Pest tests: closing an occurrence with a banner/claim link/claim deadline/template configured produces an outbound action payload with all fields populated and correctly computed `claim_deadline_at`; closing one with none of those configured produces a payload with `congrats_message: null` and no banner field; closing with zero eligible entrants still enqueues the message-edit but with `congrats_message: null`

## 6. Spec alignment

- [x] 6.1 Re-read `openspec/specs/standard-giveaways/spec.md` and `openspec/specs/standard-giveaway-occurrences/spec.md` after implementation to confirm every scenario in this change's delta specs is actually exercised by the tests added above
