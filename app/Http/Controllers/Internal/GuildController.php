<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Actions\Guilds\SyncGuildAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGuildRequest;
use App\Http\Requests\UpdateGuildRequest;
use App\Models\Guild;
use Illuminate\Http\JsonResponse;

class GuildController extends Controller
{
    public function __construct(private readonly SyncGuildAction $syncGuild) {}

    public function store(StoreGuildRequest $request): JsonResponse
    {
        $guild = $this->syncGuild->joined(
            $request->string('discord_guild_id')->toString(),
            $request->string('name')->toString(),
        );

        return response()->json($guild, 201);
    }

    public function update(UpdateGuildRequest $request, Guild $guild): JsonResponse
    {
        $guild = $this->syncGuild->update($guild, $request->validated());

        return response()->json($guild);
    }
}
