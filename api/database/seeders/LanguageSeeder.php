<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (!DB::table('languages')->exists()) {
            DB::table('languages')->insert([
                [
                    'name' => 'Italian',
                    'abbreviation' => 'it',
                    'locale' => 'it_IT'
                ],
                [
                    'name' => 'English (UK)',
                    'abbreviation' => 'en',
                    'locale' => 'en_GB'
                ],
                [
                    'name' => 'English (US)',
                    'abbreviation' => 'en',
                    'locale' => 'en_US'
                ],
                [
                    'name' => 'French (FR)',
                    'abbreviation' => 'fr',
                    'locale' => 'fr_FR'
                ],
                [
                    'name' => 'French (CA)',
                    'abbreviation' => 'fr',
                    'locale' => 'fr_CA'
                ],
                [
                    'name' => 'Spanish (ES)',
                    'abbreviation' => 'es',
                    'locale' => 'es_ES'
                ],
                [
                    'name' => 'German',
                    'abbreviation' => 'de',
                    'locale' => 'de_DE'
                ]
            ]);
        }
    }
}
