<?php

declare(strict_types=1);

use App\Models\CollectionTheme;
use App\Models\DiscordMember;
use App\Models\Giveaway;
use App\Models\GiveawayEntry;

it('processes a join request and returns the won item', function () {
    $theme = CollectionTheme::factory()->withItems(2)->create();
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')->active()->create();

    $response = $this->withHeaders(botAuthHeader())
        ->postJson("/internal/giveaways/{$giveaway->id}/entries", [
            'discord_user_id' => '777',
            'discord_username' => 'entrant',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('status', 'won')
        ->assertJsonStructure(['status', 'item' => ['id', 'name', 'image_url']]);

    expect(GiveawayEntry::query()->count())->toBe(1);
});

it('includes the assigned item\'s image url when it has one', function () {
    $theme = CollectionTheme::factory()->withItems(0)->create();
    $theme->items()->create(['name' => 'Golden Ticket', 'image_path' => 'theme-item-images/abc.jpg', 'sort_order' => 0]);
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')->active()->create();

    $response = $this->withHeaders(botAuthHeader())
        ->postJson("/internal/giveaways/{$giveaway->id}/entries", [
            'discord_user_id' => '777',
            'discord_username' => 'entrant',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('item.image_url', fn ($url) => str_contains($url, 'theme-item-images/abc.jpg'));
});

it('leaves the assigned item\'s image url null when it has none', function () {
    $theme = CollectionTheme::factory()->withItems(1)->create();
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')->active()->create();

    $response = $this->withHeaders(botAuthHeader())
        ->postJson("/internal/giveaways/{$giveaway->id}/entries", [
            'discord_user_id' => '777',
            'discord_username' => 'entrant',
        ]);

    $response->assertStatus(200)->assertJsonPath('item.image_url', null);
});

it('returns expired for a giveaway past its end time', function () {
    $theme = CollectionTheme::factory()->withItems(1)->create();
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')->closed()->create();

    $this->withHeaders(botAuthHeader())
        ->postJson("/internal/giveaways/{$giveaway->id}/entries", [
            'discord_user_id' => '888',
            'discord_username' => 'latecomer',
        ])
        ->assertStatus(200)
        ->assertJsonPath('status', 'expired')
        ->assertJsonPath('item', null);
});

it('records the display name on the synced member when provided', function () {
    $theme = CollectionTheme::factory()->withItems(1)->create();
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')->active()->create();

    $this->withHeaders(botAuthHeader())
        ->postJson("/internal/giveaways/{$giveaway->id}/entries", [
            'discord_user_id' => '777',
            'discord_username' => 'entrant',
            'discord_display_name' => 'Entrant Display Name',
        ])
        ->assertStatus(200);

    $member = DiscordMember::query()->where('discord_user_id', '777')->sole();
    expect($member->display_name)->toBe('Entrant Display Name');
});

it('rejects join requests without a valid bot token', function () {
    $theme = CollectionTheme::factory()->withItems(1)->create();
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')->active()->create();

    $this->postJson("/internal/giveaways/{$giveaway->id}/entries", [
        'discord_user_id' => '999',
        'discord_username' => 'someone',
    ])->assertStatus(401);
});

it('validates required fields', function () {
    $giveaway = Giveaway::factory()->active()->create();

    $this->withHeaders(botAuthHeader())
        ->postJson("/internal/giveaways/{$giveaway->id}/entries", [])
        ->assertStatus(422);
});
