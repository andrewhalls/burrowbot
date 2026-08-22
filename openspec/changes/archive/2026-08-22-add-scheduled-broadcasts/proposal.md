## Why

Staff repeatedly post the same kind of announcement by hand - a weekly "raid reset" reminder, a recurring rules-recap, a Friday "giveaways drop today" nudge - in a specific channel, on a schedule, with a couple of details (the date, which channel, the server name) filled in each time. There's no way to define that once and let Burrow post it automatically. Events and standard giveaways already solved "define a series, generate scheduled occurrences, post them to Discord" via the shared RRULE recurrence engine; a plain scheduled text broadcast is a simpler instance of the same pattern and should reuse it rather than inventing a new scheduling mechanism.

## What Changes

- Add **broadcasts**: a title, a message template (plain text with mail-merge-style placeholders - see below), a Discord channel, and either no recurrence (one-off) or a full custom recurrence rule (the same RFC 5545 RRULE engine built for Events), scoped to a guild, with the same active/paused/cancelled status and archive/unarchive lifecycle already established for events and standard giveaways.
- Add **broadcast occurrences**: the generated, schedulable instances of a broadcast - one for a one-off broadcast, one per recurrence for a recurring one - each snapshotted from its parent broadcast at generation time (same pattern as `event-occurrences`/`standard-giveaway-occurrences`), posted to Discord as a new plain message in the configured channel.
- Add **message template placeholders**: a fixed set of mail-merge fields staff can drop into a broadcast's message template - the target channel's mention, the guild's name, the post date/time, and (for recurring broadcasts) the next scheduled occurrence's date - resolved at the moment each occurrence is actually posted, not when it's generated or when the template is written.
- Extend the **discord-bot-gateway** contract: posting a broadcast message to a Discord channel and reporting back the resulting Discord message ID, alongside the existing giveaway/event/standard-giveaway posting behavior.
- Add a **"Broadcasts" dashboard sub-menu item** to the guild sidebar (`resources/views/components/dashboard-sidebar.blade.php`), following the same list-detail screen pattern as Events and Standard giveaways, so staff can create, edit, pause/cancel, archive, and inspect broadcast series and their occurrence history without leaving the dashboard shell.

## Capabilities

### New Capabilities
- `broadcasts`: the title/message-template/channel/recurrence definition of a broadcast series, its status lifecycle, and its dashboard screen (including the new sidebar entry).
- `broadcast-occurrences`: generating and posting individual scheduled instances of a broadcast, with placeholders resolved at post time.

### Modified Capabilities
- `discord-bot-gateway`: add posting a broadcast message, alongside the existing giveaway, event, and standard-giveaway posting behavior.

## Non-goals

- No per-recipient (per-member) mail merge or DMs - a broadcast posts one message to one channel; placeholders describe the broadcast/occurrence/guild, never an individual Discord member, and this is not a targeted-messaging or notification system.
- No thread posting mode - unlike Events and standard giveaways, a broadcast always posts as a new plain channel message (it's a one-way announcement, not something with per-occurrence discussion worth threading), so there's no posting-mode choice to make.
- No image/attachment support in v1 - message templates are plain text only; an image field could be added later following the same pattern Events and standard giveaways already use.
- No editing or deleting an occurrence's message once posted, and no "send now" override that skips the schedule - a broadcast occurrence is fire-and-forget once posted, same as an event or giveaway occurrence.
- No delivery confirmation/read receipts beyond the existing outbound-action ack/fail pattern already used for every other posted message type.
- No custom or user-defined placeholders - v1 ships a fixed, documented placeholder set (see `broadcast-occurrences` spec); a template using an unrecognized `{{...}}` token leaves it as literal text rather than erroring.
- No "end" or expiry concept for a posted broadcast occurrence - unlike a giveaway, a broadcast message doesn't close or time out; there is no analogous `ends_at`/closing job.

## Impact

- **Multi-guild scoping**: `broadcasts` and `broadcast_occurrences` are guild-scoped exactly like `events`/`event_occurrences` and `standard_giveaways`/`standard_giveaway_occurrences` - every query, policy, and Livewire component filters by `guild_id`, and the channel picker and dashboard list are guild-scoped via the existing `discord-channels` and `dashboard-list-detail-layout` capabilities. No new cross-guild exposure surface.
- New MySQL schema: `broadcasts`, `broadcast_occurrences`.
- Reuses without modification: the `simshaun/recurr` RRULE engine and occurrence-generation pattern from Events (`ExpandRecurrenceRule`, `BuildRecurrenceRule`, `ParseRecurrenceRule`, the recurrence picker UI), the `discord_outbound_actions` table (one new action type), the `discord-channels` searchable channel picker, the `browser-local-time`/timezone-aware-datetime handling already used for `recurrence_start_at`, the dashboard list-detail layout and archive/unarchive pattern from Events and standard giveaways, and creator attribution.
- Extends `openapi.yaml` with the new internal endpoint(s)/outbound action shape and extends the bot process with new posting handling, following the same patterns as the Events and standard-giveaways changes.
- New guild-scoped dashboard screen: create/manage broadcasts, plus a new "Broadcasts" entry in `resources/views/components/dashboard-sidebar.blade.php`'s `$links`/`$icons`/`$routeNameToActiveKey` maps.
