<?php

namespace Database\Seeders;

use App\Models\Authorization\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'Read' => 'read',
            'Insert' => 'insert',
            'Update' => 'update',
            'Delete' => 'delete'
        ];

        foreach ($permissions as $label => $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'label' => $label,
            ]);
        }
    }
}
