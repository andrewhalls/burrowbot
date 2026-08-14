## 1. Color tokens

- [x] 1.1 Replace `--color-accent`/`--color-accent-hover`/`--color-accent-ink` with the warm amber (dark) / terracotta (light) values in `resources/css/app.css`, contrast-checked per design.md Decision 1

## 2. Accessibility

- [x] 2.1 Global `:focus-visible` outline rule (accent-colored) for links/buttons/inputs/selects/textareas/`[tabindex]`
- [x] 2.2 Skip-to-content link + `id="main-content"` in `resources/views/components/layout.blade.php`
- [x] 2.3 Fix the login page's "Sign in with Discord" button missing `text-accent-ink`
- [x] 2.4 Pest test guarding the login button's contrast fix

## 3. Responsive

- [x] 3.1 `<main>` padding: `p-4 sm:p-6` instead of flat `p-6`
- [x] 3.2 Collapse `grid grid-cols-2 gap-3` to `grid grid-cols-1 sm:grid-cols-2 gap-3` across all 10 affected views (5 list tile grids + paired form-field grids in create/edit event and create/edit standard giveaway)

## 4. Verification

- [x] 4.1 Full Pest suite passes
- [x] 4.2 `npm run build` succeeds with no errors
