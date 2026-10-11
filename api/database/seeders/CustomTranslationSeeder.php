<?php

namespace Database\Seeders;

use App\Models\Global\Language;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomTranslationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (!DB::table('custom_translations')->exists()) {

            //Ottengo gli ID e i Locale come array associativo [locale => id]
            $languages = Language::pluck('id', 'locale');
    
            //Liste Traduzioni per Chiave => [Locale => Traduzione]
            $translations = [
                'welcome_message' => [
                    'it_IT' => 'La mia Traduzione Custom!',
                ],
                'goodbye_message' => [
                    'it_IT' => 'Arrivederci Custom!',
                ],
            ];
    
            //Ciclo per ogni Messaggio/Chiave
            foreach ($translations as $key => $localized) {
                
                //Ciclo per ogni Locale
                foreach ($localized as $locale => $value) {
                    if (!isset($languages[$locale])) continue; //Se non esiste salto
    
                    //Inserimento Chiave, Valore e ID (posizione)
                    DB::table('custom_translations')->insert([
                        'key' => $key,
                        'value' => $value,
                        'language_id' => $languages[$locale],
                    ]);
                }
            }
        }
    }
}
