## Context

See proposal.md - Why. Relevant existing pieces this design builds on:

- `UpdateGiveawayDraftAction`, `UpdateStandardGiveawayAction`, `UpdateEventAction` already exist and encode each domain's editability rule exactly (draft-only vs. anytime-but-future-occurrences-only) - none of that business logic changes here, only the missing UI and (for Standard Giveaway) the missing prize-item/required-role re-sync.
- `add-giveaway-description-and-image` established this app's local-disk image pattern (`image_path` column + `image_url` accessor + delete-old-file-on-replace, revised to only delete once no occurrence still references the old path) and its `resolvedTimezone()`/`SearchesDiscordRoles` traits - Events reuse both wholesale.
- `CreateGiveaway`, `CreateStandardGiveaway`, `CreateEvent` are the field/validation/picker reference each Edit form mirrors.
- The list-detail panel pattern (`improve-dashboard-list-detail-layout`) already gives each domain a detail panel to put an "Edit" toggle in.

## Goals / Non-Goals

**Goals:**
- Every field the Create form accepts is also editable, per the user's explicit "full parity" choice.
- Editing follows exactly the rules the existing Update actions already enforce - no new business rules invented.
- Events reach full image-support parity with Popup/Standard Giveaway (upload, display, Discord post, per-occurrence snapshot).

**Non-Goals:**
- No new business rules around *when* something is editable - that's already decided by the existing Actions.
- No shared base class/trait unifying the three new Edit components' `save()` logic - each domain's save shape differs enough (draft-only single model vs. series-with-relations-to-resync) that forcing a shared abstraction would fight the differences more than it would save, consistent with this codebase's preference for explicit per-domain Actions over one generalized mega-abstraction.

## Decisions

### Decision 1: Each Edit component is a new, parallel sibling to its Create component - not inherited from it, not a shared base
`EditGiveaway`, `EditStandardGiveaway`, `EditEvent` are new Livewire components mirroring their Create counterpart's public properties, validation rules, and Blade markup (channel picker, role picker, prize-item search, recurrence builder, image upload) almost line-for-line, but `mount()` pre-fills from an existing model instead of defaulting empty, and `save()` calls the Update action instead of the Create action. They `use` the same traits their Create counterparts already use (`ResolvesBrowserTimezone`, `SearchesDiscordRoles`) for identical behavior.

**Alternative considered**: extract a shared trait/base class holding the common fields and validation, with Create/Edit each providing only the differing `mount()`/`save()`. Rejected - the actual overlap is almost entirely in Blade markup (which can't be shared via a PHP trait anyway) and validation *rules* (a small array, trivial to duplicate exactly), while the two components' state-initialization and persistence logic are different enough in shape (Edit needs to load existing prize-item-ID/required-role-ID selections into `SearchesDiscordRoles`'/the prize-item picker's expected state, Create starts empty) that a forced shared base would need as much conditional logic as it saves.

### Decision 2: Where the "Edit" toggle lives in each detail panel
- **Popup Giveaway**: `GiveawayDashboard` gains an "Edit" button next to "Start popup giveaway" (both only shown while `$giveaway->isDraft()`, matching `UpdateGiveawayDraftAction`'s own rule), toggling `<livewire:giveaways.edit-giveaway>` in place of the entrant table while active.
- **Standard Giveaway**: `StandardGiveawayIndex`'s detail panel is occurrence-scoped (`OccurrenceDashboard`), but editing is series-scoped - an "Edit series" toggle sits above the occurrence dashboard (visible whenever a series is selected, independent of which occurrence is shown), swapping in `<livewire:standard-giveaways.edit-standard-giveaway>`.
- **Event**: the existing read-only summary partial already is series-scoped, so "Edit" toggles between that summary and `<livewire:events.edit-event>` directly, exactly like the Standard Giveaway case but without the occurrence-vs-series panel-content split (Events' occurrence view - the roster - is reached via a separate nested selection already, per `improve-dashboard-list-detail-layout`).

**Alternative considered**: a single generic `<x-edit-toggle>` wrapper. Rejected for the same reason as Decision 1 - three different "what am I toggling into" call sites with genuinely different surrounding layout, not worth a generic abstraction over three explicit `@if ($editing) ... @else ... @endif` blocks.

### Decision 3: `UpdateStandardGiveawayAction` re-syncs prize items and required roles via full delete-and-recreate, matching the full-list-sync pattern already used for Discord channel/role sync
When prize item IDs or required role IDs are present in the update attributes, existing `StandardGiveawayPrizeItem`/`StandardGiveawayRequiredRole` rows for that giveaway are deleted and recreated from the new selection - the same "replace with exactly this set" idempotent shape `SyncGuildChannelsAction`/`SyncGuildRolesAction` already established, rather than diffing add/remove individually.

**Alternative considered**: diff and apply only the add/remove delta. Rejected - full replace is simpler, already proven correct elsewhere in this codebase, and prize-item/role counts per giveaway are small (not a performance concern).

### Decision 4: Event images follow the exact same pattern as Standard Giveaway images, field-for-field
`events`/`event_occurrences` gain a nullable `image_path` column each; `Event`/`EventOccurrence` gain the same `image_url` accessor shape; `CreateEventAction`/`UpdateEventAction` handle it exactly like `CreateStandardGiveawayAction`/`UpdateStandardGiveawayAction` (including `UpdateEventAction`'s old-file-delete only firing once no `EventOccurrence` still references the old path - the same fix already made for Standard Giveaway); `GenerateEventOccurrences`/`PostDueEventOccurrences` snapshot/forward it exactly like their Standard Giveaway equivalents; `bot/src/eventOccurrenceMessage.js` gains `.setImage(image_url)` when present, mirroring `standardGiveawayOccurrenceMessage.js`.

No new decisions needed here - this is a direct, field-for-field port of an already-validated pattern, not new design.

## Risks / Trade-offs

- **[Risk]** Three new large-ish Livewire components (roughly as large as their Create counterparts) increase the surface area to keep in sync if a Create form's fields change later. → **Mitigation**: accepted - Decision 1 already rejected a shared abstraction as not worth the conditional complexity; the fields themselves change rarely enough (this session's own history shows Create-form field changes are infrequent events, not routine) that duplication cost is low relative to the complexity a forced abstraction would add.
- **[Risk]** Standard Giveaway's "Edit series" toggle sits in the same detail panel as the occurrence dashboard - a guild admin could be confused about whether editing affects the currently-shown occurrence. → **Mitigation**: the edit form only ever shows in place of (not alongside) the occurrence dashboard, and its own copy states plainly that changes apply to future occurrences only, matching the already-existing spec language.

## Migration Plan

Two additive migrations: nullable `image_path` on `events`, nullable `image_path` on `event_occurrences` - identical shape to the existing Standard Giveaway migrations, no backfill (existing events/occurrences simply have none, indistinguishable from today).
