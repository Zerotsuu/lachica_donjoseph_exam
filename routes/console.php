<?php
//AI GENERATED CODE
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// SCHEDULE SANCTUM TOKEN MAINTENANCE
Schedule::command('sanctum:prune-expired --hours=24')->daily();

Schedule::command('sanctum:maintenance --prune-expired')
    ->daily()
    ->at('02:00')
    ->name('sanctum-prune-expired')
    ->description('Remove expired Sanctum tokens daily');

Schedule::command('sanctum:maintenance --prune-old --days=30')
    ->weekly()
    ->sundays()
    ->at('03:00')
    ->name('sanctum-prune-old')
    ->description('Remove old Sanctum tokens weekly');

Schedule::command('sanctum:maintenance --limit-tokens')
    ->daily()
    ->at('04:00')
    ->name('sanctum-limit-tokens')
    ->description('Enforce token limits per user daily');

// WEEKLY STATS
Schedule::command('sanctum:maintenance --dry-run')
    ->weekly()
    ->mondays()
    ->at('09:00')
    ->name('sanctum-stats')
    ->description('Log token statistics weekly');
