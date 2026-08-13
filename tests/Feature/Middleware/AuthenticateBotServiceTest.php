<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Config::set('discord.service_token', 'the-correct-token');

    Route::middleware('bot.auth')->get('/__test/internal-ping', fn () => response()->json(['ok' => true]));
});

it('rejects a request with no bearer token', function () {
    $this->getJson('/__test/internal-ping')
        ->assertStatus(401);
});

it('rejects a request with the wrong bearer token', function () {
    $this->withHeader('Authorization', 'Bearer wrong-token')
        ->getJson('/__test/internal-ping')
        ->assertStatus(401);
});

it('accepts a request with the correct bearer token', function () {
    $this->withHeader('Authorization', 'Bearer the-correct-token')
        ->getJson('/__test/internal-ping')
        ->assertStatus(200)
        ->assertJson(['ok' => true]);
});

it('rejects every request when no service token is configured', function () {
    Config::set('discord.service_token', null);

    $this->withHeader('Authorization', 'Bearer anything')
        ->getJson('/__test/internal-ping')
        ->assertStatus(401);
});
