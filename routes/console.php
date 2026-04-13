<?php

use App\Services\BidService;
use App\Services\RewardsService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(fn () => app(RewardsService::class)->grantDailySpins())
    ->dailyAt('00:00')
    ->name('grant-daily-spins')
    ->withoutOverlapping();

Schedule::call(fn () => app(RewardsService::class)->processWeeklyLeaderboard())
    ->weeklyOn(0, '23:55')
    ->name('weekly-leaderboard')
    ->withoutOverlapping();

Schedule::call(fn () => app(BidService::class)->closeExpiredBids())
    ->everyMinute()
    ->name('close-expired-bids')
    ->withoutOverlapping(10);
