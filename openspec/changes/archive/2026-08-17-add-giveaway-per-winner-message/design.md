## Context

See proposal.md - Why. Relevant current state:

- `JoinGiveawayAction::execute()` (`app/Actions/Giveaways/JoinGiveawayAction.php:27-74`) is the single place a win happens: inside its `DB::transaction`, after creating the `GiveawayEntry` (line 66-70) and before returning `JoinResult::won(...)` (line 72), is exactly where a win is known synchronously and authoritatively - the natural hook point for this feature.
- Random item assignment fairness (`AssignRandomItem`) and authoritative server-side expiry enforcement (`Giveaway::hasExpired()`, checked before any entry is created) are pre-existing, untouched by this change - this feature only adds a side effect to the already-successful win path, it doesn't touch entry validity or item assignment at all.
- `DiscordOutboundAction` already has a `giveaway_id` FK (`app/Models/DiscordOutboundAction.php:38`, used today by `TYPE_POST_GIVEAWAY_MESSAGE`/`TYPE_CLOSE_GIVEAWAY_MESSAGE`) - no new FK column is needed for this feature's outbound action type.
- `UpdateGiveawayDraftAction` (`app/Actions/Giveaways/UpdateGiveawayDraftAction.php`) is hard-gated to `draft` status only (`if (! $giveaway->isDraft()) throw ...`), enforcing "Giveaway configuration immutability once started". The dashboard's Edit button itself is only shown for draft giveaways. Since the winner-message fields must stay editable at every status (proposal.md), they can't be edited through this action or its form.
- Laravel-bot communication contract (unaffected by this change, included per project rules): Laravel is the sole source of business logic and message content; the bot polls `GET /internal/outbound-actions` for `pending` rows and executes/acks them via `POST .../ack` - this feature adds one more outbound action type to that same existing contract, nothing new architecturally.

## Goals / Non-Goals

**Goals:**
- Fire exactly once per real win, synchronously alongside the existing win path, with zero risk of also firing for a duplicate/expired join.
- Keep the winner-message config editable at any giveaway status without touching the existing immutability rule for the other fields.

**Non-Goals:**
- No retry/backfill for entries that existed before the fields were configured (proposal.md).
- No embed, image, or rich formatting for the per-winner message - plain text only, matching how simple this notification is meant to be (a log line, not a public announcement).

## Decisions

**1. Enqueue the outbound action inside `JoinGiveawayAction`'s existing transaction, not via an event/listener.**
The win is already known, locked, and about to be returned right there (`JoinGiveawayAction.php:66-72`). Adding `if ($locked->winner_message_channel_id && $locked->winner_message_template) { DiscordOutboundAction::create(...) }` immediately after entry creation keeps the whole win side-effect visible in one place, matches how `CloseAndDrawStandardGiveawayOccurrenceAction` enqueues its outbound action inline rather than via a domain event, and guarantees the message can never be enqueued for a duplicate/expired join since those paths `return` before entry creation.

**2. New `App\Support\Giveaways\RenderWinnerMessage`, not a reuse or extension of `RenderCongratsMessage`.**
`RenderCongratsMessage` is Standard-Giveaway-shaped: plural `{winners}`, plus `{claim_link}`/`{claim_deadline}` that don't exist for popup giveaways. A second small renderer with placeholders `{winner}` (single mention) and `{prize}` (item name) - same plain `strtr`, same "unrecognized token or template with none of these placeholders is left as-is" behavior - is simpler and clearer than parameterizing/reusing a renderer built for a different shape of data. Mirrors the project's existing pattern of small, single-purpose, independently-testable support classes.

**3. New `App\Actions\Giveaways\UpdateGiveawayWinnerMessageAction`, separate from `UpdateGiveawayDraftAction`.**
Keeps the hard draft-only gate on the other fields completely untouched - this new action only ever writes `winner_message_channel_id`/`winner_message_template`, with no status check at all, so there's no risk of accidentally loosening (or having to special-case) the existing immutability rule.

**4. A new, always-visible small edit component (`EditGiveawayWinnerMessage`), not an extension of the existing draft-only `EditGiveaway` form.**
`EditGiveaway`'s own form is only reachable when the giveaway is a draft (the dashboard's Edit button is itself draft-gated). Since these two fields need to stay editable at every status, they need a separate entry point in `GiveawayDashboard`/`GiveawayIndex` that's visible regardless of status - mirrors the project's existing pattern of small, narrowly-scoped edit components for a concern that doesn't fit the main edit form's lifecycle (`EditStandardGiveawayOccurrence` from an earlier change is the same pattern: a small dedicated component for editing something with different editability rules than its parent). The two fields are also included in the main `CreateGiveaway` and `EditGiveaway` (draft) forms for the common case of setting them up before the giveaway ever starts, so admins aren't forced to always use the separate small form.

**5. Paired validation lives in each Livewire component, not the action.**
`UpdateGiveawayWinnerMessageAction` trusts its caller (consistent with other actions in this codebase, e.g. `UpdateStandardGiveawayOccurrenceAction` trusts its caller for field-level validation) - the "both or neither" rule is enforced once in each Livewire component's `validate()` call (`CreateGiveaway`, `EditGiveaway`, and the new `EditGiveawayWinnerMessage`), the same way `selectedPrizeItemIds` non-empty checks already live in the Livewire layer rather than the action layer throughout this codebase.

## Risks / Trade-offs

- [A very active giveaway could enqueue many outbound actions in a short window if lots of members join quickly] → Accepted as the same characteristic already true of the existing per-entrant public announcement and of `TYPE_POST_STANDARD_GIVEAWAY_*` actions - the outbound queue and bot poller are already designed for this volume pattern, nothing new here.
- [Admin edits the winner-message template mid-giveaway while entries are actively coming in] → Accepted: each win reads the giveaway's current field values at the moment it happens (no snapshot/versioning), so an in-flight edit naturally applies to the very next win onward - consistent with "editable regardless of status" being the whole point of Decision 3/4.

## Migration Plan

- One migration adds nullable `winner_message_channel_id` (string) and `winner_message_template` (text) to `giveaways`; existing rows get `null` for both, so no existing giveaway starts sending anything until an admin explicitly configures both fields.
- No backfill, no rollback complexity beyond the standard migration `down()`.
