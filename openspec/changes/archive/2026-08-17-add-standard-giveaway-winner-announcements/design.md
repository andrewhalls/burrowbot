## Context

See proposal.md - Why. Relevant current state:

- `StandardGiveawayOccurrence` already snapshots series-level values (`title`, `description`, `image_path`, `prize_item_ids`, `required_role_ids`, etc.) at generation time - `GenerateStandardGiveawayOccurrences.php` and `CreateStandardGiveawayAction.php` (one-off branch) are the two write paths.
- `CloseAndDrawStandardGiveawayOccurrenceAction` already draws winners and enqueues a single `DiscordOutboundAction` of type `TYPE_ANNOUNCE_STANDARD_GIVEAWAY_WINNERS`, whose payload already includes `channel_id`, `discord_thread_id`, `discord_message_id`, and the drawn `winners` array.
- `bot/src/standardGiveawayOccurrenceMessage.js` builds the live post's embed/button payload from occurrence fields; `bot/src/discordAdapter.js`'s `announceStandardGiveawayWinners` currently only sends new embeds, never touches `discord_message_id`.
- Per the existing architecture, Laravel is the sole source of business-logic decisions; the bot process only executes Discord REST calls it's told to make (see `app/Http/Controllers/Internal/OutboundActionController.php` / `bot/src/outboundPoller.js`). This change preserves that split: all data resolution (winner text, claim deadline, template substitution) happens in Laravel before the outbound action is enqueued; the bot only builds and sends/edits embeds from already-resolved payload fields.

## Goals / Non-Goals

**Goals:**
- Add banner image + claim/congrats configuration to the `StandardGiveaway` series, snapshotted per occurrence like existing fields.
- Make the live post carry a pending "Winners" section and (optionally) a banner embed.
- On close, edit the original post to show final winners, and separately send a templated congrats/claim message.

**Non-Goals:**
- No claim-deadline enforcement, expiry job, or automated re-draw (proposal.md).
- No template engine - placeholder substitution is plain string replacement, not a general-purpose templating language.
- No change to the legacy `Giveaway` (popup) close flow (`closeGiveawayMessage`) or to Events.

## Decisions

**1. New columns on both `standard_giveaways` and `standard_giveaway_occurrences`.**
`banner_image_path` (nullable string), `claim_link` (nullable string), `claim_deadline_hours` (nullable unsigned smallint), `congrats_message_template` (nullable text) are added to both tables, following the exact pattern `image_path`/`description` already use: the series holds the current/editable value, and each occurrence snapshots it at generation time (`GenerateStandardGiveawayOccurrences.php`) or at creation time for one-off giveaways (`CreateStandardGiveawayAction.php`). This is what makes "editing a series only affects future occurrences" (already a spec requirement) hold for the new fields without extra code - the existing snapshot mechanism just gets four more fields to copy. A `banner_image_url` accessor is added alongside the existing `image_url` accessor.

**2. Reuse `TYPE_ANNOUNCE_STANDARD_GIVEAWAY_WINNERS` for both the message-edit and the new congrats message, as one outbound action.**
Both Discord operations happen for the same trigger (an occurrence closing) and the bot already receives `discord_message_id` in this action's payload. Alternative considered: two separate outbound action types (one to edit, one to announce). Rejected because it doubles bookkeeping for no benefit here - the two operations aren't independently retryable in a way the caller cares about (nothing today acks/fails outbound actions at sub-action granularity), and keeping them as one action means the executor can run them in a fixed order (edit, then send) within a single handler.

**3. Laravel resolves everything the bot needs verbatim; the bot does no computation.**
`CloseAndDrawStandardGiveawayOccurrenceAction` is extended to put into the payload: the same content fields `buildStandardGiveawayOccurrenceMessage` already needs (title, description, image_url, banner_image_url, requires_booster, required_role_ids, prize_item_names, ends_at) so the bot can rebuild the full embed set from scratch rather than trying to read-modify-write Discord's returned message object; plus an absolute `claim_deadline_at` ISO8601 timestamp (computed as `now()->addHours($claim_deadline_hours)` at close time, not left for the bot to compute from a relative hour count - avoids clock skew and any delay between enqueue and the bot picking it up); plus `congrats_message` - the fully placeholder-substituted string, or `null` if the occurrence has no `congrats_message_template`, meaning the announcement step is skipped entirely (per the "skipped when no template" requirement).

**4. Placeholder substitution is a fixed set of four tokens, plain `str_replace`.**
`{winners}` (comma-joined `<@id>` mentions), `{prize}` (comma-joined prize item names), `{claim_link}`, `{claim_deadline}` (rendered as a Discord relative timestamp `<t:...:R>` from `claim_deadline_at`). Unrecognized tokens or a template with none of these are left as-is - matches the spec's "accept any subset, including none" requirement. This lives in a new small PHP helper (e.g. `App\Support\StandardGiveaways\RenderCongratsMessage`), unit-testable without touching Discord or the DB.

**5. `buildStandardGiveawayOccurrenceMessage` gains an optional `winners`/`ended` mode instead of a second function.**
Called with no `winners` argument (the live-post path, unchanged call site in `PostDueStandardGiveawayOccurrences.php`), it renders the "Winners" field as a pending placeholder and includes the Enter button. Called with a `winners` array (possibly empty) and `ended: true` (the close path), it renders the Winners field from that array ("No winners this time." when empty), drops the Enter button, and adds a footer showing the occurrence ID. A banner embed is prepended in both modes whenever `banner_image_url` is present, and omitted otherwise - existing series without a banner see no change. This keeps one source of truth for the embed's shape instead of two builder functions drifting apart.

**6. The original message is always rebuilt from payload data, never patched from Discord's returned embed.**
`message.edit({ embeds: [...] })` requires the full embeds array anyway, and re-fetching `message.embeds[0]` to preserve the banner risks carrying forward stale data if the series' banner changed between post and close (which can't happen mid-occurrence today since occurrences snapshot at generation time, but relying on that invariant here would be fragile). Rebuilding from the payload's own snapshot fields is simpler and matches Decision 3.

## Risks / Trade-offs

- [The single outbound action performs two Discord operations (edit, then send); if the process crashes between them, a retry of the same action would harmlessly re-edit the original message but could re-send the congrats message] → Accepted as a known limitation of the existing outbound-action model, which already has no sub-action idempotency elsewhere (e.g. a retried `postStandardGiveawayMessage` could already double-post today). Not introducing new infrastructure to solve a pre-existing class of risk as part of this change.
- [Admins can type an unconfigured/garbage `claim_link` since it isn't validated against Discord or URL reachability] → Accepted per proposal.md's non-goals; it's operator-facing text, not a live integration.
- [Two embeds in one message/edit is unproven for `message.edit` specifically in this codebase, though already proven for `channel.send` via the existing per-winner embeds path] → Low risk; both operations accept the same `embeds` array shape in discord.js. Verified in implementation via a bot-side test/manual check before relying on it, no separate design spike needed.

## Migration Plan

- One migration adds the four nullable columns to `standard_giveaways` and `standard_giveaway_occurrences`; both default to `null`, so existing rows behave exactly as before (no banner embed, no congrats message sent, live post's Winners field still shows the pending placeholder introduced by this change).
- No backfill needed and no rollback complexity beyond the standard migration `down()`.
