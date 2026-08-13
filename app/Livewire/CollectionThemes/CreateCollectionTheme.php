<?php

declare(strict_types=1);

namespace App\Livewire\CollectionThemes;

use App\Actions\CollectionThemes\CreateCollectionThemeAction;
use App\Models\Guild;
use Illuminate\Contracts\View\View;
use InvalidArgumentException;
use Livewire\Component;

class CreateCollectionTheme extends Component
{
    public Guild $guild;

    public string $name = '';

    /** @var list<string> */
    public array $items = ['', ''];

    public function mount(Guild $guild): void
    {
        $this->authorize('manage', $guild);

        $this->guild = $guild;
    }

    public function addItemRow(): void
    {
        $this->items[] = '';
    }

    public function removeItemRow(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function save(CreateCollectionThemeAction $createTheme): void
    {
        $this->authorize('manage', $this->guild);

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'items' => ['array'],
        ]);

        try {
            $createTheme->execute($this->guild, $this->name, $this->items);
        } catch (InvalidArgumentException) {
            $this->addError('items', 'A theme must have at least one item.');

            return;
        }

        $this->reset(['name', 'items']);
        $this->items = ['', ''];

        $this->dispatch('collection-theme-created');
    }

    public function render(): View
    {
        return view('livewire.collection-themes.create-collection-theme');
    }
}
