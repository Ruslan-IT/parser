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
        Schema::create('asian_handicaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_game_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['balanced', 'purchase']);
            $table->decimal('home_handicap', 5, 2);
            $table->decimal('away_handicap', 5, 2);
            $table->decimal('home_odds', 8, 3);
            $table->decimal('away_odds', 8, 3);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asian_handicaps');
    }
};
