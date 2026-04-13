<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carrygo_user_achievements', function (Blueprint $table) {
            $table->id();
            $table->string('msisdn');
            $table->string('achievement_key', 64);
            $table->unsignedSmallInteger('progress')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['msisdn', 'achievement_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrygo_user_achievements');
    }
};
