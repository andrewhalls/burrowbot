## 1. Data model

- [x] 1.1 Create a migration adding nullable `winner_message_channel_id` (string) and `winner_message_template` (text) to `giveaways`
- [x] 1.2 Add both fields to `Giveaway`'s `$fillable` and add a `hasWinnerMessageConfigured(): bool` helper (true only when both are non-null)
- [x] 1.3 Add `withWinnerMessage(string $channelId, string $template)` state to `GiveawayFactory`

## 2. Rendering and persistence

- [x] 2.1 Create `App\Support\Giveaways\RenderWinnerMessage`: given a template string, a winning member's Discord user id, and a won item name, substitutes `{winner}` (as `<@id>`) and `{prize}`, leaving any unrecognized token or a template with neither placeholder unchanged - mirrors `App\Support\StandardGiveaways\RenderCongratsMessage`'s exact behavior
- [x] 2.2 Pest unit tests for `RenderWinnerMessage`: both placeholders present; only one present; neither present; unrecognized token left literal
- [x] 2.3 Create `App\Actions\Giveaways\UpdateGiveawayWinnerMessageAction`: sets `winner_message_channel_id`/`winner_message_template` on a `Giveaway`, with no status check (editable at any status, unlike `UpdateGiveawayDraftAction`)
- [x] 2.4 Pest tests: updating the winner-message fields succeeds on a `draft`, `active`, and `closed` giveaway alike

## 3. Sending the per-winner message

- [x] 3.1 Add `DiscordOutboundAction::TYPE_ANNOUNCE_GIVEAWAY_WINNER = 'announce_giveaway_winner'` constant (reuses the existing `giveaway_id` FK column, no migration needed for this)
- [x] 3.2 In `JoinGiveawayAction::execute()`, immediately after creating the winning `GiveawayEntry` (and only on that path - never for `alreadyEntered`/`expired`), if `$locked->hasWinnerMessageConfigured()`, render the message via `RenderWinnerMessage` (winner = the joining member's `discord_user_id`, prize = the won item's name) and create a `DiscordOutboundAction` with `type: TYPE_ANNOUNCE_GIVEAWAY_WINNER`, `giveaway_id`, and a payload of `{ channel_id, message }`
- [x] 3.3 In `bot/src/discordAdapter.js`, add `announceGiveawayWinner({ channel_id, message })`: fetches the channel and sends `{ content: message }` - plain message, no embed
- [x] 3.4 Wire the new case into `bot/src/outboundActionExecutor.js`'s switch statement
- [x] 3.5 Pest tests on `JoinGiveawayAction`: a win on a giveaway with both fields configured enqueues the outbound action with the rendered message; a win with neither/only-one field configured enqueues nothing; `alreadyEntered`/`expired` paths never enqueue anything regardless of configuration
- [x] 3.6 Vitest tests for `announceGiveawayWinner` and the executor wiring, mirroring the existing adapter/executor test patterns (`bot/tests/discordAdapter.test.js`, `bot/tests/outboundActionExecutor.test.js`)

## 4. Admin configuration UI

- [x] 4.1 Add a paired-validation rule (if one of the two fields is set, the other becomes required) plus the channel-picker + template textarea fields to `CreateGiveaway` Livewire component + `create-giveaway.blade.php`, and to `EditGiveaway` + `edit-giveaway.blade.php` (both already draft-only, so these fields ride along with the rest of that form there)
- [x] 4.2 Create a new small `App\Livewire\Giveaways\EditGiveawayWinnerMessage` component (mirrors `EditStandardGiveawayOccurrence`'s shape): pre-fills from the giveaway, same paired-validation rule, saves via `UpdateGiveawayWinnerMessageAction`, dispatches a `giveaway-winner-message-updated` event - authorizes `manage` on the giveaway, with no draft/status gate
- [x] 4.3 Create `resources/views/livewire/giveaways/edit-giveaway-winner-message.blade.php`
- [x] 4.4 In `GiveawayIndex`: add `editingWinnerMessage` bool + `toggleEditWinnerMessage()` (authorize `manage`, no status check) + `#[On('giveaway-winner-message-updated')] closeEditWinnerMessageForm()`; toggling it off the other edit/create toggles the same way `toggleEdit`/`toggleCreateForm` already do
- [x] 4.5 In `giveaway-index.blade.php`: add a "Winner message" button in the header/detail area that's visible for any selected giveaway regardless of status (unlike the existing status-gated Edit button), rendering `EditGiveawayWinnerMessage` in the detail slot when toggled on; show a small indicator on the giveaway tile/detail when the fields are configured
- [x] 4.6 Pest/Livewire tests: paired validation rejects setting only one field on both Create and Edit forms; `EditGiveawayWinnerMessage` works regardless of giveaway status; opening/closing the winner-message form interacts correctly with the existing create/edit toggles (mirrors the existing "opening create form deselects/closes X" test pattern already used across every index screen this session)

## 5. Spec alignment

- [x] 5.1 Re-read `openspec/specs/giveaway-lifecycle/spec.md` and `openspec/specs/giveaway-entry/spec.md` after implementation to confirm every scenario in this change's delta specs is actually exercised by the tests added above
