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
        Schema::table('match_games', function (Blueprint $table) {
            // Добавляем три колонки для коэффициентов 1X2
            $table->decimal('odd_home', 8, 3)->nullable()->after('away_score');
            $table->decimal('odd_draw', 8, 3)->nullable()->after('odd_home');
            $table->decimal('odd_away', 8, 3)->nullable()->after('odd_draw');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('match_games', function (Blueprint $table) {
            $table->dropColumn(['odd_home', 'odd_draw', 'odd_away']);
            $table->json('odds_json')->nullable(); // восстанавливаем
        });
    }
};
