## Why

The backend already fully supports editing (`UpdateGiveawayDraftAction`, `UpdateStandardGiveawayAction`, `UpdateEventAction` all exist, and `standard-giveaways`/`events` already document "editing only affects future occurrences" as an agreed requirement) but no UI was ever wired to any of them - admins currently cannot fix a typo, change a channel, or add an image to anything after creating it. Separately, Events never gained image support at all when Popup Giveaways and Standard Giveaways did.

## What Changes

- Popup Giveaway, Standard Giveaway, and Event each gain a full Edit form with the same field coverage as their Create form (including recurrence rule, prize item selection, and required-role selection where applicable), reachable from the item's detail panel.
- Events gain an image field (upload at creation or via edit), shown on the Discord post the same way Popup/Standard Giveaway images already are.
- `UpdateStandardGiveawayAction` gains the ability to re-sync prize items and required roles (previously scalar-fields-only), since full edit parity requires it.

## Capabilities

### New Capabilities
None.

### Modified Capabilities
- `giveaway-lifecycle`: adds an explicit "Editing a draft giveaway" requirement (the underlying rule - draft-only, immutable once started - already existed via "Giveaway configuration immutability once started," this makes the allowed side of that rule explicit and UI-backed).
- `events`: "Editing an event series only affects future occurrences" gains an image field; "Event creation" gains an optional image field.

Note: `standard-giveaways`' "Editing a standard giveaway series only affects future occurrences" already documents prize items and restrictions as editable - that promise was made when images were added but never implemented against real re-sync logic (`UpdateStandardGiveawayAction` only ever touched scalar fields). No spec delta needed there; this change closes that pre-existing implementation gap.

## Impact

- **Affected code**: three new Livewire components (`EditGiveaway`, `EditStandardGiveaway`, `EditEvent`), each mirroring its Create counterpart's fields/validation/pickers; `GiveawayDashboard`, `StandardGiveawayIndex`, `EventIndex` each gain an "Edit" toggle in their detail panel; migration adding `image_path` to `events` and `event_occurrences`; `CreateEventAction`/`UpdateEventAction`/`GenerateEventOccurrences`/`PostDueEventOccurrences` gain image handling (mirroring the existing Standard Giveaway pattern exactly); `bot/src/eventOccurrenceMessage.js` gains `.setImage()`; `UpdateStandardGiveawayAction` gains prize-item/required-role re-sync.
- **No changes** to Popup Giveaway's own image (already exists), to entry/signup logic, or to any already-generated occurrence (edits only affect occurrences generated after the edit, per the already-established snapshot pattern).

## Non-goals

- No editing of an already-`active`/`closed` Popup Giveaway - draft-only, per the existing, unchanged `UpdateGiveawayDraftAction` rule.
- No retroactive image on already-generated occurrences (same snapshot-isolation precedent as every other editable field).
- No bulk edit or edit-history/audit trail - a straightforward "make a change, save it" form per item.
