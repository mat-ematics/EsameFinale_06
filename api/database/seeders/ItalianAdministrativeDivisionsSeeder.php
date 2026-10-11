<?php

namespace Database\Seeders;

use App\Models\Location\ItalianMunicipality;
use App\Models\Location\ItalianProvince;
use App\Models\Location\ItalianRegion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ItalianAdministrativeDivisionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //Controllo se il Seed è già stato eseguito controllando se esiste il primo record
        if (ItalianMunicipality::exists()) {
            return;
        }

        $path = 'database/csv/italian_municipalities.csv';

        //Controllo esistenza CSV
        if (!file_exists($path)) {
            $this->command->error("CSV Not Found at $path"); //Ritorno Errore a Console
            return;
        }

        $handle = fopen($path, 'r');

        $headers = fgetcsv($handle);

        //Liste e Mappe Regioni, Province, Capoluoghi
        $regionList = ItalianRegion::getList();
        
        $regionMap = [];
        $provinceMap = [];

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($headers, $row);

            $regionName = $data['region'];
            $provinceName = !empty($data['metropolitan_area']) ? $data['metropolitan_area'] : $data['province'];
            $municipalityName = $data['name'];

            //Creazione Record Regione
            if (!isset($regionMap[$regionName])) {
                $region = ItalianRegion::firstOrCreate([
                    'name' => $regionName,
                    'code' => $regionList[$regionName]['code'],
                ]);

                $regionMap[$regionName] = $region->id;
            }

            //Creazione Record Provincia
            $regionId = $regionMap[$regionName];
            $provinceKey = "$regionId|$provinceName";

            if (!isset($provinceMap[$provinceKey])) {
                $province = ItalianProvince::firstOrCreate([
                    'region_id' => $regionId,
                    'name' => $provinceName,
                    'code' => $data['province_initials'],
                    'is_metropolitan' => !empty($data['metropolitan_area']),
                ]);

                $provinceMap[$provinceKey] = $province->id;
            }

            //Creazione Record Comune
            $provinceId = $provinceMap[$provinceKey];

            ItalianMunicipality::firstOrCreate([
                'province_id' => $provinceId,
                'name' => $municipalityName,
                'cap' => $data['cap'],
                'catastal_code' => $data['catastal_code'],
            ]);

        }

        fclose($handle);
    }
}
