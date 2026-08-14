<?php

declare(strict_types=1);

namespace App\Livewire\CollectionThemes;

use App\Actions\CollectionThemes\ManageCollectionThemeItemsAction;
use App\Models\CollectionTheme;
use App\Models\CollectionThemeItem;
use Illuminate\Contracts\View\View;
use InvalidArgumentException;
use Livewire\Component;
use Livewire\WithFileUploads;

class ManageCollectionThemeItems extends Component
{
    use WithFileUploads;

    public CollectionTheme $theme;

    public string $newItemName = '';

    public mixed $newItemImage = null;

    public mixed $themeImage = null;

    public function mount(CollectionTheme $theme): void
    {
        $this->authorize('manage', $theme);

        $this->theme = $theme;
    }

    public function saveThemeImage(ManageCollectionThemeItemsAction $manageItems): void
    {
        $this->authorize('manage', $this->theme);

        $this->validate(['themeImage' => ['required', 'image', 'max:5120']]);

        $imagePath = $this->themeImage->store('theme-images', 'public');

        $manageItems->setImage($this->theme, $imagePath);

        $this->reset('themeImage');
    }

    public function removeThemeImage(ManageCollectionThemeItemsAction $manageItems): void
    {
        $this->authorize('manage', $this->theme);

        $manageItems->removeImage($this->theme);
    }

    public function addItem(ManageCollectionThemeItemsAction $manageItems): void
    {
        $this->authorize('manage', $this->theme);

        $validated = $this->validate([
            'newItemName' => ['required', 'string', 'max:255'],
            'newItemImage' => ['nullable', 'image', 'max:5120'],
        ]);

        $imagePath = $this->newItemImage?->store('theme-item-images', 'public');

        try {
            $manageItems->addItem($this->theme, $validated['newItemName'], $imagePath);
        } catch (InvalidArgumentException $e) {
            $this->addError('newItemName', $e->getMessage());

            return;
        }

        $this->reset('newItemName', 'newItemImage');
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
