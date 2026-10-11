<?php

namespace Database\Seeders;

use App\Models\Location\Continent;
use Illuminate\Database\Seeder;

class ContinentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $list = Continent::getList();

        foreach ($list as $continent) {
            Continent::firstOrCreate(['code' => $continent['code']], ['id' => $continent['id'], 'name' => $continent['name']]);
        }
    }
}
