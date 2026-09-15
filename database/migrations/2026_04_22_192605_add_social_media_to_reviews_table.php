<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('carrygo_review')) {
            return;
        }

        Schema::table('carrygo_review', function (Blueprint $table) {
            $table->string('social_platform', 50)->nullable();
            $table->string('social_handle', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('carrygo_review')) {
            return;
        }

        Schema::table('carrygo_review', function (Blueprint $table) {
            $table->dropColumn(['social_platform', 'social_handle']);
        });
    }
};
