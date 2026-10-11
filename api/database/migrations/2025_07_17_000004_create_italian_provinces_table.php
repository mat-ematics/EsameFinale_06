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
        if (!Schema::hasTable('italian_provinces')) {
            Schema::create('italian_provinces', function (Blueprint $table) {
                $table->id();
                $table->foreignId('region_id')->nullable()->constrained('italian_regions')->nullOnDelete();
                $table->string('name')->unique();
                $table->char('code', 2);
                $table->boolean('is_metropolitan');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('italian_provinces');
    }
};
