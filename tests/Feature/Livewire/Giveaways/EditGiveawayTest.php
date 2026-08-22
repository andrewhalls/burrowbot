<?php

declare(strict_types=1);

use App\Livewire\Giveaways\EditGiveaway;
use App\Models\CollectionTheme;
use App\Models\Giveaway;
use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function actingGiveawayAdminFor(Guild $guild): User
{
    $user = User::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();

    return $user;
}

it('pre-fills the form from the existing draft giveaway', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->create();
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')->for($guild)
        ->withDescription('Original description')
        ->create(['channel_id' => '999888777', 'duration_minutes' => 45]);
    $user = actingGiveawayAdminFor($guild);

    Livewire::actingAs($user)
        ->test(EditGiveaway::class, ['giveaway' => $giveaway])
        ->assertSet('channelId', '999888777')
        ->assertSet('collectionThemeId', $theme->id)
        ->assertSet('durationMinutes', 45)
        ->assertSet('description', 'Original description');
});

it('pre-fills the winner-message fields from the existing giveaway', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->create();
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')->for($guild)
        ->withWinnerMessage('987654', 'Congrats {winner}!')
        ->create();
    $user = actingGiveawayAdminFor($guild);

    Livewire::actingAs($user)
        ->test(EditGiveaway::class, ['giveaway' => $giveaway])
        ->assertSet('winnerMessageChannelId', '987654')
        ->assertSet('winnerMessageTemplate', 'Congrats {winner}!');
});

it('saves winner-message field changes to a draft giveaway', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->create();
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')->for($guild)->create();
    $user = actingGiveawayAdminFor($guild);

    Livewire::actingAs($user)
        ->test(EditGiveaway::class, ['giveaway' => $giveaway])
        ->set('winnerMessageChannelId', '987654')
        ->set('winnerMessageTemplate', 'Congrats {winner}!')
        ->call('save')
        ->assertHasNoErrors();

    expect($giveaway->fresh()->winner_message_channel_id)->toBe('987654')
        ->and($giveaway->fresh()->winner_message_template)->toBe('Congrats {winner}!');
});

it('rejects setting only one of the winner-message fields', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->create();
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')->for($guild)->create();
    $user = actingGiveawayAdminFor($guild);

    Livewire::actingAs($user)
        ->test(EditGiveaway::class, ['giveaway' => $giveaway])
        ->set('winnerMessageChannelId', '987654')
        ->call('save')
        ->assertHasErrors('winnerMessageTemplate');

    expect($giveaway->fresh()->winner_message_channel_id)->toBeNull();
});

it('hides the winner-message section when the guild\'s flag is disabled', function () {
    $guild = Guild::factory()->withPopupGiveawayWinnerMessagesDisabled()->create();
    $theme = CollectionTheme::factory()->for($guild)->create();
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')->for($guild)->create();
    $user = actingGiveawayAdminFor($guild);

    Livewire::actingAs($user)
        ->test(EditGiveaway::class, ['giveaway' => $giveaway])
        ->assertDontSee('Per-winner message');
});

it('leaves already-saved winner-message fields untouched when saving while the guild\'s flag is disabled', function () {
    $guild = Guild::factory()->withPopupGiveawayWinnerMessagesDisabled()->create();
    $theme = CollectionTheme::factory()->for($guild)->create();
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')->for($guild)
        ->withWinnerMessage('987654', 'Congrats {winner}!')
        ->create();
    $user = actingGiveawayAdminFor($guild);

    Livewire::actingAs($user)
        ->test(EditGiveaway::class, ['giveaway' => $giveaway])
        ->set('description', 'Updated description')
        ->call('save')
        ->assertHasNoErrors();

    expect($giveaway->fresh()->winner_message_channel_id)->toBe('987654')
        ->and($giveaway->fresh()->winner_message_template)->toBe('Congrats {winner}!')
        ->and($giveaway->fresh()->description)->toBe('Updated description');
});

it('saves changes to a draft giveaway', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->create();
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')->for($guild)->create(['duration_minutes' => 15]);
    $user = actingGiveawayAdminFor($guild);

    Livewire::actingAs($user)
        ->test(EditGiveaway::class, ['giveaway' => $giveaway])
        ->set('durationMinutes', 60)
        ->set('description', 'Updated description')
        ->call('save')
        ->assertDispatched('giveaway-updated')
        ->assertHasNoErrors();

    expect($giveaway->fresh()->duration_minutes)->toBe(60)
        ->and($giveaway->fresh()->description)->toBe('Updated description');
});

it('replaces the image when a new one is uploaded, and keeps the existing one otherwise', function () {
    Storage::fake('public');

    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->create();
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')->for($guild)
        ->withImage('giveaway-images/original.jpg')
        ->create();
    $user = actingGiveawayAdminFor($guild);

    Livewire::actingAs($user)
        ->test(EditGiveaway::class, ['giveaway' => $giveaway])
        ->set('image', UploadedFile::fake()->image('new.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $updated = $giveaway->fresh();
    expect($updated->image_path)->not->toBe('giveaway-images/original.jpg');
    Storage::disk('public')->assertExists($updated->image_path);
});

it('rejects editing once the giveaway is no longer a draft', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->create();
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')->for($guild)->active()->create();
    $user = actingGiveawayAdminFor($guild);

    Livewire::actingAs($user)
        ->test(EditGiveaway::class, ['giveaway' => $giveaway])
        ->set('durationMinutes', 999)
        ->call('save')
        ->assertHasErrors('collectionThemeId');

    expect($giveaway->fresh()->duration_minutes)->not->toBe(999);
});

it('denies mounting for a guild the user does not admin', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->create();
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')->for($guild)->create();
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(EditGiveaway::class, ['giveaway' => $giveaway])
        ->assertForbidden();
});
