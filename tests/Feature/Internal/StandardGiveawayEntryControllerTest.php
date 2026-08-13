<?php

declare(strict_types=1);

use App\Models\StandardGiveawayEntry;
use App\Models\StandardGiveawayOccurrence;

it('processes an entry and returns entered', function () {
    $occurrence = StandardGiveawayOccurrence::factory()->posted()->create(['ends_at' => now()->addDay()]);

    $this->withHeaders(botAuthHeader())
        ->postJson("/internal/standard-giveaway-occurrences/{$occurrence->id}/entries", [
            'discord_user_id' => '111',
            'discord_username' => 'entrant',
        ])
        ->assertStatus(200)
        ->assertJsonPath('status', 'entered');

    expect(StandardGiveawayEntry::query()->count())->toBe(1);
});

it('rejects a booster-only occurrence entry when is_boosting is false', function () {
    $occurrence = StandardGiveawayOccurrence::factory()->posted()->create([
        'requires_booster' => true,
        'ends_at' => now()->addDay(),
    ]);

    $this->withHeaders(botAuthHeader())
        ->postJson("/internal/standard-giveaway-occurrences/{$occurrence->id}/entries", [
            'discord_user_id' => '111',
            'discord_username' => 'entrant',
            'is_boosting' => false,
        ])
        ->assertStatus(200)
        ->assertJsonPath('status', 'rejected');
});

it('accepts role-restricted entries when discord_role_ids intersects', function () {
    $occurrence = StandardGiveawayOccurrence::factory()->posted()->create([
        'required_role_ids' => ['role-a'],
        'ends_at' => now()->addDay(),
    ]);

    $this->withHeaders(botAuthHeader())
        ->postJson("/internal/standard-giveaway-occurrences/{$occurrence->id}/entries", [
            'discord_user_id' => '111',
            'discord_username' => 'entrant',
            'discord_role_ids' => ['role-a', 'role-z'],
        ])
        ->assertStatus(200)
        ->assertJsonPath('status', 'entered');
});

it('returns closed for an ended occurrence', function () {
    $occurrence = StandardGiveawayOccurrence::factory()->posted()->create(['ends_at' => now()->subMinute()]);

    $this->withHeaders(botAuthHeader())
        ->postJson("/internal/standard-giveaway-occurrences/{$occurrence->id}/entries", [
            'discord_user_id' => '111',
            'discord_username' => 'entrant',
        ])
        ->assertStatus(200)
        ->assertJsonPath('status', 'closed');
});

it('rejects entry requests without a valid bot token', function () {
    $occurrence = StandardGiveawayOccurrence::factory()->posted()->create(['ends_at' => now()->addDay()]);

    $this->postJson("/internal/standard-giveaway-occurrences/{$occurrence->id}/entries", [
        'discord_user_id' => '111',
        'discord_username' => 'entrant',
    ])->assertStatus(401);
});

it('validates required fields', function () {
    $occurrence = StandardGiveawayOccurrence::factory()->create();

    $this->withHeaders(botAuthHeader())
        ->postJson("/internal/standard-giveaway-occurrences/{$occurrence->id}/entries", [])
        ->assertStatus(422);
});
