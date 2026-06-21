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
            $table->string('season')->nullable()->after('betexplorer_id');
            $table->enum('match_status', ['scheduled', 'finished', 'postponed'])->nullable()->after('season');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('match_games', function (Blueprint $table) {
            $table->dropColumn(['season', 'match_status']);
        });
    }
};
