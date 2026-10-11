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
        if (!Schema::hasTable('italian_municipalities')) {
            Schema::create('italian_municipalities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('province_id')->nullable()->constrained('italian_provinces')->nullOnDelete();
                $table->string('name');
                $table->char('catastal_code', 4)->unique();
                $table->char('cap', 5);

                $table->unique(['name', 'province_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('italian_municipalities');
    }
};
