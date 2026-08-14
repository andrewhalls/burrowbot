<?php

declare(strict_types=1);

use App\Models\DiscordMember;
use App\Models\EventOccurrence;
use App\Models\EventRole;
use App\Models\EventRoleSet;

it('signs up for a role and returns confirmed', function () {
    $roleSet = EventRoleSet::factory()->create();
    $role = EventRole::factory()->for($roleSet, 'eventRoleSet')->create();
    $occurrence = EventOccurrence::factory()->create([
        'event_role_set_id' => $roleSet->id,
        'scheduled_start_at' => now()->addDay(),
    ]);

    $this->withHeaders(botAuthHeader())
        ->postJson("/internal/event-occurrences/{$occurrence->id}/signups", [
            'discord_user_id' => '111',
            'discord_username' => 'entrant',
            'event_role_id' => $role->id,
        ])
        ->assertStatus(200)
        ->assertJsonPath('status', 'confirmed')
        ->assertJsonPath('role.id', $role->id);
});

it('marks not attending when no role is provided', function () {
    $occurrence = EventOccurrence::factory()->create(['scheduled_start_at' => now()->addDay()]);

    $this->withHeaders(botAuthHeader())
        ->postJson("/internal/event-occurrences/{$occurrence->id}/signups", [
            'discord_user_id' => '111',
            'discord_username' => 'entrant',
        ])
        ->assertStatus(200)
        ->assertJsonPath('status', 'not_attending');
});

it('records the display name on the synced member when provided', function () {
    $occurrence = EventOccurrence::factory()->create(['scheduled_start_at' => now()->addDay()]);

    $this->withHeaders(botAuthHeader())
        ->postJson("/internal/event-occurrences/{$occurrence->id}/signups", [
            'discord_user_id' => '111',
            'discord_username' => 'entrant',
            'discord_display_name' => 'Entrant Display Name',
        ])
        ->assertStatus(200);

    $member = DiscordMember::query()->where('discord_user_id', '111')->sole();
    expect($member->display_name)->toBe('Entrant Display Name');
});

it('rejects signup requests without a valid bot token', function () {
    $occurrence = EventOccurrence::factory()->create();

    $this->postJson("/internal/event-occurrences/{$occurrence->id}/signups", [
        'discord_user_id' => '111',
        'discord_username' => 'entrant',
    ])->assertStatus(401);
});

it('validates required fields', function () {
    $occurrence = EventOccurrence::factory()->create();

    $this->withHeaders(botAuthHeader())
        ->postJson("/internal/event-occurrences/{$occurrence->id}/signups", [])
        ->assertStatus(422);
});
