<?php

use App\Console\Commands\CloseExpiredGiveaways;
use App\Console\Commands\CloseExpiredStandardGiveawayOccurrences;
use App\Console\Commands\GenerateEventOccurrences;
use App\Console\Commands\GenerateStandardGiveawayOccurrences;
use App\Console\Commands\PostDueEventOccurrences;
use App\Console\Commands\PostDueGiveaways;
use App\Console\Commands\PostDueStandardGiveawayOccurrences;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(CloseExpiredGiveaways::class)->everyMinute();
Schedule::command(PostDueGiveaways::class)->everyMinute();
Schedule::command(GenerateEventOccurrences::class)->hourly();
Schedule::command(PostDueEventOccurrences::class)->everyMinute();
Schedule::command(GenerateStandardGiveawayOccurrences::class)->hourly();
Schedule::command(PostDueStandardGiveawayOccurrences::class)->everyMinute();
Schedule::command(CloseExpiredStandardGiveawayOccurrences::class)->everyMinute();
