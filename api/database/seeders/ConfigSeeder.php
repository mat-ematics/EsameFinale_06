<?php

namespace Database\Seeders;

use App\Models\Global\Config;
use Illuminate\Database\Seeder;

class ConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Config::firstOrCreate(['key' => 'max_login_attempts', 'value' => 3]);
        Config::firstOrCreate(['key' => 'max_login_time_duration', 'value' => 600]);
        Config::firstOrCreate(['key' => 'max_token_duration', 'value' => 18000]);
        Config::firstOrCreate(['key' => 'login_lock_duration', 'value' => 900]);
        Config::firstOrCreate(['key' => 'login_attempt_duration', 'value' => 60]);
    }
}
