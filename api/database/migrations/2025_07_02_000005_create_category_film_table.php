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
        if (!Schema::hasTable('category_film')) {
            Schema::create('category_film', function (Blueprint $table) {
                $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
                $table->foreignId('film_id')->constrained('films')->cascadeOnDelete();
                $table->unique(['category_id', 'film_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_film');
    }
};
