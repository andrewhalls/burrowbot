<?php

declare(strict_types=1);

namespace App\Actions\CollectionThemes;

use App\Models\CollectionTheme;
use App\Models\Guild;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Creates a collection theme with its initial item list in one transaction.
 *
 * See openspec specs/themed-item-lists - "Theme creation": a theme with
 * zero (non-blank) items must be rejected and nothing created.
 */
class CreateCollectionThemeAction
{
    /**
     * @param  list<string>  $itemNames
     */
    public function execute(Guild $guild, string $name, array $itemNames): CollectionTheme
    {
        $itemNames = array_values(array_filter(
            array_map('trim', $itemNames),
            fn (string $item) => $item !== '',
        ));

        if ($itemNames === []) {
            throw new InvalidArgumentException('A collection theme must have at least one item.');
        }

        return DB::transaction(function () use ($guild, $name, $itemNames) {
            $theme = $guild->collectionThemes()->create(['name' => $name]);

            foreach ($itemNames as $index => $itemName) {
                $theme->items()->create(['name' => $itemName, 'sort_order' => $index]);
            }

            return $theme->load('items');
        });
    }
}
