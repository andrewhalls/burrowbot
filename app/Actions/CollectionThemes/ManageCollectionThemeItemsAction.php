<?php

declare(strict_types=1);

namespace App\Actions\CollectionThemes;

use App\Models\CollectionTheme;
use App\Models\CollectionThemeItem;
use InvalidArgumentException;

/**
 * Adds/removes items on an existing collection theme. Blocked while the
 * theme backs an active giveaway, so the prize pool can't change mid-draw.
 *
 * See openspec specs/themed-item-lists - "Theme item management".
 */
class ManageCollectionThemeItemsAction
{
    public function addItem(CollectionTheme $theme, string $name, ?string $imagePath = null): CollectionThemeItem
    {
        $this->ensureEditable($theme);

        $name = trim($name);

        if ($name === '') {
            throw new InvalidArgumentException('Item name cannot be blank.');
        }

        $nextSortOrder = ((int) $theme->items()->max('sort_order')) + 1;

        return $theme->items()->create(['name' => $name, 'image_path' => $imagePath, 'sort_order' => $nextSortOrder]);
    }

    public function removeItem(CollectionTheme $theme, CollectionThemeItem $item): void
    {
        $this->ensureEditable($theme);

        $item->delete();
    }

    private function ensureEditable(CollectionTheme $theme): void
    {
        if (! $theme->isEditable()) {
            throw new InvalidArgumentException('This theme cannot be edited while it backs an active giveaway.');
        }
    }
}
