<?php

declare(strict_types=1);

namespace App\Actions\CollectionThemes;

use App\Models\CollectionTheme;
use Illuminate\Support\Facades\DB;

/**
 * Duplicates a collection theme within its own guild: a new theme row
 * with a derived name, the same image_path (references the same
 * already-uploaded file, no re-upload), and a copy of every item (name,
 * image_path, sort_order) as independent rows - editing the duplicate
 * afterward never affects the source.
 *
 * See openspec specs/collection-themes - "Collection theme duplication".
 */
class DuplicateCollectionThemeAction
{
    public function execute(CollectionTheme $theme): CollectionTheme
    {
        return DB::transaction(function () use ($theme) {
            $duplicate = $theme->guild->collectionThemes()->create([
                'name' => "{$theme->name} (Copy)",
                'image_path' => $theme->image_path,
            ]);

            foreach ($theme->items as $item) {
                $duplicate->items()->create([
                    'name' => $item->name,
                    'image_path' => $item->image_path,
                    'sort_order' => $item->sort_order,
                ]);
            }

            return $duplicate->load('items');
        });
    }
}
