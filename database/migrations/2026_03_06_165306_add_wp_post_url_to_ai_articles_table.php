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
            $table->string('wp_post_url')->nullable()->after('wp_post_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_articles', function (Blueprint $table) {
            $table->dropColumn('wp_post_url');
        });
    }
};
