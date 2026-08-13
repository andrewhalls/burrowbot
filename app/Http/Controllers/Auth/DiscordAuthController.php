<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\SyncGuildAdminsForUserAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Discord\DiscordUserGuildsClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

class DiscordAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('discord')
            ->scopes(['identify', 'guilds'])
            ->redirect();
    }

    public function callback(
        Request $request,
        DiscordUserGuildsClient $guildsClient,
        SyncGuildAdminsForUserAction $syncGuildAdmins,
    ): RedirectResponse {
        if ($request->has('error')) {
            return redirect()->route('login')->with('error', 'Discord sign-in was cancelled.');
        }

        /** @var SocialiteUser $discordUser */
        $discordUser = Socialite::driver('discord')->user();

        $user = User::query()->updateOrCreate(
            ['discord_user_id' => $discordUser->getId()],
            [
                'name' => $discordUser->getNickname() ?? $discordUser->getName() ?? $discordUser->getId(),
                'email' => $discordUser->getEmail(),
                'avatar_url' => $discordUser->getAvatar(),
            ],
        );

        $administeredGuildIds = $guildsClient->administeredGuildIds($discordUser->token);
        $syncGuildAdmins->execute($user, $administeredGuildIds);

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
