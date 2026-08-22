## Context

See proposal.md - Why. Relevant current state:

- `Guild` (`app/Models/Guild.php`) has a boolean `is_active` cast already - the new flag follows the identical pattern (`protected function casts()` gains one more `'boolean'` entry).
- `GuildSettings` (`app/Livewire/Guilds/GuildSettings.php`, 44 lines) only configures `default_channel_id` today via `$this->authorize('manage', $guild)` in both `mount()` and `save()`.
- `JoinGiveawayAction::execute()` already checks `$locked->hasWinnerMessageConfigured()` right after creating the winning `GiveawayEntry` (from the archived `add-giveaway-per-winner-message` change) before enqueueing the outbound action - this is the single point the new flag check joins.
- `CloseAndDrawStandardGiveawayOccurrenceAction` already builds `$announcedWinners` (with `discord_user_id`, `item_name`, etc.) and separately renders+enqueues the combined `congrats_message` via `RenderCongratsMessage` - the new per-winner loop is independent, reading the same `$announcedWinners` array the existing code already has in scope.
- `App\Support\Giveaways\RenderWinnerMessage` (`__invoke(string $template, string $winnerDiscordUserId, string $prize): string`) has no dependency on any Giveaway-specific model - confirmed safe to call from Standard Giveaway code directly.

## Goals / Non-Goals

**Goals:**
- Make the popup giveaway feature optional per guild without touching its already-tested core logic (action, renderer, bot adapter) beyond adding one guard condition.
- Give Standard Giveaways an equivalent per-winner mechanism that composes cleanly with the existing batch congrats message rather than replacing it.

**Non-Goals:**
- No per-series override of the guild flag - it's guild-wide only (proposal.md Non-goals).
- No UI unification between the popup and standard giveaway winner-message sections - each capability keeps its own form section, consistent with this codebase's existing parallel-capability convention.

## Decisions

**1. New `guilds.popup_giveaway_winner_messages_enabled` boolean, default `true`.**
Defaulting to enabled means every existing guild - including the one that already configured the popup giveaway fields under the just-archived change - keeps working exactly as before this change ships. An admin who wants to turn it off does so explicitly afterward. The alternative (default `false`, opt-in) would silently break the feature for existing users the moment this migration runs - rejected.

**2. Flag enforced twice: UI (`CreateGiveaway`/`EditGiveaway`/`EditGiveawayWinnerMessage`) and authoritatively in `JoinGiveawayAction`.**
Hiding the fields in the UI when the flag is off is a convenience, not the real gate - a guild that already has both fields saved from before the flag was disabled must have message-sending actually stop, not just lose the ability to edit the fields. `JoinGiveawayAction`'s existing `hasWinnerMessageConfigured()` check becomes `hasWinnerMessageConfigured() && $locked->guild->popup_giveaway_winner_messages_enabled` (or equivalent) - one extra boolean read on an already-loaded relation, no new query pattern.

**3. Standard Giveaway per-winner fields are named distinctly from the existing congrats fields and stored as a wholly separate pair.**
`per_winner_message_channel_id`/`per_winner_message_template`, independent of `congrats_message_template`/`claim_link`/`claim_deadline_hours`. Considered layering this onto the existing congrats fields (e.g. a "send individually" checkbox) and rejected it: the two mechanisms have different failure/skip conditions already (the batch message keys off `congrats_message_template` alone; per-winner keys off its own pair), and proposal.md is explicit that they must be independently configurable, not a toggle on one shared set of fields.

**4. Per-winner sends live in `CloseAndDrawStandardGiveawayOccurrenceAction`, in a loop separate from the existing batch-message construction.**
Same trigger point (occurrence close, winners already drawn and in `$announcedWinners`), but a distinct `foreach` producing one `DiscordOutboundAction` per winner (`TYPE_ANNOUNCE_STANDARD_GIVEAWAY_WINNER`, singular) when both new fields are set - runs unconditionally alongside the existing single batch action, each independently gated on its own pair of fields being configured. Neither mechanism can suppress or interfere with the other.

**5. Reuse `App\Support\Giveaways\RenderWinnerMessage` and the bot's `announceGiveawayWinner` adapter method for Standard Giveaways too, rather than duplicating them.**
This is a deliberate, narrow exception to the "keep capabilities parallel and independent" convention the archived change's own design.md established for `RenderCongratsMessage` vs `RenderWinnerMessage`. That convention exists to avoid coupling two capabilities' domain models together; `RenderWinnerMessage` was already built with zero such coupling (plain strings in, plain string out), and the bot adapter method is equally generic (`channel_id` + `message` in, one Discord API call out) - duplicating either would be copying identical code for no behavioral or architectural benefit. The new Standard Giveaway outbound action type (`TYPE_ANNOUNCE_STANDARD_GIVEAWAY_WINNER`) is still its own distinct type for observability/debugging (so outbound-action logs and any future per-type metrics can tell popup and standard giveaway sends apart), but both types map to the same bot-side execution.

## Risks / Trade-offs

- [A guild that disables the flag after already configuring popup giveaway winner-message fields loses no data - fields stay saved, just inert] → Intentional (Decision 2); re-enabling the flag resumes sending with whatever is still configured, per the `guild-management` delta's "Guild admin re-enables the flag" scenario.
- [Standard Giveaways can now enqueue up to `winner_count` extra outbound actions per close, on top of the existing single batch one] → Accepted as the same characteristic already true of the batch message's own winner-count scaling and of the popup giveaway's per-entrant volume - the outbound queue is already designed for this pattern (see the archived change's own Risks section).

## Migration Plan

- One migration adds `popup_giveaway_winner_messages_enabled` (boolean, default `true`) to `guilds` and `per_winner_message_channel_id`/`per_winner_message_template` (nullable) to `standard_giveaways`. Existing guilds get the flag enabled (no behavior change); existing standard giveaways get both new fields `null` (no per-winner messages sent until explicitly configured).
- No backfill, no rollback complexity beyond the standard migration `down()`.
