## 1. Schema

- [x] 1.1 Migration: add nullable `display_name` to `discord_members`
- [x] 1.2 Update `DiscordMember` model (`$fillable`) and factory (add an optional `withDisplayName()` state)

## 2. Laravel sync path

- [x] 2.1 `SyncDiscordMemberAction::execute()` accepts and stores an optional `$displayName`
- [x] 2.2 `UpsertMemberRequest` accepts `display_name` (`sometimes|nullable|string|max:255`); `MemberController::upsert()` passes it through
- [x] 2.3 Update the giveaway-entry request/action (`JoinGiveawayAction` and its request) to accept `discord_display_name` and pass it to `SyncDiscordMemberAction`
- [x] 2.4 Update the event-signup request/action to accept `discord_display_name` and pass it through
- [x] 2.5 Update the standard-giveaway-entry request/action to accept `discord_display_name` and pass it through
- [x] 2.6 Pest tests: each of the four endpoints stores `display_name` when provided, and leaves it null when omitted, without clobbering a previously-stored value from another endpoint

## 3. Bot

- [x] 3.1 Add `resolveDisplayName(member, user)` helper (`member?.displayName ?? user.globalName ?? user.username`)
- [x] 3.2 `upsertObservedMember()`: accept `interaction.member` alongside `interaction.user`, send resolved display name to `upsertMember`
- [x] 3.3 `laravelClient.upsertMember()`: accept and send `display_name`
- [x] 3.4 Join-giveaway handler, `handleEventSignupInteraction()`, `handleStandardGiveawayEntryInteraction()`: resolve and pass display name alongside username
- [x] 3.5 `laravelClient.joinGiveaway()`, `signUpForEventOccurrence()`, `submitStandardGiveawayEntry()`: accept and send `discord_display_name`

## 4. Display

- [x] 4.1 `giveaway-dashboard.blade.php` entrant table: show `display_name ?? username`
- [x] 4.2 `occurrence-dashboard.blade.php` (standard giveaways) winners/entrants lists: show `display_name ?? username`
- [x] 4.3 `occurrence-roster.blade.php` (events) confirmed/waitlisted/not-attending lists: show `display_name ?? username`

## 5. Docs

- [x] 5.1 `openapi.yaml`: add `discord_display_name`/`display_name` to the affected internal endpoint request schemas

## 6. Verification

- [x] 6.1 Run the full Pest suite, confirm no regressions
- [x] 6.2 `npm run build`, confirm no Vite/Tailwind errors
