<?php

declare(strict_types=1);

use App\Livewire\Giveaways\EditGiveawayWinnerMessage;
use App\Models\Giveaway;
use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;
use Livewire\Livewire;

function actingGiveawayAdminForWinnerMessage(Guild $guild): User
{
    $user = User::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();

    return $user;
}

it('pre-fills from the existing giveaway', function () {
    $guild = Guild::factory()->create();
    $giveaway = Giveaway::factory()->for($guild)->withWinnerMessage('987654', 'Congrats {winner}!')->create();
    $user = actingGiveawayAdminForWinnerMessage($guild);

    Livewire::actingAs($user)
        ->test(EditGiveawayWinnerMessage::class, ['giveaway' => $giveaway])
        ->assertSet('winnerMessageChannelId', '987654')
        ->assertSet('winnerMessageTemplate', 'Congrats {winner}!');
});

it('saves changes regardless of giveaway status', function () {
    $guild = Guild::factory()->create();
    $giveaway = Giveaway::factory()->for($guild)->closed()->create();
    $user = actingGiveawayAdminForWinnerMessage($guild);

    Livewire::actingAs($user)
        ->test(EditGiveawayWinnerMessage::class, ['giveaway' => $giveaway])
        ->set('winnerMessageChannelId', '987654')
        ->set('winnerMessageTemplate', 'Congrats {winner}!')
        ->call('save')
        ->assertDispatched('giveaway-winner-message-updated')
        ->assertHasNoErrors();

    expect($giveaway->fresh()->winner_message_channel_id)->toBe('987654')
        ->and($giveaway->fresh()->winner_message_template)->toBe('Congrats {winner}!');
});

it('rejects mounting when the guild\'s flag is disabled', function () {
    $guild = Guild::factory()->withPopupGiveawayWinnerMessagesDisabled()->create();
    $giveaway = Giveaway::factory()->for($guild)->create();
    $user = actingGiveawayAdminForWinnerMessage($guild);

    Livewire::actingAs($user)
        ->test(EditGiveawayWinnerMessage::class, ['giveaway' => $giveaway])
        ->assertForbidden();
});

it('denies mounting for a guild the user does not admin', function () {
    $giveaway = Giveaway::factory()->create();
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(EditGiveawayWinnerMessage::class, ['giveaway' => $giveaway])
        ->assertForbidden();
});
