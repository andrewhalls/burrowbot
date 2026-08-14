<?php

declare(strict_types=1);

use App\Actions\StandardGiveaways\UpdateStandardGiveawayAction;
use App\Actions\StandardGiveaways\UpdateStandardGiveawayStatusAction;
use App\Models\CollectionThemeItem;
use App\Models\StandardGiveaway;
use App\Models\StandardGiveawayOccurrence;
use App\Models\StandardGiveawayPrizeItem;
use App\Models\StandardGiveawayRequiredRole;
use Illuminate\Support\Facades\Storage;

it('updates giveaway fields without touching existing occurrences', function () {
    $giveaway = StandardGiveaway::factory()->create(['title' => 'Old Title']);
    $occurrence = StandardGiveawayOccurrence::factory()->create([
        'standard_giveaway_id' => $giveaway->id,
        'title' => 'Old Title',
    ]);

    (new UpdateStandardGiveawayAction)->execute($giveaway, ['title' => 'New Title']);

    expect($giveaway->fresh()->title)->toBe('New Title')
        ->and($occurrence->fresh()->title)->toBe('Old Title');
});

it('leaves already-generated occurrences\' images unchanged and deletes the orphaned old file when replaced', function () {
    Storage::fake('public');
    Storage::disk('public')->put('standard-giveaway-images/old.jpg', 'old-bytes');
    Storage::disk('public')->put('standard-giveaway-images/new.jpg', 'new-bytes');

    $giveaway = StandardGiveaway::factory()->create(['image_path' => 'standard-giveaway-images/old.jpg']);
    $occurrence = StandardGiveawayOccurrence::factory()->create([
        'standard_giveaway_id' => $giveaway->id,
        'image_path' => 'standard-giveaway-images/old.jpg',
    ]);

    (new UpdateStandardGiveawayAction)->execute($giveaway, ['image_path' => 'standard-giveaway-images/new.jpg']);

    expect($giveaway->fresh()->image_path)->toBe('standard-giveaway-images/new.jpg')
        ->and($occurrence->fresh()->image_path)->toBe('standard-giveaway-images/old.jpg');

    // The old file is still referenced by the already-generated occurrence,
    // so it must survive this edit (design.md Decision 2, revised).
    Storage::disk('public')->assertExists('standard-giveaway-images/old.jpg');
});

it('deletes the old image file once no occurrence references it anymore', function () {
    Storage::fake('public');
    Storage::disk('public')->put('standard-giveaway-images/old.jpg', 'old-bytes');
    Storage::disk('public')->put('standard-giveaway-images/new.jpg', 'new-bytes');

    $giveaway = StandardGiveaway::factory()->create(['image_path' => 'standard-giveaway-images/old.jpg']);

    (new UpdateStandardGiveawayAction)->execute($giveaway, ['image_path' => 'standard-giveaway-images/new.jpg']);

    Storage::disk('public')->assertMissing('standard-giveaway-images/old.jpg');
});

it('replaces the prize item set when prize_item_ids is present', function () {
    $giveaway = StandardGiveaway::factory()->create();
    $oldItem = CollectionThemeItem::factory()->create();
    StandardGiveawayPrizeItem::factory()->for($giveaway)->create(['collection_theme_item_id' => $oldItem->id]);
    $newItemA = CollectionThemeItem::factory()->create();
    $newItemB = CollectionThemeItem::factory()->create();

    (new UpdateStandardGiveawayAction)->execute($giveaway, [
        'prize_item_ids' => [$newItemA->id, $newItemB->id],
    ]);

    expect($giveaway->prizeItems()->pluck('collection_theme_item_id')->all())
        ->toEqualCanonicalizing([$newItemA->id, $newItemB->id]);
});

it('replaces the required role set when required_role_ids is present', function () {
    $giveaway = StandardGiveaway::factory()->create();
    StandardGiveawayRequiredRole::factory()->for($giveaway)->create(['discord_role_id' => 'old-role']);

    (new UpdateStandardGiveawayAction)->execute($giveaway, [
        'required_role_ids' => ['role-a', 'role-b'],
    ]);

    expect($giveaway->requiredRoles()->pluck('discord_role_id')->all())
        ->toEqualCanonicalizing(['role-a', 'role-b']);
});

it('leaves prize items and required roles untouched when omitted from the update attributes', function () {
    $giveaway = StandardGiveaway::factory()->create();
    $item = CollectionThemeItem::factory()->create();
    StandardGiveawayPrizeItem::factory()->for($giveaway)->create(['collection_theme_item_id' => $item->id]);
    StandardGiveawayRequiredRole::factory()->for($giveaway)->create(['discord_role_id' => 'role-a']);

    (new UpdateStandardGiveawayAction)->execute($giveaway, ['title' => 'New Title']);

    expect($giveaway->prizeItems()->pluck('collection_theme_item_id')->all())->toBe([$item->id])
        ->and($giveaway->requiredRoles()->pluck('discord_role_id')->all())->toBe(['role-a']);
});

it('leaves an already-generated occurrence\'s snapshotted prize items and roles unaffected by a later edit', function () {
    $giveaway = StandardGiveaway::factory()->create();
    $oldItem = CollectionThemeItem::factory()->create();
    $occurrence = StandardGiveawayOccurrence::factory()->create([
        'standard_giveaway_id' => $giveaway->id,
        'prize_item_ids' => [$oldItem->id],
        'required_role_ids' => ['old-role'],
    ]);
    $newItem = CollectionThemeItem::factory()->create();

    (new UpdateStandardGiveawayAction)->execute($giveaway, [
        'prize_item_ids' => [$newItem->id],
        'required_role_ids' => ['new-role'],
    ]);

    expect($occurrence->fresh()->prize_item_ids)->toBe([$oldItem->id])
        ->and($occurrence->fresh()->required_role_ids)->toBe(['old-role']);
});

it('transitions giveaway status', function () {
    $giveaway = StandardGiveaway::factory()->create(['status' => StandardGiveaway::STATUS_ACTIVE]);

    (new UpdateStandardGiveawayStatusAction)->execute($giveaway, StandardGiveaway::STATUS_PAUSED);

    expect($giveaway->fresh()->status)->toBe(StandardGiveaway::STATUS_PAUSED);
});

it('rejects an invalid status', function () {
    $giveaway = StandardGiveaway::factory()->create();

    expect(fn () => (new UpdateStandardGiveawayStatusAction)->execute($giveaway, 'bogus'))
        ->toThrow(InvalidArgumentException::class);
});
