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
        Schema::create('bet_results', function (Blueprint $table) {
            $table->id();

            $table->foreignId('match_game_id');

            $table->string('market');

            $table->decimal('odds',8,3);

            $table->enum('result',[
                'WIN',
                'LOSE',
                'RETURN'
            ]);

            $table->decimal('payout',8,3);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bet_results');
    }
};
