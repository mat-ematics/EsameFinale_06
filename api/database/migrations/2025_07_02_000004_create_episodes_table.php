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
        if (!Schema::hasTable('episodes'))
        {
            Schema::create('episodes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tv_series_id')->constrained('tv_series')->cascadeOnDelete();
                $table->string('name');
                $table->string('description')->nullable();
                $table->unsignedTinyInteger('season_number');
                $table->unsignedInteger('episode_number');
                $table->time('length');
                $table->unsignedSmallInteger('year');
                $table->timestamps();
                $table->softDeletes();

                // Chiave Composta
                $table->unique(['tv_series_id', 'season_number', 'episode_number'], 'unique_episode_per_season_per_series');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
