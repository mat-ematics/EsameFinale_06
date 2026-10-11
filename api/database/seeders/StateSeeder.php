<?php

namespace Database\Seeders;

use App\Enums\StateEnum;
use App\Models\User\State;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //Creazione degli Stati Utente
        foreach (StateEnum::cases() as $state) {
            State::firstOrCreate([
                'name' => $state->value, //Nome
                'label' => ucfirst($state->value), //Label (nome con prima lettera maiuscola)
                'description' => $this->getDescription($state), //Descrizione (possibilmente nulla)
            ]);
        }
    }

    private function getDescription(StateEnum $state) : string
    {
        //Ritorna la descrizione di ogni Caso di Enum
        return match ($state) {
            StateEnum::Active => 'User account is active and fully functional.',
            StateEnum::Suspended => 'Account temporarily disabled by admin.',
            StateEnum::Banned => 'Account permanently banned for violations.',
            StateEnum::Locked => 'Account locked due to security concerns.',
        };
    }
}
