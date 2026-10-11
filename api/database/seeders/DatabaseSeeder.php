<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ConfigSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            RolePermissionSeeder::class,
            CategorySeeder::class,
            ContinentSeeder::class,
            CountrySeeder::class,
            ItalianAdministrativeDivisionsSeeder::class,
            StateSeeder::class,
            LanguageSeeder::class,
            TranslationSeeder::class,
            CustomTranslationSeeder::class,
        ]);
    }
}
