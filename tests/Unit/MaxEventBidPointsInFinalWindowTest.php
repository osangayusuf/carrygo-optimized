<?php

use App\Enums\BidStatus;
use App\Models\Bid;
use App\Models\BidActive;
use App\Models\BidEntry;
use App\Rules\MaxEventBidPointsInFinalWindow;
use App\Services\BidExpiryService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['carrygo_bid', 'carrygo_bid_active', 'carrygo_bid_entry'] as $table) {
        if (Schema::hasTable($table)) {
            continue;
        }

        match ($table) {
            'carrygo_bid' => Schema::create('carrygo_bid', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('open_points')->default(100);
                $table->unsignedInteger('open_date')->default(48);
                $table->tinyInteger('status')->default(0);
                $table->boolean('event_special')->default(false);
                $table->timestamp('created_at')->nullable();
            }),
            'carrygo_bid_active' => Schema::create('carrygo_bid_active', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bidid');
                $table->unsignedInteger('points')->default(0);
                $table->tinyInteger('status')->default(0);
                $table->timestamp('created_at')->nullable();
            }),
            'carrygo_bid_entry' => Schema::create('carrygo_bid_entry', function (Blueprint $table) {
                $table->id();
                $table->string('msisdn');
                $table->unsignedBigInteger('bidid');
                $table->unsignedInteger('points');
                $table->timestamp('created_at')->nullable();
            }),
            default => null,
        };
    }
});

function validateBidPoints(Bid $bid, string $msisdn, int $points): bool
{
    $validator = Validator::make(
        ['points' => $points],
        ['points' => [new MaxEventBidPointsInFinalWindow($bid, $msisdn)]]
    );

    return $validator->passes();
}

test('rejects bids exceeding 1000 points in final window', function () {
    Carbon::setTestNow('2026-05-21 12:00:00');

    $bid = Bid::create([
        'open_points' => 100,
        'open_date' => 48,
        'status' => BidStatus::Live,
        'event_special' => true,
    ]);

    $bidActive = new BidActive([
        'bidid' => $bid->id,
        'points' => 500,
        'status' => 0,
    ]);
    $bidActive->created_at = Carbon::parse('2026-05-20 11:00:00');
    $bidActive->save();

    $entry = new BidEntry([
        'msisdn' => '2348099999999',
        'bidid' => $bid->id,
        'points' => 600,
    ]);
    $entry->created_at = Carbon::parse('2026-05-21 11:30:00');
    $entry->save();

    expect(validateBidPoints($bid->fresh(), '2348099999999', 500))->toBeFalse();

    Carbon::setTestNow();
});

test('allows bids within 1000 point final window allowance', function () {
    Carbon::setTestNow('2026-05-21 12:00:00');

    $bid = Bid::create([
        'open_points' => 100,
        'open_date' => 48,
        'status' => BidStatus::Live,
        'event_special' => true,
    ]);

    $bidActive = new BidActive([
        'bidid' => $bid->id,
        'points' => 500,
        'status' => 0,
    ]);
    $bidActive->created_at = Carbon::parse('2026-05-20 11:00:00');
    $bidActive->save();

    $entry = new BidEntry([
        'msisdn' => '2348099999999',
        'bidid' => $bid->id,
        'points' => 600,
    ]);
    $entry->created_at = Carbon::parse('2026-05-21 11:30:00');
    $entry->save();

    expect(validateBidPoints($bid->fresh(), '2348099999999', 400))->toBeTrue();

    Carbon::setTestNow();
});

test('ignores bids placed before the final window', function () {
    Carbon::setTestNow('2026-05-21 12:00:00');

    $bid = Bid::create([
        'open_points' => 100,
        'open_date' => 48,
        'status' => BidStatus::Live,
        'event_special' => true,
    ]);

    $bidActive = new BidActive([
        'bidid' => $bid->id,
        'points' => 500,
        'status' => 0,
    ]);
    $bidActive->created_at = Carbon::parse('2026-05-20 11:00:00');
    $bidActive->save();

    $windowStart = app(BidExpiryService::class)->finalWindowStart($bid->fresh());

    $entry = new BidEntry([
        'msisdn' => '2348099999999',
        'bidid' => $bid->id,
        'points' => 900,
    ]);
    $entry->created_at = $windowStart->copy()->subHour();
    $entry->save();

    expect(validateBidPoints($bid->fresh(), '2348099999999', 500))->toBeTrue();

    Carbon::setTestNow();
});

test('does not cap non event bids in final window', function () {
    Carbon::setTestNow('2026-05-21 12:00:00');

    $bid = Bid::create([
        'open_points' => 100,
        'open_date' => 48,
        'status' => BidStatus::Live,
        'event_special' => false,
    ]);

    $bidActive = new BidActive([
        'bidid' => $bid->id,
        'points' => 500,
        'status' => 0,
    ]);
    $bidActive->created_at = Carbon::parse('2026-05-20 11:00:00');
    $bidActive->save();

    $entry = new BidEntry([
        'msisdn' => '2348099999999',
        'bidid' => $bid->id,
        'points' => 600,
    ]);
    $entry->created_at = Carbon::parse('2026-05-21 10:00:00');
    $entry->save();

    expect(validateBidPoints($bid->fresh(), '2348099999999', 500))->toBeTrue();

    Carbon::setTestNow();
});
