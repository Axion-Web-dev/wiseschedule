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
            $table->string('twitter_id')->nullable()->after('wp_password');
            $table->string('twitter_handle')->nullable()->after('twitter_id');
            $table->text('access_token')->nullable()->after('twitter_handle');
            $table->text('refresh_token')->nullable()->after('access_token');
            $table->timestamp('expires_at')->nullable()->after('refresh_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wp_sites', function (Blueprint $table) {
            $table->dropColumn(['twitter_id', 'twitter_handle', 'access_token', 'refresh_token', 'expires_at']);
        });
    }
};
