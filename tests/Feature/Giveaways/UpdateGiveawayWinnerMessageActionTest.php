<?php

declare(strict_types=1);

use App\Actions\Giveaways\UpdateGiveawayWinnerMessageAction;
use App\Models\Giveaway;

it('sets the winner-message fields on a draft giveaway', function () {
    $giveaway = Giveaway::factory()->create();

    (new UpdateGiveawayWinnerMessageAction)->execute($giveaway, '123456', 'Congrats {winner}!');

    expect($giveaway->fresh()->winner_message_channel_id)->toBe('123456')
        ->and($giveaway->fresh()->winner_message_template)->toBe('Congrats {winner}!');
});

it('sets the winner-message fields on an active giveaway', function () {
    $giveaway = Giveaway::factory()->active()->create();

    (new UpdateGiveawayWinnerMessageAction)->execute($giveaway, '123456', 'Congrats {winner}!');

    expect($giveaway->fresh()->winner_message_channel_id)->toBe('123456')
        ->and($giveaway->fresh()->winner_message_template)->toBe('Congrats {winner}!');
});

it('sets the winner-message fields on a closed giveaway', function () {
    $giveaway = Giveaway::factory()->closed()->create();

    (new UpdateGiveawayWinnerMessageAction)->execute($giveaway, '123456', 'Congrats {winner}!');

    expect($giveaway->fresh()->winner_message_channel_id)->toBe('123456')
        ->and($giveaway->fresh()->winner_message_template)->toBe('Congrats {winner}!');
});

it('clears both fields when passed null', function () {
    $giveaway = Giveaway::factory()->withWinnerMessage('123456', 'Congrats {winner}!')->create();

    (new UpdateGiveawayWinnerMessageAction)->execute($giveaway, null, null);

    expect($giveaway->fresh()->winner_message_channel_id)->toBeNull()
        ->and($giveaway->fresh()->winner_message_template)->toBeNull();
});
