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
        Schema::create('passwords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('password', 64);
            $table->string('salt', 64);
            $table->timestamps();
        });

        Schema::dropColumns('users', ['password', 'salt']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('passwords');
        Schema::table('users', function (Blueprint $table) {
            $table->string('password', 64)->after('username');
            $table->string('salt', 64)->after('password');
        });
    }
};
