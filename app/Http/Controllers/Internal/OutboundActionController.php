<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Http\Requests\AckOutboundActionRequest;
use App\Http\Requests\FailOutboundActionRequest;
use App\Models\DiscordOutboundAction;
use App\Models\EventOccurrence;
use App\Models\Giveaway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OutboundActionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $actions = DiscordOutboundAction::query()
            ->where('status', DiscordOutboundAction::STATUS_PENDING)
            ->when($request->filled('since'), fn ($query) => $query->where('id', '>', $request->integer('since')))
            ->orderBy('id')
            ->get();

        return response()->json($actions);
    }

    public function ack(AckOutboundActionRequest $request, DiscordOutboundAction $outboundAction): JsonResponse
    {
        $outboundAction->update(['status' => DiscordOutboundAction::STATUS_ACKED]);

        $discordMessageId = $request->string('discord_message_id')->toString();
        $discordThreadId = $request->string('discord_thread_id')->toString();

        if ($outboundAction->type === DiscordOutboundAction::TYPE_POST_GIVEAWAY_MESSAGE && $discordMessageId !== '') {
            Giveaway::query()
                ->whereKey($outboundAction->giveaway_id)
                ->update(['discord_message_id' => $discordMessageId]);
        }

        if (in_array($outboundAction->type, [
            DiscordOutboundAction::TYPE_POST_EVENT_OCCURRENCE_THREAD,
            DiscordOutboundAction::TYPE_POST_EVENT_OCCURRENCE_MESSAGE,
        ], true)) {
            EventOccurrence::query()
                ->whereKey($outboundAction->event_occurrence_id)
                ->update(array_filter([
                    'discord_message_id' => $discordMessageId !== '' ? $discordMessageId : null,
                    'discord_thread_id' => $discordThreadId !== '' ? $discordThreadId : null,
                ]));
        }

        return response()->json($outboundAction->fresh());
    }

    public function fail(FailOutboundActionRequest $request, DiscordOutboundAction $outboundAction): JsonResponse
    {
        $outboundAction->update([
            'status' => DiscordOutboundAction::STATUS_FAILED,
            'attempts' => $outboundAction->attempts + 1,
            'last_failure_reason' => $request->string('reason')->toString(),
        ]);

        return response()->json($outboundAction->fresh());
    }
}
