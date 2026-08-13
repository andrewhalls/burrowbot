<?php

declare(strict_types=1);

use App\Actions\Giveaways\FulfillGiveawayEntryAction;
use App\Models\GiveawayEntry;
use App\Models\User;

it('records who fulfilled an entry and when', function () {
    $entry = GiveawayEntry::factory()->create();
    $staff = User::factory()->create();

    $updated = (new FulfillGiveawayEntryAction)->execute($entry, $staff);

    expect($updated->isFulfilled())->toBeTrue()
        ->and($updated->fulfilled_by_user_id)->toBe($staff->id)
        ->and($updated->fulfilled_at)->not->toBeNull();
});
