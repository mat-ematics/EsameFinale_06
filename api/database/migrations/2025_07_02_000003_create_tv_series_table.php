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
        if (!Schema::hasTable('tv_series'))
        {
            Schema::create('tv_series', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('description')->nullable();
                $table->unsignedTinyInteger('season_count')->default(0);
                $table->unsignedInteger('episode_count')->default(0);
                $table->string('directors');
                $table->string('actors');
                $table->unsignedSmallInteger('start_year');
                $table->unsignedSmallInteger('end_year');
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tv_series');
    }
};
