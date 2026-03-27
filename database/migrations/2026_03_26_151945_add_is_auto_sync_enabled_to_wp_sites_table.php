<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::table('wp_sites', function (Blueprint $table) {
            $table->boolean('is_auto_sync_enabled')->default(true)->after('is_connected');
        });
    }

    public function down(): void
    {
        Schema::table('wp_sites', function (Blueprint $table) {
            $table->dropColumn('is_auto_sync_enabled');
        });
    }
};