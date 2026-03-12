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
            $table->integer('word_count_target')->default(1000)->after('wp_post_id');
            $table->string('content_type')->default('blog_post')->after('word_count_target');
            $table->text('additional_instructions')->nullable()->after('content_type');
            $table->string('reading_time')->nullable()->after('featured_image_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_articles', function (Blueprint $table) {
            $table->dropColumn([
                'word_count_target',
                'content_type', 
                'additional_instructions',
                'reading_time'
            ]);
        });
    }
};
