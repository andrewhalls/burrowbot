<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Actions\Giveaways\JoinGiveawayAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\JoinGiveawayRequest;
use App\Models\Giveaway;
use Illuminate\Http\JsonResponse;

class GiveawayEntryController extends Controller
{
    public function store(JoinGiveawayRequest $request, Giveaway $giveaway, JoinGiveawayAction $joinGiveaway): JsonResponse
    {
        $result = $joinGiveaway->execute(
            $giveaway,
            $request->string('discord_user_id')->toString(),
            $request->string('discord_username')->toString(),
        );

        return response()->json($result->toArray());
    }
}
