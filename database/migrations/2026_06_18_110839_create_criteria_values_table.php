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
        Schema::create('criteria_values', function (Blueprint $table) {
            $table->id();

            $table->foreignId('match_game_id');

            $table->decimal('criteria_1',10,3)->nullable();
            $table->decimal('criteria_2',10,3)->nullable();
            $table->decimal('criteria_3',10,3)->nullable();
            $table->decimal('criteria_4',10,3)->nullable();
            $table->decimal('criteria_5',10,3)->nullable();
            $table->decimal('criteria_6',10,3)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('criteria_values');
    }
};
