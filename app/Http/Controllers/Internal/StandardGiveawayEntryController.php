<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Actions\StandardGiveaways\SubmitStandardGiveawayEntryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StandardGiveawayEntryRequest;
use App\Models\StandardGiveawayOccurrence;
use Illuminate\Http\JsonResponse;

class StandardGiveawayEntryController extends Controller
{
    public function store(
        StandardGiveawayEntryRequest $request,
        StandardGiveawayOccurrence $occurrence,
        SubmitStandardGiveawayEntryAction $submitEntry,
    ): JsonResponse {
        $result = $submitEntry->execute(
            $occurrence,
            $request->string('discord_user_id')->toString(),
            $request->string('discord_username')->toString(),
            $request->array('discord_role_ids'),
            $request->boolean('is_boosting'),
        );

        return response()->json($result->toArray());
    }
}
