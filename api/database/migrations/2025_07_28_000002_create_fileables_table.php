<?php

use App\Enums\FileRoleEnum;
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
        if (!Schema::hasTable('fileables')) {
            Schema::create('fileables', function (Blueprint $table) {
                $table->foreignId('file_id')->constrained('files')->cascadeOnDelete();
                $table->morphs('fileable');
                $table->string('role')->default(FileRoleEnum::Other->value); //Ruolo/i opzionali del file (poster, cover...)
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fileables');
    }
};
