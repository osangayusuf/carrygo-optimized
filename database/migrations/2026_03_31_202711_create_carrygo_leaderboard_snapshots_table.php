<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carrygo_leaderboard_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('msisdn');
            $table->unsignedTinyInteger('rank');
            $table->unsignedInteger('total_bid_pts');
            $table->date('week_start');
            $table->unsignedSmallInteger('bonus_awarded');
            $table->timestamp('created_at')->useCurrent();

            $table->index('week_start');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrygo_leaderboard_snapshots');
    }
};
