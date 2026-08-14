<?php

declare(strict_types=1);

namespace App\Actions\StandardGiveaways;

use App\Actions\Members\SyncDiscordMemberAction;
use App\Models\StandardGiveawayEntry;
use App\Models\StandardGiveawayOccurrence;
use App\Support\StandardGiveaways\StandardGiveawayEntryResult;
use Illuminate\Support\Facades\DB;

/**
 * Processes a member entering a standard giveaway occurrence: the single
 * place that enforces the end-time cutoff, booster/role eligibility, and
 * one-entry-per-member.
 *
 * See openspec specs/standard-giveaway-entries (all requirements);
 * design.md Decision 1 (eligibility data comes from the bot, per request)
 * and Decision 4 (checked once, at entry).
 */
class SubmitStandardGiveawayEntryAction
{
    public function __construct(private readonly SyncDiscordMemberAction $syncMember) {}

    /**
     * @param  list<string>  $discordRoleIds
     */
    public function execute(
        StandardGiveawayOccurrence $occurrence,
        string $discordUserId,
        string $discordUsername,
        array $discordRoleIds,
        bool $isBoosting,
        ?string $discordDisplayName = null,
    ): StandardGiveawayEntryResult {
        return DB::transaction(function () use ($occurrence, $discordUserId, $discordUsername, $discordRoleIds, $isBoosting, $discordDisplayName) {
            // Locks the occurrence row so concurrent entries on the SAME
            // occurrence serialize here - mirrors JoinGiveawayAction and
            // SignUpForEventRoleAction.
            $locked = StandardGiveawayOccurrence::query()->lockForUpdate()->findOrFail($occurrence->id);

            $member = $this->syncMember->execute($locked->standardGiveaway->guild, $discordUserId, $discordUsername, displayName: $discordDisplayName);

            // Authoritative cutoff: independent of `status`.
            if ($locked->hasEnded()) {
                return StandardGiveawayEntryResult::closed();
            }

            $existing = StandardGiveawayEntry::query()
                ->where('standard_giveaway_occurrence_id', $locked->id)
                ->where('discord_member_id', $member->id)
                ->first();

            if ($existing) {
                return StandardGiveawayEntryResult::alreadyEntered();
            }

            if ($locked->requires_booster && ! $isBoosting) {
                return StandardGiveawayEntryResult::rejected('This giveaway is for server boosters only.');
            }

            if ($locked->required_role_ids !== [] && array_intersect($locked->required_role_ids, $discordRoleIds) === []) {
                return StandardGiveawayEntryResult::rejected("You don't have the required role to enter this giveaway.");
            }

            StandardGiveawayEntry::query()->create([
                'standard_giveaway_occurrence_id' => $locked->id,
                'discord_member_id' => $member->id,
            ]);

            return StandardGiveawayEntryResult::entered();
        });
    }
}
