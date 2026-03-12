<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ai_articles', function (Blueprint $table) {
            // Set default value for status column
            DB::statement("ALTER TABLE ai_articles ALTER status SET DEFAULT 'pending'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_articles', function (Blueprint $table) {
            DB::statement("ALTER TABLE ai_articles ALTER status DROP DEFAULT");
        });
    }
};
