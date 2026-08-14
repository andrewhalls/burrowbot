## Why

Two places today ask an admin for Discord role(s) without any real connection to the guild's actual roles: Standard Giveaway's "Required roles" eligibility restriction is a free-text "comma/space separated Discord role IDs" input, and an Event Role Set's individual roles (e.g. "Tank"/"Healer"/"DPS") are just arbitrary admin-typed labels, not references to real Discord roles at all. Both are error-prone (a mistyped ID silently does nothing) and give no visibility into what roles actually exist in the server - the same problem `add-discord-channel-picker` already solved for channels.

## What Changes

- The bot syncs each guild's Discord roles into Laravel, the same way it already syncs channels (`discord-channels`) - guild join, real-time role create/update/delete events, and a periodic fallback resync. `@everyone` and Discord-managed roles (bot integration roles, the auto-created Server Booster role - already covered by its own dedicated "boosters only" restriction) are filtered out bot-side before Laravel ever sees them.
- A reusable searchable multi-select role picker, showing the guild's existing Event Role Sets as one-click presets at the top (bulk-adding all of that set's underlying Discord roles) followed by the individual synced Discord roles below.
- Standard Giveaway's "Required roles" free-text field is replaced by this picker.
- **BREAKING**: An Event Role Set's individual roles gain a `discord_role_id` reference and are now selected from the picker instead of typed as free text - `CreateEventRoleSet`'s per-row "Role name" text input and `ManageEventRoleSetRoles`'s "New role" text input are both replaced. Each role's display name now comes from its synced Discord role, not an admin-typed label.

## Capabilities

### New Capabilities
- `discord-roles`: keeps Laravel's record of each guild's synced Discord roles current, and defines the searchable-multi-select-with-presets picker contract every role-selecting field in the dashboard uses.

### Modified Capabilities
- `event-role-sets`: "Role set creation" and "Role set item management" - a role set's roles are now selected from the guild's synced Discord roles (with existing role sets offered as bulk-select presets) rather than typed as free-text names.

## Impact

- **Affected code**: migration adding `discord_roles` table; bot `bot/src/index.js` (RoleCreate/RoleUpdate/RoleDelete handlers + periodic sweep, mirroring the existing channel sync exactly) and a new `bot/src/discordRoles.js` filter; `PUT /internal/guilds/{guild}/roles` endpoint; migration adding nullable `discord_role_id` to `event_roles`; `CreateStandardGiveaway` (`requiredRoleIdsInput` removed, replaced by the picker); `CreateEventRoleSet` and `ManageEventRoleSetRoles` (free-text role name inputs replaced by the picker; each bulk-added role defaults to uncapped capacity, same default both already use today).
- **Multi-guild scoping**: `discord_roles` rows are guild-scoped exactly like `discord_channels` - a picker on Guild A's forms never shows Guild B's roles or role sets.
- **No changes** to eligibility-checking logic itself (`SubmitStandardGiveawayEntryAction`'s role/boost matching), event signup capacity/waitlist logic, or the bot's Discord role-checking on entry/signup - purely how role IDs get *selected* in the dashboard, not how they're evaluated.

## Non-goals

- No role management (create/rename/delete/permission changes) from Burrow's side - Discord remains the source of truth, Burrow only mirrors, exactly like `discord-channels`.
- No backfill of `discord_role_id` onto any Event Role that already exists with only a free-text name - existing roles keep functioning with their current name and no synced-role link; only newly added roles go through the picker.
- No per-role capacity configuration *during* a bulk multi-select add - every role added in one bulk action gets the same capacity settings already exposed on that form (matching today's existing single-role-add default of uncapped); fine-grained per-role capacity after a bulk add is unchanged from today's existing (already limited) reconfiguration capability.
