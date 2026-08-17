<?php

declare(strict_types=1);

namespace App\Livewire\CollectionThemes;

use App\Actions\CollectionThemes\DuplicateCollectionThemeAction;
use App\Models\CollectionTheme;
use App\Models\Guild;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class CollectionThemeIndex extends Component
{
    public Guild $guild;

    public bool $showCreateForm = false;

    public ?int $selectedId = null;

    public function mount(Guild $guild): void
    {
        $this->authorize('view', $guild);

        $this->guild = $guild;
    }

    public function toggleCreateForm(): void
    {
        $this->showCreateForm = ! $this->showCreateForm;

        if ($this->showCreateForm) {
            $this->selectedId = null;
        }
    }

    #[On('collection-theme-created')]
    public function closeCreateForm(int $themeId): void
    {
        $this->showCreateForm = false;
        $this->selectedId = $themeId;
    }

    public function select(int $themeId): void
    {
        $exists = CollectionTheme::query()->where('guild_id', $this->guild->id)->where('id', $themeId)->exists();

        $this->selectedId = $exists ? $themeId : null;
        $this->showCreateForm = false;
    }

    public function deselect(): void
    {
        $this->selectedId = null;
    }

    public function duplicate(int $themeId, DuplicateCollectionThemeAction $duplicateTheme): void
    {
        $theme = CollectionTheme::query()->where('guild_id', $this->guild->id)->findOrFail($themeId);

        $this->authorize('manage', $theme);

        $duplicated = $duplicateTheme->execute($theme);

        $this->selectedId = $duplicated->id;
    }

    public function render(): View
    {
        $themes = $this->guild->collectionThemes()
            ->withCount('items')
            ->orderBy('name')
            ->get();

        return view('livewire.collection-themes.collection-theme-index', [
            'themes' => $themes,
            'selectedTheme' => $this->selectedId ? $themes->firstWhere('id', $this->selectedId) : null,
        ]);
    }
}
