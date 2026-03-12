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
        Schema::table('ai_articles', function (Blueprint $table) {
            $table->string('status', 50)->change(); // Extend from 10 to 50 characters
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_articles', function (Blueprint $table) {
            $table->string('status', 10)->change(); // Revert back to 10 characters
        });
    }
};
