<?php

declare(strict_types=1);

namespace App\Livewire\Broadcasts;

use App\Actions\Broadcasts\ArchiveBroadcastAction;
use App\Actions\Broadcasts\DeleteBroadcastAction;
use App\Actions\Broadcasts\UnarchiveBroadcastAction;
use App\Actions\Broadcasts\UpdateBroadcastStatusAction;
use App\Models\Broadcast;
use App\Models\Guild;
use Illuminate\Contracts\View\View;
use InvalidArgumentException;
use Livewire\Attributes\On;
use Livewire\Component;

class BroadcastIndex extends Component
{
    public Guild $guild;

    public bool $showCreateForm = false;

    public ?int $selectedId = null;

    public bool $editing = false;

    public bool $showArchived = false;

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
            $this->editing = false;
        }
    }

    #[On('broadcast-created')]
    public function closeCreateForm(int $broadcastId): void
    {
        $this->showCreateForm = false;
        $this->selectedId = $broadcastId;
    }

    #[On('broadcast-updated')]
    public function closeEditForm(): void
    {
        $this->editing = false;
    }

    public function setStatus(int $broadcastId, string $status, UpdateBroadcastStatusAction $updateStatus): void
    {
        $broadcast = Broadcast::query()->where('guild_id', $this->guild->id)->findOrFail($broadcastId);

        $this->authorize('manage', $broadcast);

        $updateStatus->execute($broadcast, $status);
    }

    public function archive(int $broadcastId, ArchiveBroadcastAction $archiveBroadcast): void
    {
        $broadcast = Broadcast::query()->where('guild_id', $this->guild->id)->findOrFail($broadcastId);

        $this->authorize('manage', $broadcast);

        $archiveBroadcast->execute($broadcast);
    }

    public function unarchive(int $broadcastId, UnarchiveBroadcastAction $unarchiveBroadcast): void
    {
        $broadcast = Broadcast::query()->where('guild_id', $this->guild->id)->findOrFail($broadcastId);

        $this->authorize('manage', $broadcast);

        $unarchiveBroadcast->execute($broadcast);
    }

    public function select(int $broadcastId): void
    {
        $exists = Broadcast::query()->where('guild_id', $this->guild->id)->where('id', $broadcastId)->exists();

        $this->selectedId = $exists ? $broadcastId : null;
        $this->editing = false;
        $this->showCreateForm = false;
    }

    public function deselect(): void
    {
        $this->selectedId = null;
        $this->editing = false;
    }

    public function toggleEdit(): void
    {
        $broadcast = Broadcast::query()->where('guild_id', $this->guild->id)->findOrFail($this->selectedId);

        $this->authorize('manage', $broadcast);

        $this->editing = ! $this->editing;
    }

    public function delete(DeleteBroadcastAction $deleteBroadcast): void
    {
        $broadcast = Broadcast::query()->where('guild_id', $this->guild->id)->findOrFail($this->selectedId);

        $this->authorize('manage', $broadcast);

        try {
            $deleteBroadcast->execute($broadcast);
        } catch (InvalidArgumentException $e) {
            $this->addError('delete', $e->getMessage());

            return;
        }

        $this->selectedId = null;
        $this->editing = false;
    }

    public function render(): View
    {
        $broadcasts = $this->guild->broadcasts()
            ->with('creator')
            ->when(! $this->showArchived, fn ($query) => $query->whereNull('archived_at'))
            ->orderByDesc('created_at')
            ->get();

        $selectedBroadcast = $this->selectedId
            ? Broadcast::query()
                ->with(['creator', 'occurrences' => fn ($query) => $query->orderByDesc('scheduled_post_at')->limit(5)])
                ->find($this->selectedId)
            : null;

        return view('livewire.broadcasts.broadcast-index', [
            'broadcasts' => $broadcasts,
            'selectedBroadcast' => $selectedBroadcast,
        ]);
    }
}
