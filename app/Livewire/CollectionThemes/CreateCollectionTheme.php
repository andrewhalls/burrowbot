<?php

declare(strict_types=1);

namespace App\Livewire\CollectionThemes;

use App\Actions\CollectionThemes\CreateCollectionThemeAction;
use App\Models\Guild;
use App\Support\GuildAdmins\GuildAdminSection;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreateCollectionTheme extends Component
{
    use WithFileUploads;

    public Guild $guild;

    public string $name = '';

    /** @var list<string> */
    public array $items = ['', ''];

    public mixed $image = null;

    public function mount(Guild $guild): void
    {
        abort_unless(Auth::user()->hasGuildAdminSection($guild, GuildAdminSection::THEMES), 403);

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
        abort_unless(Auth::user()->hasGuildAdminSection($this->guild, GuildAdminSection::THEMES), 403);

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'items' => ['array'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);

        $imagePath = $this->image?->store('theme-images', 'public');

        try {
            $theme = $createTheme->execute($this->guild, $this->name, $this->items, $imagePath);
        } catch (InvalidArgumentException) {
            $this->addError('items', 'A theme must have at least one item.');

            return;
        }

        $this->reset(['name', 'items', 'image']);
        $this->items = ['', ''];

        $this->dispatch('collection-theme-created', themeId: $theme->id);
    }

    public function render(): View
    {
        return view('livewire.collection-themes.create-collection-theme');
    }
}
