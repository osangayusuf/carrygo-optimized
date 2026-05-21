<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    if (! Schema::hasTable('carrygo_bid')) {
        Schema::create('carrygo_bid', function (Blueprint $table) {
            $table->id();
            $table->string('category')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }
});

test('login page stores referral code from query string', function () {
    $response = $this->get(route('login', ['ref' => 'myref123']));

    $response->assertOk();
    $response->assertSessionHas('pending_referral_code', 'MYREF123');
});

test('login route accepts referral query parameter', function () {
    $url = route('login', ['ref' => 'SHAREME12']);

    expect($url)->toContain('ref=SHAREME12');
});
