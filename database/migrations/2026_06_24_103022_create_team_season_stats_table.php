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
        Schema::create('team_season_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('league_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('season', 10);

            // Основные показатели
            $table->integer('matches_total')->default(0);
            $table->integer('matches_home')->default(0);
            $table->integer('matches_away')->default(0);

            $table->integer('points_total')->default(0);
            $table->integer('points_home')->default(0);
            $table->integer('points_away')->default(0);

            $table->integer('goals_scored_total')->default(0);
            $table->integer('goals_scored_home')->default(0);
            $table->integer('goals_scored_away')->default(0);

            $table->integer('goals_conceded_total')->default(0);
            $table->integer('goals_conceded_home')->default(0);
            $table->integer('goals_conceded_away')->default(0);

            $table->integer('goals_diff_total')->default(0);
            $table->integer('goals_diff_home')->default(0);
            $table->integer('goals_diff_away')->default(0);

            // Форма (последние 5 матчей)
            $table->integer('points_last5')->default(0);
            $table->integer('points_last5_home')->default(0);
            $table->integer('points_last5_away')->default(0);
            $table->integer('goals_scored_last5')->default(0);
            $table->integer('goals_conceded_last5')->default(0);

            $table->timestamps();

            // Уникальность
            $table->unique(['team_id', 'league_id', 'season']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_season_stats');
    }
};
