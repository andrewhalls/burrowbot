<?php

use App\Http\Controllers\Internal\EventSignupController;
use App\Http\Controllers\Internal\GiveawayEntryController;
use App\Http\Controllers\Internal\GiveawayRecoveryController;
use App\Http\Controllers\Internal\GuildController;
use App\Http\Controllers\Internal\MemberController;
use App\Http\Controllers\Internal\OutboundActionController;
use App\Http\Controllers\Internal\StandardGiveawayEntryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| /internal/* routes
|--------------------------------------------------------------------------
|
| Loaded by routes/api.php under the "internal" prefix and "bot.auth"
| middleware. Populated capability-by-capability as each is implemented.
| Contract documented in openapi.yaml at the repo root.
|
*/

Route::post('/guilds', [GuildController::class, 'store']);
Route::patch('/guilds/{guild:discord_guild_id}', [GuildController::class, 'update']);

Route::put('/guilds/{guild:discord_guild_id}/members/{discordUserId}', [MemberController::class, 'upsert']);

Route::post('/giveaways/{giveaway}/entries', [GiveawayEntryController::class, 'store']);
Route::get('/giveaways/active', [GiveawayRecoveryController::class, 'activeGiveaways']);

Route::get('/outbound-actions', [OutboundActionController::class, 'index']);
Route::post('/outbound-actions/{outboundAction}/ack', [OutboundActionController::class, 'ack']);
Route::post('/outbound-actions/{outboundAction}/fail', [OutboundActionController::class, 'fail']);

Route::post('/event-occurrences/{occurrence}/signups', [EventSignupController::class, 'store']);

Route::post('/standard-giveaway-occurrences/{occurrence}/entries', [StandardGiveawayEntryController::class, 'store']);
