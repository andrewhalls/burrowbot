## 1. Shared list-detail shell

- [x] 1.1 `resources/views/components/list-detail-shell.blade.php`: slotted responsive wrapper - single render of the `list`/`detail` slots, `hidden lg:block` toggled by a `:selected` prop so exactly one column shows below `lg:`, both always shown side by side at `lg:` and up (design.md Decision 1, revised during implementation)
- [x] 1.2 `resources/views/components/list-detail-empty.blade.php`: shared empty-state placeholder for "nothing selected yet"
- [x] 1.3 Add a short spacing-convention comment block to `resources/css/app.css` alongside the existing token comments, documenting the card/tile/section spacing scale (design.md Decision 3)

## 2. Popup Giveaways

- [x] 2.1 `GiveawayIndex`: add `?int $selectedId = null`, `select(int $id)`/`deselect()`; card-tile markup for each giveaway (replacing the divided-row list)
- [x] 2.2 Detail panel mounts `<livewire:giveaways.giveaway-dashboard :giveaway="$selectedGiveaway" />` for the selected giveaway (design.md Decision 2 - `mount(Giveaway $giveaway)` only needs the giveaway prop, `guild` is unused route-model-binding sugar)
- [x] 2.3 Pest/Livewire tests: selecting a tile shows that giveaway's dashboard in the panel; deselecting/the back control returns to the list-only view; the underlying `guilds.giveaways.show` route still works unmodified

## 3. Standard Giveaways

- [x] 3.1 `StandardGiveawayIndex`: same treatment as 2.1 - tiles remain series-level (unchanged list shape); selecting a series shows its most-recently-scheduled occurrence's dashboard, or a friendly "no occurrences yet" message if the series (typically a just-created recurring one) has none generated yet
- [x] 3.2 Detail panel mounts `<livewire:standard-giveaways.occurrence-dashboard :occurrence="$selectedOccurrence" />`
- [x] 3.3 Pest/Livewire tests: same shape as 2.3, for the occurrence dashboard, plus the zero-occurrences-yet empty state

## 4. Events

- [x] 4.1 `EventIndex`: same `$selectedId`/`select()`/`deselect()` treatment; card-tile markup
- [x] 4.2 New lightweight read-only event summary partial for the detail panel (title, description, role set, recurrence, posting mode, recent occurrences), linking through to `guilds.event-occurrences.show` for occurrence-level detail (design.md Decision 2)
- [x] 4.3 Pest/Livewire tests: selecting an event shows its summary in the panel; the summary links correctly to an occurrence's existing roster page

## 5. Collection Themes

- [x] 5.1 `CollectionThemeIndex`: same `$selectedId`/`select()`/`deselect()` treatment; card-tile markup showing each theme's name/item count (not its full item-management UI) in the list
- [x] 5.2 Detail panel mounts `<livewire:collection-themes.manage-collection-theme-items :theme="$selectedTheme" />` for the selected theme only (replacing "every theme's items always expanded")
- [x] 5.3 Pest/Livewire tests: only the selected theme's item-management UI renders; selecting a different theme swaps it (no prior test file existed for this Index component - created one)

## 6. Event Role Sets

- [x] 6.1 `EventRoleSetIndex`: same treatment as 5.1
- [x] 6.2 Detail panel mounts `<livewire:event-role-sets.manage-event-role-set-roles :role-set="$selectedRoleSet" />` for the selected role set only
- [x] 6.3 Pest/Livewire tests: same shape as 5.3 (no prior test file existed for this Index component - created one)

## 7. Verification

- [x] 7.1 Full Pest suite passes
- [ ] 7.2 Manual check in a browser: each of the 5 screens shows card tiles (not divider rows), a working detail panel on desktop width, and list-replaces-detail behavior below the responsive breakpoint (not verifiable from this environment - no browser access; left for the user to confirm live)
- [x] 7.3 `openspec validate improve-dashboard-list-detail-layout --strict` passes
