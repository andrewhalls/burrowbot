<?php

use App\Http\Controllers\Auth\DiscordAuthController;
use App\Livewire\CollectionThemes\CollectionThemeIndex;
use App\Livewire\Dashboard\DashboardHome;
use App\Livewire\EventRoleSets\EventRoleSetIndex;
use App\Livewire\Events\EventIndex;
use App\Livewire\Events\OccurrenceRoster;
use App\Livewire\Giveaways\CreateGiveaway;
use App\Livewire\Giveaways\GiveawayDashboard;
use App\Livewire\Guilds\GuildSettings;
use App\Livewire\StandardGiveaways\OccurrenceDashboard;
use App\Livewire\StandardGiveaways\StandardGiveawayIndex;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
});

// Not guest-only: an already-authenticated user re-runs this flow via the
// dashboard's "check again" action (design.md - Decision 3) after inviting
// the bot, to re-sync guild_admins without a full sign-out/sign-in.
Route::get('/auth/discord/redirect', [DiscordAuthController::class, 'redirect'])->name('auth.discord.redirect');
Route::get('/auth/discord/callback', [DiscordAuthController::class, 'callback'])->name('auth.discord.callback');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardHome::class)->name('dashboard');

    Route::get('/guilds/{guild}/settings', GuildSettings::class)->name('guilds.settings');
    Route::get('/guilds/{guild}/themes', CollectionThemeIndex::class)->name('guilds.themes.index');
    Route::get('/guilds/{guild}/event-role-sets', EventRoleSetIndex::class)->name('guilds.event-role-sets.index');
    Route::get('/guilds/{guild}/events', EventIndex::class)->name('guilds.events.index');
    Route::get('/guilds/{guild}/event-occurrences/{occurrence}', OccurrenceRoster::class)->name('guilds.event-occurrences.show');
    Route::get('/guilds/{guild}/giveaways/create', CreateGiveaway::class)->name('guilds.giveaways.create');
    Route::get('/guilds/{guild}/giveaways/{giveaway}', GiveawayDashboard::class)->name('guilds.giveaways.show');
    Route::get('/guilds/{guild}/standard-giveaways', StandardGiveawayIndex::class)->name('guilds.standard-giveaways.index');
    Route::get('/guilds/{guild}/standard-giveaway-occurrences/{occurrence}', OccurrenceDashboard::class)->name('guilds.standard-giveaway-occurrences.show');

    Route::post('/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
