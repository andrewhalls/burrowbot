## Why

A standard giveaway's Discord post currently looks the same the moment it goes live as it does after it closes: the original message is never touched again, winners are announced (if at all) with a bare per-winner or inline-mentions embed, and nothing tells a winner how or where to claim their prize. Admins want a branded, two-image post (a reusable banner plus the actual prize image), a "Winners" field on that same message that fills in once the giveaway ends, and a separate, per-series-customizable message that tags winners with claim instructions and a claim-by deadline.

## What Changes

- Add four new series-level fields to `StandardGiveaway`: `banner_image_path`, `claim_link`, `claim_deadline_hours`, `congrats_message_template`. Each is optional; a series created before this change behaves as if none are set.
- The live occurrence post gains a second embed (a banner embed showing `banner_image_path`, when set) alongside the existing content embed, which itself gains a "Winners" field (shown as pending until the giveaway closes), a "Prize" field, and a footer showing the occurrence's ID.
- **BREAKING (behavioral)**: closing/drawing a `StandardGiveawayOccurrence` now edits the original live-post message in place - filling in the "Winners" field and marking it ended - instead of leaving that message untouched. Existing automation or admins relying on the original post staying static after closing will see it change.
- Closing also posts a new, separate Discord message congratulating the drawn winners, built from that series' `congrats_message_template` (a mail-merge-style template with placeholders for winner mentions, prize name, claim link, and a claim-by deadline computed from `claim_deadline_hours`). If a series has no template configured, this message is skipped.
- `claim_deadline_hours` and `claim_link` are display-only: no claim enforcement, expiry, automated re-draw, or real ticket-system integration is included.
- Out of scope: popup Giveaways (`Giveaway` model) and Events keep their current close behavior unchanged.

## Capabilities

### New Capabilities

(none - this extends two existing capabilities)

### Modified Capabilities

- `standard-giveaways`: adds the four new optional series-level configuration fields (banner image, claim link, claim deadline, congrats message template), editable via the existing Create/Edit series forms.
- `standard-giveaway-occurrences`: changes what the live post contains (banner embed, Winners/Prize fields, footer ID), and changes what happens when an occurrence closes (edits the original message, posts a new congrats/claim message).

## Impact

- **Multi-guild scoping**: all four new fields live on `StandardGiveaway`, which is already guild-scoped via its existing `guild_id`; no cross-guild exposure risk since nothing here introduces a new query path that could leak across guilds.
- **Affected code**: `app/Models/StandardGiveaway.php` (new columns + fillable), a new migration, `app/Actions/StandardGiveaways/CreateStandardGiveawayAction.php` and `UpdateStandardGiveawayAction.php`, the Create/Edit Standard Giveaway Livewire components and views, `app/Console/Commands/GenerateStandardGiveawayOccurrences.php` (snapshot new fields onto each occurrence, matching the existing `image_path`/`description` pattern), `app/Console/Commands/PostDueStandardGiveawayOccurrences.php`, `app/Actions/StandardGiveaways/CloseAndDrawStandardGiveawayOccurrenceAction.php`, `app/Models/DiscordOutboundAction.php` (payload/type changes), and the bot's `bot/src/standardGiveawayOccurrenceMessage.js` / `bot/src/discordAdapter.js` (new embed-building and message-edit logic).
- **No impact** on the legacy `Giveaway` (popup) model, `Event`/`EventOccurrence`, or their bot-side message handling.
