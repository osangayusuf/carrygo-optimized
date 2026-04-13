<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carrygo_users', function (Blueprint $table) {
            $table->unsignedTinyInteger('spins_balance')->default(1)->change();
        });
    }

    public function down(): void
    {
        Schema::table('carrygo_users', function (Blueprint $table) {
            $table->unsignedTinyInteger('spins_balance')->default(0)->change();
        });
    }
};
