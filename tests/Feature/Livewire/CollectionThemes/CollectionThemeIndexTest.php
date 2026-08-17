<?php

declare(strict_types=1);

use App\Livewire\CollectionThemes\CollectionThemeIndex;
use App\Models\CollectionTheme;
use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;
use Livewire\Livewire;

it('lists themed collections for the guild with their item count', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    CollectionTheme::factory()->for($guild)->withItems(3)->create(['name' => 'Retro Arcade']);

    Livewire::actingAs($user)
        ->test(CollectionThemeIndex::class, ['guild' => $guild])
        ->assertSee('Retro Arcade')
        ->assertSee('3 items');
});

it('duplicates a theme and selects the duplicate', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $theme = CollectionTheme::factory()->for($guild)->withItems(2)->create(['name' => 'Retro Arcade']);

    $component = Livewire::actingAs($user)
        ->test(CollectionThemeIndex::class, ['guild' => $guild])
        ->call('duplicate', $theme->id);

    expect(CollectionTheme::query()->where('name', 'Retro Arcade (Copy)')->exists())->toBeTrue();

    $duplicate = CollectionTheme::query()->where('name', 'Retro Arcade (Copy)')->sole();
    $component->assertSet('selectedId', $duplicate->id);
});

it('refuses to duplicate a theme belonging to a different guild', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $otherTheme = CollectionTheme::factory()->withItems(1)->create();

    expect(fn () => Livewire::actingAs($user)
        ->test(CollectionThemeIndex::class, ['guild' => $guild])
        ->call('duplicate', $otherTheme->id)
    )->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect(CollectionTheme::query()->count())->toBe(1);
});

it('shows a theme\'s image in its tile when set, and the fallback glyph when not', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $withImage = CollectionTheme::factory()->for($guild)->withItems(1)->withImage()->create(['name' => 'Has Image']);
    CollectionTheme::factory()->for($guild)->withItems(1)->create(['name' => 'No Image']);

    Livewire::actingAs($user)
        ->test(CollectionThemeIndex::class, ['guild' => $guild])
        ->assertSee($withImage->image_url, false)
        ->assertSeeHtml('<svg');
});

it('shows only the selected theme\'s item-management UI in the detail panel', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $themeA = CollectionTheme::factory()->for($guild)->withItems(0)->create(['name' => 'Theme A']);
    $themeA->items()->create(['name' => 'Item A', 'sort_order' => 0]);
    $themeB = CollectionTheme::factory()->for($guild)->withItems(0)->create(['name' => 'Theme B']);
    $themeB->items()->create(['name' => 'Item B', 'sort_order' => 0]);

    $component = Livewire::actingAs($user)
        ->test(CollectionThemeIndex::class, ['guild' => $guild])
        ->assertSee('Select an item from the list')
        ->call('select', $themeA->id);

    $component->assertSee('Item A')->assertDontSee('Item B');

    $component->call('select', $themeB->id)
        ->assertSee('Item B')
        ->assertDontSee('Item A');
});

it('returns to the list-only view on deselect', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $theme = CollectionTheme::factory()->for($guild)->withItems(1)->create();

    Livewire::actingAs($user)
        ->test(CollectionThemeIndex::class, ['guild' => $guild])
        ->call('select', $theme->id)
        ->call('deselect')
        ->assertSee('Select an item from the list');
});

it('refuses to select a theme belonging to a different guild', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $otherGuild = Guild::factory()->create();
    $otherTheme = CollectionTheme::factory()->for($otherGuild)->withItems(1)->create();

    Livewire::actingAs($user)
        ->test(CollectionThemeIndex::class, ['guild' => $guild])
        ->call('select', $otherTheme->id)
        ->assertSee('Select an item from the list');
});

it('opening the create form deselects the current theme, and selecting a tile closes the create form', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $theme = CollectionTheme::factory()->for($guild)->withItems(1)->create();

    $component = Livewire::actingAs($user)
        ->test(CollectionThemeIndex::class, ['guild' => $guild])
        ->call('select', $theme->id)
        ->call('toggleCreateForm');

    $component->assertSet('selectedId', null)
        ->assertSeeLivewire('collection-themes.create-collection-theme');

    $component->call('select', $theme->id)
        ->assertSet('showCreateForm', false)
        ->assertDontSeeLivewire('collection-themes.create-collection-theme');
});

it('selects the newly created theme after submitting the create form', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();

    $component = Livewire::actingAs($user)->test(CollectionThemeIndex::class, ['guild' => $guild]);
    $component->call('toggleCreateForm');

    $theme = CollectionTheme::factory()->for($guild)->withItems(1)->create();
    $component->dispatch('collection-theme-created', themeId: $theme->id);

    $component->assertSet('showCreateForm', false)
        ->assertSet('selectedId', $theme->id);
});

it('denies mounting for a guild the user does not admin', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();

    Livewire::actingAs($user)
        ->test(CollectionThemeIndex::class, ['guild' => $guild])
        ->assertForbidden();
});
