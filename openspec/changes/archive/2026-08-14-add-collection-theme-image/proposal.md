## Why

A collection theme (e.g. "Retro Arcade") has no image of its own today - `CreateCollectionTheme` only accepts a name and item rows, so the theme tile on the dashboard falls back to a generic decorative icon glyph instead of a real thumbnail, even though individual theme *items* already support per-item images. Admins want the theme itself to carry a recognizable image, settable at creation and editable afterward.

## What Changes

- Add a nullable `image_path` column to `collection_themes` (same storage convention as `giveaways.image_path`/`standard_giveaways.image_path`).
- `CreateCollectionTheme` gains an optional image upload field (max 5MB, same convention as every other image field in the app), stored via `CreateCollectionThemeAction`.
- `ManageCollectionThemeItems` (the existing "manage this theme after creation" screen) gains a section to upload, replace, or remove the theme's own image - this is the natural home for it since there's no separate "edit theme" screen today, and it already mirrors this same add/replace/remove pattern for per-item images.
- The theme list tile (`collection-theme-index.blade.php`) shows the theme's uploaded image (cropped into the existing icon-badge slot) when set, falling back to the current decorative glyph when not.
- Uploading an oversized theme image gets the same friendly client-side size-check error already added globally (`resources/js/app.js`), not a raw "Post content too large" failure.

## Capabilities

### New Capabilities

(none)

### Modified Capabilities

- `collection-themes`: theme creation gains an optional image field; theme management gains the ability to set/replace/remove the theme's own image, independent of its items' images.

## Impact

- Multi-guild scoping: unaffected - `image_path` is just another attribute on an already guild-scoped `CollectionTheme` row; no new cross-guild exposure.
- Migration: `add_image_path_to_collection_themes_table`.
- `App\Models\CollectionTheme`, `App\Actions\CollectionThemes\CreateCollectionThemeAction`, `App\Actions\CollectionThemes\ManageCollectionThemeItemsAction` (or a small new action for the theme's own image, see design.md).
- `App\Livewire\CollectionThemes\CreateCollectionTheme`, `App\Livewire\CollectionThemes\ManageCollectionThemeItems` and their Blade views.
- `resources/views/livewire/collection-themes/collection-theme-index.blade.php` (tile image).

## Non-goals

- No cropping/resizing UI - stored and displayed as-uploaded (same as every other image field in the app today).
- No change to per-item images or the existing item image-upload flow.
- No retroactive image backfill for existing themes - they simply show the fallback glyph until an admin sets one.
