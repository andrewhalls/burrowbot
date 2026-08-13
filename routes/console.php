<?php

use App\Console\Commands\CloseExpiredGiveaways;
use App\Console\Commands\GenerateEventOccurrences;
use App\Console\Commands\PostDueEventOccurrences;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(CloseExpiredGiveaways::class)->everyMinute();
Schedule::command(GenerateEventOccurrences::class)->hourly();
Schedule::command(PostDueEventOccurrences::class)->everyMinute();
