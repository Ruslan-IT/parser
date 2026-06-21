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
        Schema::create('match_probabilities', function (Blueprint $table) {

            $table->id();

            $table->foreignId('match_game_id');

            $table->string('market');

            $table->integer('criterion');

            $table->integer('category');

            $table->integer('sample_size');

            $table->decimal('probability',8,5);

            $table->decimal('effectiveness',8,5);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_probabilities');
    }
};
