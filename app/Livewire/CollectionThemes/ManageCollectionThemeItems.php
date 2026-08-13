<?php

declare(strict_types=1);

namespace App\Livewire\CollectionThemes;

use App\Actions\CollectionThemes\ManageCollectionThemeItemsAction;
use App\Models\CollectionTheme;
use App\Models\CollectionThemeItem;
use Illuminate\Contracts\View\View;
use InvalidArgumentException;
use Livewire\Component;

class ManageCollectionThemeItems extends Component
{
    public CollectionTheme $theme;

    public string $newItemName = '';

    public function mount(CollectionTheme $theme): void
    {
        $this->authorize('manage', $theme);

        $this->theme = $theme;
    }

    public function addItem(ManageCollectionThemeItemsAction $manageItems): void
    {
        $this->authorize('manage', $this->theme);

        $this->validate(['newItemName' => ['required', 'string', 'max:255']]);

        try {
            $manageItems->addItem($this->theme, $this->newItemName);
        } catch (InvalidArgumentException $e) {
            $this->addError('newItemName', $e->getMessage());

            return;
        }

        $this->reset('newItemName');
        $this->theme->unsetRelation('items');
    }

    public function removeItem(int $itemId, ManageCollectionThemeItemsAction $manageItems): void
    {
        $this->authorize('manage', $this->theme);

        $item = CollectionThemeItem::query()
            ->where('collection_theme_id', $this->theme->id)
            ->findOrFail($itemId);

        try {
            $manageItems->removeItem($this->theme, $item);
        } catch (InvalidArgumentException $e) {
            $this->addError('newItemName', $e->getMessage());

            return;
        }

        $this->theme->unsetRelation('items');
    }

    public function render(): View
    {
        return view('livewire.collection-themes.manage-collection-theme-items', [
            'items' => $this->theme->items,
            'editable' => $this->theme->isEditable(),
        ]);
    }
}
