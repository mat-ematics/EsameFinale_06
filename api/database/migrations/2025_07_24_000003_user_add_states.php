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
        if (
            Schema::hasTable('users') &&
            !Schema::hasColumn('users', 'state_id') &&
            !Schema::hasColumn('users', 'state_until')
           ) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('state_id')
                      ->default(1)
                      ->after('salt')
                      ->constrained('states');

                $table->timestamp('state_until')->nullable()->after('state_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (
            Schema::hasTable('users') &&
            Schema::hasColumn('users', 'state_id') &&
            Schema::hasColumn('users', 'state_until')
           ) {
            Schema::dropColumns('users', ['state_id', 'state_until']);
        }
    }
};
