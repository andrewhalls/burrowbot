## 1. Schema

- [x] 1.1 Migration: add nullable `image_path` to `collection_themes`
- [x] 1.2 Update `CollectionTheme` model (`$fillable`) and factory (add an optional `withImage()` state)

## 2. Creation

- [x] 2.1 `CreateCollectionThemeAction::execute()` accepts and stores an optional image path
- [x] 2.2 `CreateCollectionTheme` Livewire component: `image` property, validation (`nullable|image|max:5120`), pass through to the action, reset on successful save
- [x] 2.3 `create-collection-theme.blade.php`: image upload field + preview, matching the existing field markup used by `create-standard-giveaway.blade.php`
- [x] 2.4 Pest test: creating a theme with an image records `image_path`; creating without one leaves it null

## 3. Post-creation image management

- [x] 3.1 Small action (or extend `ManageCollectionThemeItemsAction`) to set/replace/remove a theme's image, deleting the old stored file on replace/remove
- [x] 3.2 `ManageCollectionThemeItems` Livewire component: image property + `saveImage()`/`removeImage()` methods
- [x] 3.3 `manage-collection-theme-items.blade.php`: image section (current image preview, upload/replace input, remove button) above the item list
- [x] 3.4 Pest/Livewire test: set image on a theme with none, replace an existing image, remove an image - each leaves the theme's items untouched

## 4. Display

- [x] 4.1 `collection-theme-index.blade.php`: render the theme's image in the tile's icon-badge slot when set, falling back to the existing glyph when not
- [x] 4.2 Pest test: theme with an image renders its `<img>`; theme without one renders the fallback glyph

## 5. Verification

- [x] 5.1 Run the full Pest suite, confirm no regressions
- [x] 5.2 `npm run build`, confirm no Vite/Tailwind errors
