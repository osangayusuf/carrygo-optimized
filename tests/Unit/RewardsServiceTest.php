<?php

use App\Models\User;
use App\Services\RewardsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    if (! Schema::hasTable('carrygo_active_points')) {
        Schema::create('carrygo_active_points', function ($table) {
            $table->id();
            $table->string('msisdn')->unique();
            $table->unsignedInteger('points')->default(0);
            $table->timestamp('updated_at')->nullable();
        });
    }
});

test('grantDailySpins sets spin balance to configured limit and does not accrue', function () {
    $userWithZero = User::factory()->create(['spins_balance' => 0]);
    $userWithOne = User::factory()->create(['spins_balance' => 1]);
    $userWithTwo = User::factory()->create(['spins_balance' => 2]);

    $service = app(RewardsService::class);
    $service->grantDailySpins();

    $userWithZero->refresh();
    $userWithOne->refresh();
    $userWithTwo->refresh();

    // With configured limit of 1:
    // User who had 0 should now have 1.
    // User who had 1 should still have 1 (not 2).
    // User who had 2 should now have 1 (reset/capped to 1).
    expect($userWithZero->spins_balance)->toBe(1)
        ->and($userWithOne->spins_balance)->toBe(1)
        ->and($userWithTwo->spins_balance)->toBe(1);
});

test('grantDailySpins honors custom free_spins_per_day configuration', function () {
    Config::set('rewards.spin_wheel.free_spins_per_day', 2);

    $userWithZero = User::factory()->create(['spins_balance' => 0]);
    $userWithOne = User::factory()->create(['spins_balance' => 1]);
    $userWithThree = User::factory()->create(['spins_balance' => 3]);

    $service = app(RewardsService::class);
    $service->grantDailySpins();

    $userWithZero->refresh();
    $userWithOne->refresh();
    $userWithThree->refresh();

    expect($userWithZero->spins_balance)->toBe(2)
        ->and($userWithOne->spins_balance)->toBe(2)
        ->and($userWithThree->spins_balance)->toBe(2);
});
