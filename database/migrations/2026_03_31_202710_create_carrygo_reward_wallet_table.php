<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carrygo_reward_wallet', function (Blueprint $table) {
            $table->id();
            $table->string('msisdn');
            $table->enum('source', ['checkin', 'spin', 'achievement', 'leaderboard', 'referral']);
            $table->string('description');
            $table->unsignedSmallInteger('points');
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['msisdn', 'claimed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrygo_reward_wallet');
    }
};
