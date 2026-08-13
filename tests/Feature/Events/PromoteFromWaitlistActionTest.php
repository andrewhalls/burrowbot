<?php

declare(strict_types=1);

use App\Actions\Events\PromoteFromWaitlistAction;
use App\Models\EventOccurrence;
use App\Models\EventRole;
use App\Models\EventRoleSignup;

it('promotes the oldest waitlisted signup for the role', function () {
    $role = EventRole::factory()->waitlisted(1)->create();
    $occurrence = EventOccurrence::factory()->create(['event_role_set_id' => $role->event_role_set_id]);

    $older = EventRoleSignup::factory()
        ->for($occurrence, 'eventOccurrence')
        ->for($role, 'eventRole')
        ->waitlisted()
        ->create(['created_at' => now()->subMinutes(10)]);

    $newer = EventRoleSignup::factory()
        ->for($occurrence, 'eventOccurrence')
        ->for($role, 'eventRole')
        ->waitlisted()
        ->create(['created_at' => now()->subMinute()]);

    (new PromoteFromWaitlistAction)->execute($occurrence, $role);

    expect($older->fresh()->is_waitlisted)->toBeFalse()
        ->and($newer->fresh()->is_waitlisted)->toBeTrue();
});

it('does nothing when no one is waitlisted for the role', function () {
    $role = EventRole::factory()->waitlisted(1)->create();
    $occurrence = EventOccurrence::factory()->create(['event_role_set_id' => $role->event_role_set_id]);

    // Should not throw.
    (new PromoteFromWaitlistAction)->execute($occurrence, $role);

    expect(EventRoleSignup::query()->count())->toBe(0);
});

it('only promotes for the specified role, not other roles on the same occurrence', function () {
    $roleA = EventRole::factory()->waitlisted(1)->create();
    $roleB = EventRole::factory()->for($roleA->eventRoleSet, 'eventRoleSet')->waitlisted(1)->create();
    $occurrence = EventOccurrence::factory()->create(['event_role_set_id' => $roleA->event_role_set_id]);

    $waitlistedOnB = EventRoleSignup::factory()
        ->for($occurrence, 'eventOccurrence')
        ->for($roleB, 'eventRole')
        ->waitlisted()
        ->create();

    (new PromoteFromWaitlistAction)->execute($occurrence, $roleA);

    expect($waitlistedOnB->fresh()->is_waitlisted)->toBeTrue();
});
