<?php

declare(strict_types=1);

namespace App\Livewire\CollectionThemes;

use App\Models\Guild;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class CollectionThemeIndex extends Component
{
    public Guild $guild;

    public bool $showCreateForm = false;

    public function mount(Guild $guild): void
    {
        $this->authorize('view', $guild);

        $this->guild = $guild;
    }

    #[On('collection-theme-created')]
    public function closeCreateForm(): void
    {
        $this->showCreateForm = false;
    }

    public function render(): View
    {
        $themes = $this->guild->collectionThemes()
            ->withCount('items')
            ->orderBy('name')
            ->get();

        return view('livewire.collection-themes.collection-theme-index', [
            'themes' => $themes,
        ]);
    }
}
