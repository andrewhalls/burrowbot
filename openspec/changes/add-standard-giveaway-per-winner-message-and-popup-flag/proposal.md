## Why

The per-winner mail-merge message built earlier this session landed on the wrong giveaway type - "normal giveaways" meant Standard Giveaways (the recurring, draw-at-close type), not Popup Giveaways (the instant-win-on-click type it was actually built on). Rather than throw that work away, this change adds the intended feature to Standard Giveaways as a second, independent mechanism alongside the existing batch congratulations message, and makes the already-shipped Popup Giveaway version optional per guild instead of always-on.

## What Changes

- Add a guild-level feature flag, configurable in Guild Settings, that gates whether the Popup Giveaway per-winner message feature is available for that guild. Defaults to enabled (matches current behavior for anyone who already configured it).
- The flag is enforced both in the UI (the winner-message fields/section are hidden on Create/Edit Giveaway and the dedicated winner-message form when disabled) and authoritatively server-side (the send-check in `JoinGiveawayAction` also requires the flag, not just the fields being configured) - so disabling it for a guild that already has fields set immediately stops the messages without requiring the admin to also clear those fields.
- Add a new, separate per-winner message mechanism to Standard Giveaways: an optional channel + mail-merge template pair (paired validation, same as the Popup Giveaway version), sent as one individual message per drawn winner when an occurrence closes - in addition to, not instead of, the existing single combined "congrats message" already sent to all winners together.
- Reuses the existing `{winner}`/`{prize}` renderer and the bot's generic plain-message-send adapter method from the Popup Giveaway work, since both are already capability-agnostic.

## Capabilities

### New Capabilities

(none - this extends four existing capabilities)

### Modified Capabilities

- `guild-management`: adds the new per-guild feature flag and its Guild Settings UI.
- `giveaway-lifecycle`: the Popup Giveaway winner-message configuration fields are only usable when the guild's flag is enabled.
- `giveaway-entry`: the per-winner message send is only attempted when the guild's flag is enabled, in addition to the existing "both fields configured" condition.
- `standard-giveaways`: adds the new, separate per-winner message channel/template fields.
- `standard-giveaway-occurrences`: adds the per-winner message send behavior on occurrence close, alongside the existing batch congrats message.

## Non-goals

- No change to the existing batch congrats message behavior on Standard Giveaways - it keeps working exactly as before; the new per-winner mechanism is purely additive.
- No removal of anything already built for Popup Giveaways - the feature stays, just becomes toggleable per guild.
- No global/cross-guild default change beyond the flag defaulting to enabled for continuity.
- No per-series override of the guild-level flag - it's a guild-wide switch, not configurable per giveaway.

## Impact

- **Multi-guild scoping**: the new flag lives on `Guild`, already the natural per-tenant scope; the new Standard Giveaway fields live on `StandardGiveaway`, already guild-scoped via `guild_id` - no cross-guild exposure risk in either case.
- **Affected code**: one migration adding a boolean to `guilds` and two nullable fields to `standard_giveaways`; `app/Models/Guild.php`, `app/Livewire/Guilds/GuildSettings.php` + its view; `app/Livewire/Giveaways/{CreateGiveaway,EditGiveaway,EditGiveawayWinnerMessage}.php` + views (flag-gating) and `app/Actions/Giveaways/JoinGiveawayAction.php` (server-side gate); `app/Models/StandardGiveaway.php`, `app/Livewire/StandardGiveaways/{CreateStandardGiveaway,EditStandardGiveaway}.php` + views, `app/Actions/StandardGiveaways/CloseAndDrawStandardGiveawayOccurrenceAction.php`, a new `DiscordOutboundAction` type, and `bot/src/outboundActionExecutor.js`'s switch statement (new case, existing adapter method).
- **No impact** on Events, Collection Themes, or the popup Giveaway's existing public/private win-result replies.
