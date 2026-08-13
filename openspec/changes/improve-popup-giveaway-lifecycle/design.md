## Context

See proposal.md - Why. Relevant existing pieces this design builds on:

- `Giveaway` already has `STATUS_DRAFT`/`STATUS_ACTIVE`/`STATUS_CLOSED` constants and `starts_at`/`ends_at` columns (`database/migrations/2026_08_11_194200_create_giveaways_table.php`) - `starts_at` has existed since v1 but nothing has ever set it, since nothing ever starts a giveaway.
- `DiscordOutboundAction::TYPE_POST_GIVEAWAY_MESSAGE` and the bot's `postGiveawayMessage` handler already exist and are unchanged (`discord-bot-gateway` - "Posting a giveaway message") - starting a giveaway only needs to enqueue one of these with the existing payload shape (`channel_id`, `collection_theme_name`, `ends_at`), exactly as `CreateGiveawayAction` would have needed to from day one.
- `giveaways:close-expired` (`app/Console/Commands/CloseExpiredGiveaways.php`) already closes `active` giveaways past `ends_at` - unaffected by this change, it only ever sees giveaways that are now actually reachable via `active` status for the first time.
- `App\Actions\StandardGiveaways\CloseAndDrawStandardGiveawayOccurrenceAction` establishes this codebase's pattern for a scheduled-process-safe state transition: `DB::transaction` + `lockForUpdate` + re-check the guard condition inside the lock before acting, so a manual action and a scheduled command can never race into a double-post.
- `EventIndex`/`StandardGiveawayIndex` establish the list-view pattern this change's `GiveawayIndex` follows: guild-scoped query, status shown, an inline action button, guild-nav-reachable route.

## Goals / Non-Goals

**Goals:**
- Make `giveaway-lifecycle`'s already-specified "Starting a giveaway" requirement actually work, for the first time.
- Add scheduled start without duplicating the posting/transition logic between the manual and scheduled paths.
- A list view so a guild's giveaways are discoverable at all.
- Rename to "Popup Giveaway" in the UI only, zero risk to anything already built on the existing identifiers.

**Non-Goals:**
- No recurrence (proposal.md - Non-goals) - `scheduled_start_at` is a single nullable timestamp, not an RRULE.
- No change to `giveaway-entry`'s join/assignment logic, or to `discord-bot-gateway`'s posting contract - both already correct and untouched.
- No rename of any code/schema/route/spec identifier - see proposal.md.

## Decisions

### Decision 1: One `StartGiveawayAction`, called by both the manual button and the scheduled command
```php
class StartGiveawayAction
{
    public function execute(Giveaway $giveaway): Giveaway
    {
        return DB::transaction(function () use ($giveaway) {
            $locked = Giveaway::query()->lockForUpdate()->findOrFail($giveaway->id);

            if (! $locked->isDraft()) {
                return $locked; // idempotent no-op - already started (or closed)
            }

            $startedAt = now();

            $locked->update([
                'status' => Giveaway::STATUS_ACTIVE,
                'starts_at' => $startedAt,
                'ends_at' => $startedAt->clone()->addMinutes($locked->duration_minutes),
            ]);

            DiscordOutboundAction::query()->create([
                'type' => DiscordOutboundAction::TYPE_POST_GIVEAWAY_MESSAGE,
                'giveaway_id' => $locked->id,
                'payload' => [
                    'channel_id' => $locked->channel_id,
                    'collection_theme_name' => $locked->collectionTheme->name,
                    'ends_at' => $locked->ends_at->toIso8601String(),
                ],
                'status' => DiscordOutboundAction::STATUS_PENDING,
            ]);

            return $locked;
        });
    }
}
```
The `lockForUpdate` + re-check-`isDraft()`-inside-the-lock pattern (same as `CloseAndDrawStandardGiveawayOccurrenceAction`) makes this safe to call from both a user-driven button click and a scheduled command without a race double-posting: whichever transaction acquires the row lock first wins, and the second sees `status` already `active` and no-ops.

