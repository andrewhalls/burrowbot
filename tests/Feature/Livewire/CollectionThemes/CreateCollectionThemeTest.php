<?php

declare(strict_types=1);

use App\Livewire\CollectionThemes\CreateCollectionTheme;
use App\Models\CollectionTheme;
use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('creates a theme and dispatches an event on success', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();

    Livewire::actingAs($user)
        ->test(CreateCollectionTheme::class, ['guild' => $guild])
        ->set('name', 'Retro Arcade')
        ->set('items', ['Joystick', 'Cartridge'])
        ->call('save')
        ->assertDispatched('collection-theme-created')
        ->assertHasNoErrors();

    expect(CollectionTheme::query()->where('name', 'Retro Arcade')->exists())->toBeTrue();
});

it('shows a validation error and creates nothing when every item is blank', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();

    Livewire::actingAs($user)
        ->test(CreateCollectionTheme::class, ['guild' => $guild])
        ->set('name', 'Empty Theme')
        ->set('items', ['', ''])
        ->call('save')
        ->assertHasErrors('items');

    expect(CollectionTheme::query()->count())->toBe(0);
});

it('creates a theme with an image', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();

    Livewire::actingAs($user)
        ->test(CreateCollectionTheme::class, ['guild' => $guild])
        ->set('name', 'Retro Arcade')
        ->set('items', ['Joystick'])
        ->set('image', UploadedFile::fake()->image('theme.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $theme = CollectionTheme::query()->where('name', 'Retro Arcade')->firstOrFail();

    expect($theme->image_path)->not->toBeNull();
    Storage::disk('public')->assertExists($theme->image_path);
});

it('denies mounting the component for a guild the user does not admin', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateCollectionTheme::class, ['guild' => $guild])
        ->assertForbidden();
});
