## Context

See proposal.md - Why. Relevant current state:

- `Event` and `StandardGiveaway` both have `status` (active/paused/cancelled), transitioned freely by `UpdateEventStatusAction`/`UpdateStandardGiveawayStatusAction` (`$event->update(['status' => $status])`, no transition-graph validation).
- `EventIndex::render()` (`app/Livewire/Events/EventIndex.php:131-153`) queries `$this->guild->events()->...->get()` with no filter at all. `EventIndex` already has a `setStatus(int $eventId, string $status, ...)` method reused by every status-change button, and `resources/views/livewire/events/event-index.blade.php` renders per-tile Activate/Pause/Cancel buttons directly in the list grid.
- `StandardGiveawayIndex` has the identical unfiltered list query and the identical per-tile Activate/Pause/Cancel buttons (`standard-giveaway-index.blade.php:58-68`, same `setStatus` pattern). Only Edit/Delete moved to the header row for this capability per `improve-list-detail-header-and-create-placement` (this session) - status-transition buttons, which Archive/Unarchive are closest to, stayed per-tile in both capabilities. Archive/Unarchive therefore belongs alongside Activate/Pause/Cancel as a per-tile button in both index views, not in either header row.
- Neither of the project's design rules about bot communication, random item assignment, giveaway expiry enforcement, or waitlist promotion apply to this change - it touches only the two admin list-index screens and their series models.

## Goals / Non-Goals

**Goals:**
- One consistent archive/unarchive + hidden-by-default-with-toggle pattern, applied to both `Event` and `StandardGiveaway` independently.
- Keep each capability's existing UI convention (Events: per-tile buttons; Standard Giveaways: header-row actions) rather than forcing one to match the other.

**Non-Goals:**
- No shared trait/base class across the two models for this - proposal.md already calls out that this codebase keeps Events and Standard Giveaways as parallel, separately-implemented capabilities rather than introducing a shared abstraction.
- No pagination or search added to either index as part of this change, even though an unfiltered `->get()` will eventually want both - out of scope, tracked as a pre-existing gap, not something this change is responsible for fixing.

## Decisions

**1. `archived_at` as a plain nullable timestamp column, not a new status value and not `SoftDeletes`.**
A fourth status value (`archived`) would force every place that currently branches on exactly three statuses (status pills, `UpdateStatusAction`'s `VALID_STATUSES` list, any `where('status', STATUS_ACTIVE)` filter) to learn about a new one, and would conflate "why did this stop running" (cancelled) with "should this show up in my list" (archived) into one field. Eloquent's `SoftDeletes` was considered and rejected: it globally scopes every query on the model unless explicitly `withTrashed()`, which would silently affect occurrence generation/posting commands and any other current or future query against `Event`/`StandardGiveaway` - far more blast radius than the two index screens this change is actually about. A plain nullable column read only by the two list queries this change touches is the smallest correct mechanism.

**2. Archive is its own action, not a mode of `UpdateStatusAction`.**
Archiving does two things atomically (force status to `cancelled` + stamp `archived_at`), which doesn't fit `UpdateStatusAction`'s single-responsibility "set exactly this status" contract without adding a side effect its name doesn't suggest. `ArchiveEventAction`/`ArchiveStandardGiveawayAction` (and their `Unarchive*` counterparts) are small, single-purpose, and directly testable, matching the existing `Delete*Action`/`UpdateStatusAction` granularity already used for both capabilities.

**3. Unarchiving does not restore prior status.**
Once archived, a series is `cancelled` for good unless the admin separately reactivates it - unarchiving only ever clears `archived_at`. Storing and restoring a "status before archiving" was considered and rejected as unnecessary complexity: the admin already has a one-click Activate button available the moment the series is unarchived and visible again, and silently reviving a paused/active series's exact prior state on unarchive would be a surprising side effect for an action whose only stated purpose is visibility.

**4. `showArchived` is a plain Livewire boolean property on each Index component, not a persisted preference.**
Resets to `false` (hidden) on every fresh page load, matching "hidden by default" from proposal.md. No need for a session/database-backed preference for a per-visit convenience toggle.

## Risks / Trade-offs

- [Archiving is a destructive-feeling action for an active/paused series since it force-cancels it in the same click, with no separate confirmation step] → Mitigated the same way the existing Cancel button already is in this codebase (no confirmation dialog anywhere in this app currently) - consistent with existing UX, not a new gap introduced by this change. If a confirmation pattern is added generally later, Archive should get it too.
- [The two index queries remain unpaginated `->get()` calls; adding a filter doesn't fix that pre-existing scalability gap] → Out of scope per Non-Goals; noted for future work, not blocking this change.

## Migration Plan

- One migration adds nullable `archived_at` to both `events` and `standard_giveaways`; existing rows get `null` (not archived), so every current series remains visible exactly as before deploying this change.
- No backfill, no rollback complexity beyond the standard migration `down()`.