**Alternative considered**: separate "start now" and "start scheduled" code paths. Rejected - they do the exact same thing (post + transition + set timestamps); the only difference is what triggers the call, which belongs in the caller (a Livewire action vs. a console command), not duplicated business logic.

### Decision 2: The scheduled command filters on `scheduled_start_at <= now()` explicitly - not a blind "post everything pending" sweep
```php
Giveaway::query()
    ->where('status', Giveaway::STATUS_DRAFT)
    ->whereNotNull('scheduled_start_at')
    ->where('scheduled_start_at', '<=', now())
    ->get();
```
New command `giveaways:post-due` (registered alongside `giveaways:close-expired` in `routes/console.php`, `everyMinute()`), iterating this query and calling `StartGiveawayAction` for each.

**Note**: `standard-giveaways:post-due-occurrences` (the closest existing precedent) posts every `scheduled`-status occurrence with no time comparison, relying entirely on `standard-giveaways:generate-occurrences`'s 30-day-ahead generation window to have not created the row yet - which means an occurrence generated within that window but not yet due would be posted immediately, before its actual `scheduled_post_at`. That looks like a latent bug in the already-shipped Standard Giveaways feature, not a pattern to copy. This design's explicit `scheduled_start_at <= now()` filter avoids replicating it for pop-up giveaways. Fixing the existing Standard Giveaways command is out of scope for this change - flagged here for a possible future fix, not actioned.

**Alternative considered**: mirror `standard-giveaways:post-due-occurrences` exactly (no time filter). Rejected per the above - it would start a giveaway as soon as it's created if scheduling were implemented the same way, which directly contradicts "scheduled for the future."

### Decision 3: `GiveawayIndex` follows the exact `EventIndex`/`StandardGiveawayIndex` shape
Guild-scoped query (`$guild->giveaways()`), one row per giveaway showing status and `withCount('entries')`, a "Start" button on `draft` rows calling `StartGiveawayAction` (guarded by `GiveawayPolicy::manage`), linking each row into the existing `GiveawayDashboard` (`guilds.giveaways.show`) for entrant management. New route `guilds.giveaways.index` at `/guilds/{guild}/giveaways`. `dashboard-sidebar.blade.php`'s "Giveaways" link target changes from `guilds.giveaways.create` to `guilds.giveaways.index`; the create form remains reachable from a "+ New giveaway" control on the index page, matching how `EventIndex`/`StandardGiveawayIndex` toggle their own create forms inline.

### Decision 4: "Popup Giveaway" is a view-layer label only
Every `Giveaway`/`giveaway_id`/`giveaways.*` route name/`GiveawayPolicy`/`giveaway-lifecycle` (etc.) identifier is untouched. Only Blade copy changes: page headings, button text ("Create popup giveaway", "Start popup giveaway"), and the sidebar link label ("Popup Giveaways"). No renaming pass over PHP/JS identifiers.

## Risks / Trade-offs

- **[Risk]** A guild admin could set `scheduled_start_at` in the past by mistake. → **Mitigation**: validate `scheduled_start_at` is strictly in the future at creation time (same validation style as every other date input in this app); if somehow still in the past by the time the scheduled command runs (clock skew, a very short window), it's still `<= now()` and starts immediately on the next minute's run - never silently stuck.
- **[Risk]** `StartGiveawayAction`'s row lock adds contention if an admin mashes "Start" repeatedly. → **Mitigation**: negligible - identical trade-off already accepted by every other `lockForUpdate` transition in this codebase (giveaway entry, standard giveaway closing), and a giveaway is started once, not a hot path.

## Migration Plan

One migration: nullable `scheduled_start_at` timestamp on `giveaways`. No backfill needed (existing giveaways have none). Safe to deploy and roll back like any other additive migration - `deploy.sh` already runs `migrate --force`.
