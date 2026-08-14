<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Actions\Events\MarkNotAttendingAction;
use App\Actions\Events\SignUpForEventRoleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\EventSignupRequest;
use App\Models\EventOccurrence;
use App\Models\EventRole;
use Illuminate\Http\JsonResponse;

class EventSignupController extends Controller
{
    public function store(
        EventSignupRequest $request,
        EventOccurrence $occurrence,
        SignUpForEventRoleAction $signUpForRole,
        MarkNotAttendingAction $markNotAttending,
    ): JsonResponse {
        $discordUserId = $request->string('discord_user_id')->toString();
        $discordUsername = $request->string('discord_username')->toString();
        $discordDisplayName = $request->string('discord_display_name')->toString() ?: null;

        if ($request->filled('event_role_id')) {
            $role = EventRole::query()
                ->where('event_role_set_id', $occurrence->event_role_set_id)
                ->findOrFail($request->integer('event_role_id'));

            $result = $signUpForRole->execute($occurrence, $role, $discordUserId, $discordUsername, $discordDisplayName);
        } else {
            $result = $markNotAttending->execute($occurrence, $discordUserId, $discordUsername, $discordDisplayName);
        }

        return response()->json($result->toArray());
    }
}
