<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('criteria_categories')->insert([
            ['name' => 'Категория 1 (300+)', 'min_matches' => 300, 'max_matches' => null],
            ['name' => 'Категория 2 (151-299)', 'min_matches' => 151, 'max_matches' => 299],
            ['name' => 'Категория 3 (50-150)', 'min_matches' => 50,  'max_matches' => 150],
        ]);
    }

    public function down(): void
    {
        DB::table('criteria_categories')->truncate();
    }
};
