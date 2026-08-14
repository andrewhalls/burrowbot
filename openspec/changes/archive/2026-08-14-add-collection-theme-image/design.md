## Context

`CollectionTheme` currently has no image column at all. Per-item images already exist (`CollectionThemeItem.image_path`, uploaded via `ManageCollectionThemeItems::addItem()`/its update path) and follow the established Livewire temp-upload -> `Storage::disk('public')` pattern used by `giveaways.image_path` and `standard_giveaways.image_path`. There is no dedicated "edit theme" screen today - `ManageCollectionThemeItems` is the only screen shown after a theme is selected, and it's scoped to item add/remove.

The Laravel <-> bot communication contract, random item assignment, giveaway expiry, and recurrence rules are all unaffected by this change - a theme's own image is never posted to Discord by the bot (only per-occurrence giveaway posts carry images), so none of those rules apply here.

## Goals / Non-Goals

**Goals:**
- Optional image at theme creation, using the same 5MB/`image/*` convention as every other image field.
- Set/replace/remove the theme's image after creation, from the existing per-theme management screen.
- Theme list tiles show the image when set.

**Non-Goals:**
- No new "edit theme" screen - reuses `ManageCollectionThemeItems`.
- The theme image is never sent to Discord (unlike giveaway/event images) - it's a dashboard-only visual aid for admins, since themes are a reusable content pool, not something posted as-is.

## Decisions

**Decision 1: Theme image lives on `ManageCollectionThemeItems`, not a new component.**
`ManageCollectionThemeItems` is already the "you've selected a theme, now manage it" screen. Adding a small image section (current image preview, upload/replace input, remove button) above the item list avoids introducing a parallel `EditCollectionTheme` component for a single field. Alternative considered: a new dedicated edit-theme component, rejected as needless duplication for one field.

**Decision 2: Same upload/validation convention as existing image fields.**
`image` property with `#[Validate('nullable|image|max:5120')]` (or the form-level equivalent already used by `CreateStandardGiveaway`/`CreateEvent`), stored via `$image->store(...)` on the `public` disk, old file deleted on replace/remove (mirrors the standard-giveaway-image pattern already in the codebase). No new upload mechanism.

**Decision 3: Tile rendering falls back to the existing glyph.**
`collection-theme-index.blade.php`'s icon-badge `<span>` renders `<img>` when `$theme->image_path` is set (object-cover, same badge dimensions), otherwise the current inline SVG glyph - a single conditional, no new component.

## Risks / Trade-offs

- [Oversized upload still hits Livewire's raw temp-upload endpoint before Laravel validation runs] → Already mitigated app-wide by the client-side size pre-check in `resources/js/app.js`; no new work needed here, just confirm the theme's file input carries `wire:model` so the existing global listener covers it.
- [Orphaned file on disk if a theme is deleted after having an image] → Out of scope: theme deletion isn't a feature covered here or elsewhere yet; when it is, the delete action should clean up `image_path` as part of that work.
