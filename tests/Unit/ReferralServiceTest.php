<?php

use App\Models\RewardWallet;
use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

test('processAfterLogin awards referee and referrer', function () {
    $referrer = User::factory()->create([
        'msisdn' => '2348011111111',
        'referral_code' => 'REFCODE1',
        'email' => null,
        'password' => null,
    ]);

    $referee = User::factory()->create([
        'msisdn' => '2348022222222',
        'referral_code' => 'REFCODE2',
        'email' => null,
        'password' => null,
    ]);

    $service = app(ReferralService::class);
    $result = $service->processAfterLogin($referee, 'REFCODE1');

    expect($result['processed'])->toBeTrue();

    $referee->refresh();

    expect($referee->referred_by_user_id)->toBe($referrer->id)
        ->and(RewardWallet::where('msisdn', '2348022222222')->where('source', 'referral')->sum('points'))->toBe(100)
        ->and(RewardWallet::where('msisdn', '2348011111111')->where('source', 'referral')->sum('points'))->toBe(50);
});

test('processAfterLogin is idempotent when already referred', function () {
    $referrer = User::factory()->create([
        'msisdn' => '2348011111111',
        'referral_code' => 'REFCODE1',
        'email' => null,
        'password' => null,
    ]);

    $referee = User::factory()->create([
        'msisdn' => '2348022222222',
        'referral_code' => 'REFCODE2',
        'referred_by_user_id' => $referrer->id,
        'email' => null,
        'password' => null,
    ]);

    RewardWallet::create([
        'msisdn' => '2348022222222',
        'source' => 'referral',
        'description' => 'Existing',
        'points' => 100,
    ]);

    $service = app(ReferralService::class);
    $result = $service->processAfterLogin($referee, 'REFCODE1');

    expect($result['processed'])->toBeFalse()
        ->and($result['reason'])->toBe('already_referred')
        ->and(RewardWallet::where('msisdn', '2348022222222')->where('source', 'referral')->count())->toBe(1);
});

test('processAfterLogin rejects self referral', function () {
    $user = User::factory()->create([
        'msisdn' => '2348033333333',
        'referral_code' => 'MYCODE12',
        'email' => null,
        'password' => null,
    ]);

    $result = app(ReferralService::class)->processAfterLogin($user, 'MYCODE12');

    expect($result['processed'])->toBeFalse()
        ->and($result['reason'])->toBe('self_referral');
});
