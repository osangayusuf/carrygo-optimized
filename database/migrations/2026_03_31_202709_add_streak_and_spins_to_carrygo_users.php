<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carrygo_users', function (Blueprint $table) {
            $table->unsignedTinyInteger('checkin_streak')->default(0)->after('referral_code');
            $table->date('last_checkin_date')->nullable()->after('checkin_streak');
            $table->unsignedTinyInteger('spins_balance')->default(0)->after('last_checkin_date');
        });
    }

    public function down(): void
    {
        Schema::table('carrygo_users', function (Blueprint $table) {
            $table->dropColumn(['checkin_streak', 'last_checkin_date', 'spins_balance']);
        });
    }
};
