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
        Schema::create('match_games', function (Blueprint $table) {
            $table->id();

            $table->foreignId('league_id');

            $table->foreignId('home_team_id');
            $table->foreignId('away_team_id');

            $table->dateTime('match_date')->nullable();

            $table->integer('home_score')->nullable();
            $table->integer('away_score')->nullable();

            $table->string('betexplorer_id')->unique();

            $table->string('url');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_games');
    }
};
