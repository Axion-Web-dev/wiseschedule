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
            // Add missing columns
            if (!Schema::hasColumn('ai_articles', 'word_count_target')) {
                $table->integer('word_count_target')->default(1000);
            }
            
            if (!Schema::hasColumn('ai_articles', 'content_type')) {
                $table->string('content_type')->default('blog_post');
            }
            
            if (!Schema::hasColumn('ai_articles', 'additional_instructions')) {
                $table->text('additional_instructions')->nullable();
            }
            
            if (!Schema::hasColumn('ai_articles', 'reading_time')) {
                $table->string('reading_time')->nullable();
            }

            // Fix status column - make it a string with default
            $table->string('status', 50)->default('pending')->change();
            
            // Make wp_site_id nullable for testing
            $table->foreignId('wp_site_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_articles', function (Blueprint $table) {
            $table->dropColumn(['word_count_target', 'content_type', 'additional_instructions', 'reading_time']);
            $table->enum('status', ['pending', 'generating', 'ready', 'published', 'failed'])->default('pending')->change();
            $table->foreignId('wp_site_id')->nullable(false)->change();
        });
    }
};
