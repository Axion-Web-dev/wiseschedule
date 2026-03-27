<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {

            $table->dropUnique(['wp_post_id']);

            $table->unique(['wp_site_id', 'wp_post_id']);
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {

            $table->dropUnique(['wp_site_id', 'wp_post_id']);

            $table->unique('wp_post_id');
        });
    }
};