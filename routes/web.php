<?php

use App\Http\Controllers\Auth\MsisdnLoginController;
use App\Http\Controllers\BidController;
use App\Http\Controllers\CheckinController;
use App\Http\Controllers\EventBidsController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HowToPlayController;
use App\Http\Controllers\LeaderboardBidsController;
use App\Http\Controllers\OpenBidsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RewardClaimController;
use App\Http\Controllers\SpinController;
use App\Http\Controllers\TaskCenterController;
use App\Http\Controllers\TermsController;
use App\Http\Controllers\TrendingBidsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/open-bids', [OpenBidsController::class, 'index'])->name('open-bids');
Route::get('/how-to-play', [HowToPlayController::class, 'index'])->name('how-to-play');
Route::get('/terms', [TermsController::class, 'index'])->name('terms');

Route::middleware('guest')->group(function () {
    Route::get('/login/{msisdn?}', [MsisdnLoginController::class, 'show'])
        ->name('login');

    Route::post('/login', [MsisdnLoginController::class, 'login'])
        ->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [MsisdnLoginController::class, 'logout'])
        ->name('logout');

    Route::get('/events', [EventBidsController::class, 'index'])->name('events');
    Route::get('/trending', [TrendingBidsController::class, 'index'])->name('trending');
    Route::get('/history', [HistoryController::class, 'index'])->name('history');
    Route::get('/leaderboard', [LeaderboardBidsController::class, 'index'])->name('leaderboard');
    Route::post('/bids/{bid}/place', [BidController::class, 'store'])->name('bids.place');
    Route::post('/bids/{bid}/review', [HistoryController::class, 'storeReview'])->name('bids.review');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');

    // Rewards & Task Center
    Route::get('/tasks', [TaskCenterController::class, 'index'])->name('tasks');
    Route::post('/checkin', [CheckinController::class, 'store'])->name('checkin');
    Route::post('/spin', [SpinController::class, 'store'])->name('spin');
    Route::post('/rewards/claim', [RewardClaimController::class, 'store'])->name('rewards.claim');
});

Route::fallback(function () {
    return redirect('/');
});
