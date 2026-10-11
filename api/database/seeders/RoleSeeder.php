<?php

namespace Database\Seeders;

use App\Models\Authorization\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {        
       
        $roles = [
            'Administrator' => 'admin',
            'User' => 'user',
            'Guest' => 'guest',
        ];

        foreach ($roles as $label => $name) {
            Role::firstOrCreate([
                'name' => $name,
                'label' => $label,
            ]);
        }
    }
}
