<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::table('ai_articles', function (Blueprint $table) {
            $table->text('keywords')->change(); // Change from varchar to text to allow longer content
        });
    }

    public function down(): void
    {
        Schema::table('ai_articles', function (Blueprint $table) {
            $table->string('keywords')->change(); // Revert back to string if needed
        });
    }
};