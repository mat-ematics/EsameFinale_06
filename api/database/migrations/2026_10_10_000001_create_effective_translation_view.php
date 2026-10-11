<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement($this->dropViewSql('effective_translations'));
        DB::statement("
            CREATE VIEW effective_translations AS
            SELECT language_id, `key`, value
            FROM custom_translations

            UNION ALL

            SELECT t.language_id, t.`key`, t.value
            FROM translations t
            LEFT JOIN custom_translations c
                ON c.language_id = t.language_id
                AND c.`key` = t.`key`
            WHERE c.id IS NULL
        ");
    }

    public function down(): void
    {
        DB::statement($this->dropViewSql('effective_translations'));
    }

    private function dropViewSql(string $name): string
    {
        // MySQL < 8.0 doesn't support "DROP VIEW IF EXISTS"? It does; this is for portability
        return "DROP VIEW IF EXISTS {$name}";
    }
};
