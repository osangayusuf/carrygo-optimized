<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carrygo_users', function (Blueprint $table) {
            $table->foreignId('referred_by_user_id')
                ->nullable()
                ->after('referral_code')
                ->constrained('carrygo_users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('carrygo_users', function (Blueprint $table) {
            $table->dropForeign(['referred_by_user_id']);
            $table->dropColumn('referred_by_user_id');
        });
    }
};
