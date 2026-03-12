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
        Schema::table('wp_sites', function (Blueprint $table) {
            $table->string('twitter_avatar')->nullable()->after('twitter_handle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wp_sites', function (Blueprint $table) {
            $table->dropColumn('twitter_avatar');
        });
    }
};
