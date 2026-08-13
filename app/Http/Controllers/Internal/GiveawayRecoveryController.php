<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Giveaway;
use Illuminate\Http\JsonResponse;

/**
 * GET /internal/giveaways/active - called by the bot on startup/reconnect
 * so it can rebuild its discord_message_id -> giveaway_id routing table
 * without re-posting any giveaway that is already active.
 *
 * See openspec specs/discord-bot-gateway - "Idempotent recovery on reconnect".
 */
class GiveawayRecoveryController extends Controller
{
    public function activeGiveaways(): JsonResponse
    {
        $giveaways = Giveaway::query()
            ->with('guild')
            ->where('status', Giveaway::STATUS_ACTIVE)
            ->get()
            ->map(fn (Giveaway $giveaway) => [
                'id' => $giveaway->id,
                'guild_discord_id' => $giveaway->guild->discord_guild_id,
                'channel_id' => $giveaway->channel_id,
                'discord_message_id' => $giveaway->discord_message_id,
                'ends_at' => $giveaway->ends_at?->toIso8601String(),
            ]);

        return response()->json($giveaways);
    }
}
