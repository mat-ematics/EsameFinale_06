<?php

namespace Database\Seeders;

use App\Models\Location\Continent;
use App\Models\Location\Country;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = 'database/csv/countries.csv';

        //Controllo esistenza CSV
        if (!file_exists($path)) {
            $this->command->error("CSV Not Found at $path"); //Ritorno Errore a Console
            return;
        }

        $handle = fopen($path, 'r');

        $headers = fgetcsv($handle);

        $list = Continent::getList();

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($headers, $row);

            Country::firstOrCreate([
                'continent_id' => $list[$data['continent']]['id'],
                'name' => $data['name'],
                'iso' => $data['iso'],
                'iso3' => $data['iso3'],
                'phone_prefix' => $data['phone_prefix'],
            ]);
        }

        fclose($handle);
    }
}
