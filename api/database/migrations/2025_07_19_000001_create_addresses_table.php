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
        if (!Schema::hasTable('addresses')) {
            Schema::create('addresses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
                $table->foreignId('italian_municipality_id')->nullable()->constrained('italian_municipalities')->nullOnDelete();
                $table->char('cap', 5);
                $table->string('street_address');
                $table->string('house_number', 10);
                $table->string('locality')->nullable(); //Frazione, Paese...
                $table->string('additional_info')->nullable(); //Informazioni aggiuntive sulla località
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
