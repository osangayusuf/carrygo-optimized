<?php

use App\Models\Bid;
use App\Models\BidEntry;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    // Dynamically build tables for SQLite testing environment if they don't exist
    if (! Schema::hasTable('carrygo_bid')) {
        Schema::create('carrygo_bid', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('image')->nullable();
            $table->string('url')->nullable();
            $table->string('price')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->string('category')->nullable();
            $table->integer('open_points')->default(0);
            $table->integer('open_date')->default(0);
            $table->timestamp('created_at')->nullable();
        });
    }

    if (! Schema::hasTable('carrygo_bid_entry')) {
        Schema::create('carrygo_bid_entry', function (Blueprint $table) {
            $table->id();
            $table->string('msisdn');
            $table->unsignedBigInteger('bidid');
            $table->integer('points')->default(0);
            $table->timestamp('created_at')->nullable();
        });
    }

    if (! Schema::hasTable('carrygo_bid_active')) {
        Schema::create('carrygo_bid_active', function (Blueprint $table) {
            $table->id();
            $table->string('msisdn')->nullable();
            $table->unsignedBigInteger('bidid');
            $table->integer('points')->default(0);
            $table->tinyInteger('status')->default(0);
            $table->timestamp('created_at')->nullable();
        });
    }

    if (! Schema::hasTable('carrygo_bid_winners')) {
        Schema::create('carrygo_bid_winners', function (Blueprint $table) {
            $table->id();
            $table->string('msisdn');
            $table->integer('total_points')->default(0);
            $table->unsignedBigInteger('bidid');
            $table->timestamp('created_at')->nullable();
        });
    }

    if (! Schema::hasTable('carrygo_active_points')) {
        Schema::create('carrygo_active_points', function (Blueprint $table) {
            $table->id();
            $table->string('msisdn');
            $table->integer('points')->default(0);
            $table->timestamp('updated_at')->nullable();
        });
    }
});

test('profile page returns user engaged bids and paginates them', function () {
    $user = User::create([
        'msisdn' => '2348030001111',
        'role' => 'user',
        'referral_code' => 'TESTREF123',
    ]);

    // Create 3 bids
    $bid1 = Bid::create([
        'name' => 'Premium Leather Bag',
        'price' => '25,000',
        'status' => 0, // Live
        'open_points' => 100,
        'open_date' => 24,
    ]);

    $bid2 = Bid::create([
        'name' => 'Premium Gaming Console',
        'price' => '450,000',
        'status' => 0, // Live
        'open_points' => 1000,
        'open_date' => 48,
    ]);

    $bid3 = Bid::create([
        'name' => 'Gucci Sunglasses',
        'price' => '120,000',
        'status' => 2, // Closed
        'open_points' => 500,
        'open_date' => 12,
    ]);

    // User bids on bid1 and bid2, but not bid3
    $entry1 = new BidEntry([
        'msisdn' => $user->msisdn,
        'bidid' => $bid1->id,
        'points' => 50,
    ]);
    $entry1->created_at = now()->subHour();
    $entry1->save();

    $entry2 = new BidEntry([
        'msisdn' => $user->msisdn,
        'bidid' => $bid2->id,
        'points' => 150,
    ]);
    $entry2->created_at = now();
    $entry2->save();

    // Make request as authenticated user
    $response = $this->actingAs($user)->get(route('profile'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Profile')
        ->has('myBids.data', 2)
        // Verify we get the bids
        ->where('myBids.data.0.id', $bid2->id)
        ->where('myBids.data.1.id', $bid1->id)
    );
});
