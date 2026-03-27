<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::table('ai_articles', function (Blueprint $table) {
            $table->dateTime('scheduled_at')->nullable()->after('featured_image_url');
        });
    }

    public function down(): void
    {
        Schema::table('ai_articles', function (Blueprint $table) {
            $table->dropColumn('scheduled_at');
        });
    }
};