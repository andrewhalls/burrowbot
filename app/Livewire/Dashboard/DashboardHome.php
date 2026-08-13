<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Post-login landing page: lists the guilds the authenticated user
 * administers, each linking into that guild's pages, or shows onboarding
 * (bot-invite link + instructions) when they administer none yet.
 *
 * See openspec specs/dashboard-home.
 */
class DashboardHome extends Component
{
    public function render(): View
    {
        $guildAdmins = auth()->user()->guildAdmins()->with('guild')->get();

        return view('livewire.dashboard.dashboard-home', [
            'guilds' => $guildAdmins->pluck('guild'),
        ]);
    }
}
