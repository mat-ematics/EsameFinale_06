<?php

namespace Database\Seeders;

use App\Models\Global\Language;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TranslationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (!DB::table('translations')->exists()) {

            //Ottengo gli ID e i Locale come array associativo [locale => id]
            $languages = Language::pluck('id', 'locale');
    
            //Liste Traduzioni per Chiave => [Locale => Traduzione]
            $translations = [
                'welcome_message' => [
                    'en_US' => 'Welcome!',
                    'en_GB' => 'Welcome!',
                    'it_IT' => 'Benvenuto!',
                    'fr_FR' => 'Bienvenue!',
                    'fr_CA' => 'Bienvenue!',
                    'es_ES' => '¡Bienvenido!',
                    'de_DE' => 'Willkommen!',
                ],
                'goodbye_message' => [
                    'en_US' => 'Goodbye!',
                    'it_IT' => 'Arrivederci!',
                    'fr_FR' => 'Au revoir!',
                    'es_ES' => '¡Adiós!',
                    'de_DE' => 'Auf Wiedersehen!',
                ],
            ];
    
            //Ciclo per ogni Messaggio/Chiave
            foreach ($translations as $key => $localized) {
                
                //Ciclo per ogni Locale
                foreach ($localized as $locale => $value) {
                    if (!isset($languages[$locale])) continue; //Se non esiste salto
    
                    //Inserimento Chiave, Valore e ID (posizione)
                    DB::table('translations')->insert([
                        'key' => $key,
                        'value' => $value,
                        'language_id' => $languages[$locale],
                    ]);
                }
            }
        }
    }
}
