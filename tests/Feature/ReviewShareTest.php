<?php

use App\Models\Bid;
use App\Models\BidWinner;
use App\Models\Review;
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
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('carrygo_review')) {
        Schema::create('carrygo_review', function (Blueprint $table) {
            $table->id();
            $table->string('user_id'); // Stores MSISDN
            $table->integer('rating');
            $table->text('comment');
            $table->string('moderation_status')->default('pending');
            $table->unsignedBigInteger('bidid')->nullable();
            $table->string('social_platform')->nullable();
            $table->string('social_handle')->nullable();
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

test('visitor can view approved review page', function () {
    // Create User, Bid, and Review
    $user = User::create([
        'msisdn' => '2348030001111',
        'role' => 'user',
    ]);

    $bid = Bid::create([
        'name' => 'Premium Leather Bag',
        'price' => '25,000',
        'status' => 2, // Closed
    ]);

    $review = Review::create([
        'user_id' => $user->msisdn,
        'rating' => 5,
        'comment' => 'This is a fantastic bag! I love it!',
        'moderation_status' => 'approved',
        'bidid' => $bid->id,
    ]);

    $response = $this->get(route('reviews.show', $review));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('ReviewShow')
        ->where('review.id', $review->id)
        ->where('isWinner', false) // No BidWinner entry exists yet
    );
});

test('visitor can view approved review page with winner verification', function () {
    $user = User::create([
        'msisdn' => '2348030002222',
        'role' => 'user',
    ]);

    $bid = Bid::create([
        'name' => 'Premium Gaming Console',
        'price' => '450,000',
        'status' => 2, // Closed
    ]);

    // Create Winner Entry
    BidWinner::create([
        'msisdn' => $user->msisdn,
        'bidid' => $bid->id,
        'total_points' => 1500,
    ]);

    $review = Review::create([
        'user_id' => $user->msisdn,
        'rating' => 5,
        'comment' => 'Unbelievable win! Arrived quickly.',
        'moderation_status' => 'approved',
        'bidid' => $bid->id,
    ]);

    $response = $this->get(route('reviews.show', $review));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('ReviewShow')
        ->where('review.id', $review->id)
        ->where('isWinner', true) // Verified Winner!
    );
});

test('visitor cannot view unapproved review', function () {
    $user = User::create([
        'msisdn' => '2348030003333',
        'role' => 'user',
    ]);

    $review = Review::create([
        'user_id' => $user->msisdn,
        'rating' => 4,
        'comment' => 'Nice service.',
        'moderation_status' => 'pending', // Unapproved
    ]);

    $response = $this->get(route('reviews.show', $review));
    $response->assertNotFound();
});

test('visitor can generate approved review PNG card image', function () {
    $user = User::create([
        'msisdn' => '2348030004444',
        'role' => 'user',
    ]);

    $review = Review::create([
        'user_id' => $user->msisdn,
        'rating' => 5,
        'comment' => 'Wonderful CarryGo auction experience!',
        'moderation_status' => 'approved',
    ]);

    $response = $this->get(route('reviews.image', $review));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'image/png');
});
