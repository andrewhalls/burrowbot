## Why

Admins building out several similar prize pools (e.g. seasonal variants of the same theme) currently have to recreate every item by hand - there's no way to start from an existing theme.

## What Changes

- A guild admin can duplicate an existing collection theme from its detail panel. The duplicate gets a name derived from the original (e.g. "Retro Arcade (Copy)"), and copies every item (name, image, order) and the theme's own image - referencing the same already-uploaded files, no re-upload needed.
- The duplicate is created immediately and selected in the list, ready to rename/edit like any other theme.

## Capabilities

### Modified Capabilities

- `collection-themes`: adds theme duplication as a creation path alongside the existing from-scratch creation.

## Impact

- Multi-guild scoping: unaffected - duplication only ever operates within the source theme's own guild, producing another guild-scoped row.
- `App\Actions\CollectionThemes\DuplicateCollectionThemeAction` (new).
- `App\Livewire\CollectionThemes\CollectionThemeIndex` (new `duplicate()` method), `resources/views/livewire/collection-themes/collection-theme-index.blade.php` (Duplicate button on the tile).

## Non-goals

- No "duplicate into a different guild" - source and duplicate are always the same guild's admin duplicating within their own guild.
- No selective duplication (e.g. "copy only some items") - always a full copy of the source theme as it currently stands.
