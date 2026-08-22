## Why

Popup giveaways already announce every win publicly in the giveaway's own channel, but admins have no way to also send a customized, templated message per winner somewhere else (e.g. a staff-only "winners log" channel, or a channel formatted for hand-off to fulfillment) - today the only way to see who won what is the public announcement or the admin dashboard's entrant list.

## What Changes

- Add two new optional fields to a popup giveaway series: a destination Discord channel (via the existing channel-picker dropdown, distinct from the giveaway's own posting channel) and a mail-merge message template. Both must be set together - setting one without the other is rejected by validation.
- Placeholders: `{winner}` (the winning member's Discord mention) and `{prize}` (the won item's name). A template may use any subset of these, including neither.
- The moment a member wins (assigned an item on entry), if both fields are configured, the system sends one new Discord message - built from the template with placeholders substituted - to the configured channel. One message per winner, sent as soon as they win, not batched.
- This is purely additive: the existing public/private win-result replies (in the giveaway's own channel) are completely unchanged. This is a second, independent, optional notification.
- Unlike the giveaway's channel/collection theme/duration/description/image, the winner-message channel and template stay editable regardless of the giveaway's status (draft, active, or closed) - they don't affect anything already posted to Discord, only future win events.

## Assumptions to review before implementation

These weren't confirmed by direct question - flagging them here so they're easy to challenge:

1. **Per-winner timing**: this fires at the moment of each individual win (popup giveaways award instantly on entry, unlike Standard Giveaways which draw winners in a batch at close time), not as a batch summary at giveaway close.
2. **No claim link/deadline**: unlike the similar Standard Giveaway feature, this has no claim-link or claim-deadline placeholder - popup giveaway items are handed out manually by staff, there's no self-serve claim concept for this capability.
3. **Editable anytime**: the winner-message channel/template can be set or changed even after the giveaway has started or closed, since it only affects future win events and never touches the already-posted Discord message.

## Capabilities

### New Capabilities

(none - this extends two existing capabilities)

### Modified Capabilities

- `giveaway-lifecycle`: adds the two new optional series-level fields and their editability rule.
- `giveaway-entry`: adds the per-winner templated message sent on a new win, alongside the existing public/private result replies.

## Non-goals

- No change to Standard Giveaways or Events - scoped entirely to the popup Giveaway capability.
- No claim-link/claim-deadline concept - out of scope for this capability.
- No retroactive messages for entries that already exist before this feature is configured - only new wins after both fields are set.
- No change to the existing public/private win-result replies (`giveaway-entry`'s "Entrant sees their result") - this is a wholly separate, additional message.

## Impact

- **Multi-guild scoping**: the two new fields live on `Giveaway`, already guild-scoped via `guild_id`; the channel-picker reuses the same guild-scoped channel list every other channel picker in the app already uses - no cross-guild exposure risk.
- **Affected code**: one migration adding `winner_message_channel_id`/`winner_message_template` to `giveaways`; `app/Models/Giveaway.php`; `app/Actions/Giveaways/CreateGiveawayAction.php` and `UpdateGiveawayAction.php` (or wherever giveaway create/edit persistence lives); the Create/Edit Giveaway Livewire components and views (new channel-picker + template textarea, paired validation); `app/Actions/Giveaways/JoinGiveawayAction.php` (or the win-handling code path) to enqueue the new outbound action; a new `App\Support\Giveaways\RenderWinnerMessage`; a new `DiscordOutboundAction` type; a small new bot-side adapter method to send the plain message.
- **No impact** on Standard Giveaways, Events, or the existing public/private win-result reply path (`bot/src/joinInteractionReply.js` unchanged).
