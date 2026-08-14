<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Actions\Members\SyncDiscordMemberAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertMemberRequest;
use App\Models\Guild;
use Illuminate\Http\JsonResponse;

class MemberController extends Controller
{
    public function __construct(private readonly SyncDiscordMemberAction $syncMember) {}

    public function upsert(UpsertMemberRequest $request, Guild $guild, string $discordUserId): JsonResponse
    {
        $member = $this->syncMember->execute(
            $guild,
            $discordUserId,
            $request->string('username')->toString(),
            $request->string('avatar_url')->toString() ?: null,
            $request->string('display_name')->toString() ?: null,
        );

        return response()->json($member);
    }
}
