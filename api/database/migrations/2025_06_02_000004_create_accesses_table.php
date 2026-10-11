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
        if (!Schema::hasTable('accesses')) {
            Schema::create('accesses', function (Blueprint $table) {
                $table->id();
                $table->string('ip', 45);
                $table->string('user_hash');
                $table->tinyInteger('attempts')->unsigned()->default(1);
                $table->timestamp('attempt_start_at');
                $table->timestamp('last_attempt_at')->nullable();
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
        Schema::dropIfExists('accesses');
    }
};
