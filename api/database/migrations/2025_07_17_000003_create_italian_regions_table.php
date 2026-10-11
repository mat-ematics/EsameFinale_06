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
        if (!Schema::hasTable('italian_regions')) {
            Schema::create('italian_regions', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->char('code', 3);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('italian_regions');
    }
};
