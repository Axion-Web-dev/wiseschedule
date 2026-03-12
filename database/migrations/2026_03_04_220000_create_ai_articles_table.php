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
        Schema::create('ai_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wp_site_id')->constrained()->onDelete('cascade');
            $table->string('topic');
            $table->string('keywords')->nullable();
            $table->string('tone')->default('professional');
            $table->string('title')->nullable();
            $table->longText('content')->nullable();
            $table->text('featured_image_url')->nullable();
            $table->enum('status', ['pending', 'generating', 'ready', 'published', 'failed'])->default('pending');
            $table->string('wp_post_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_articles');
    }
};
