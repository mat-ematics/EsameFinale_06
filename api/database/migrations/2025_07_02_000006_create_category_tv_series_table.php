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
        if (!Schema::hasTable('category_tv_series')) {
            Schema::create('category_tv_series', function (Blueprint $table) {
                $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
                $table->foreignId('tv_series_id')->constrained('tv_series')->cascadeOnDelete();
                $table->unique(['category_id', 'tv_series_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_tv_series');
    }
};
