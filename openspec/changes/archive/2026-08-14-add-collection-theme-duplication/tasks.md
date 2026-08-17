## 1. Duplication

- [x] 1.1 `DuplicateCollectionThemeAction`: create a new theme in the same guild with a derived name ("{name} (Copy)"), copying `image_path` and every item's `name`/`image_path`/`sort_order`
- [x] 1.2 `CollectionThemeIndex::duplicate(int $themeId)`: authorize `manage` on the source theme, call the action, select the new theme
- [x] 1.3 `collection-theme-index.blade.php`: "Duplicate" button on each tile
- [x] 1.4 Pest tests: duplicating copies items (name/image/order) and the theme's own image into an independent new row; duplicate name is derived from the original; duplicating a theme with zero items still succeeds

## 2. Verification

- [x] 2.1 Full Pest suite passes
- [x] 2.2 `npm run build` succeeds
