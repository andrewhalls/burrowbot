<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Actions\Discord\SyncGuildChannelsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\SyncGuildChannelsRequest;
use App\Models\Guild;
use Illuminate\Http\JsonResponse;

class ChannelController extends Controller
{
    public function __construct(private readonly SyncGuildChannelsAction $syncChannels) {}

    public function sync(SyncGuildChannelsRequest $request, Guild $guild): JsonResponse
    {
        $this->syncChannels->execute($guild, $request->array('channels'));

        return response()->json($guild->channels()->orderBy('name')->get());
    }
}
