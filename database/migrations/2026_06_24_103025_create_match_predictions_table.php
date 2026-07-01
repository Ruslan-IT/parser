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
        Schema::create('match_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_game_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('criteria_id')->nullable(); // 1..6
            $table->foreignId('category_id')->nullable()->constrained('criteria_categories')->onDelete('set null');
            $table->boolean('is_average')->default(false);

            // Вероятности (0..1)
            $table->decimal('prob_home', 6, 5)->nullable();
            $table->decimal('prob_draw', 6, 5)->nullable();
            $table->decimal('prob_away', 6, 5)->nullable();

            // Эффективности
            $table->decimal('eff_home', 8, 5)->nullable();
            $table->decimal('eff_draw', 8, 5)->nullable();
            $table->decimal('eff_away', 8, 5)->nullable();

            // Азиатские форы
            $table->decimal('handicap_home_prob', 6, 5)->nullable();
            $table->decimal('handicap_away_prob', 6, 5)->nullable();
            $table->decimal('handicap_home_eff', 8, 5)->nullable();
            $table->decimal('handicap_away_eff', 8, 5)->nullable();

            $table->timestamps();

            // Индексы
            $table->index(['match_game_id', 'criteria_id', 'is_average']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_predictions');
    }
};
