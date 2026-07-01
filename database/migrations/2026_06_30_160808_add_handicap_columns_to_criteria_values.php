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
        Schema::table('criteria_values', function (Blueprint $table) {
            $table->decimal('handicap_balanced_home', 8, 5)->nullable();
            $table->decimal('handicap_balanced_away', 8, 5)->nullable();
            $table->decimal('handicap_purchase_home', 8, 5)->nullable();
            $table->decimal('handicap_purchase_away', 8, 5)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('criteria_values', function (Blueprint $table) {
            $table->dropColumn([
                'handicap_balanced_home',
                'handicap_balanced_away',
                'handicap_purchase_home',
                'handicap_purchase_away',
            ]);
        });
    }
};
