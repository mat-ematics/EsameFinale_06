<?php

namespace Database\Seeders;

use App\Models\Authorization\RolePermission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pivotValues = [
            ['role_id' => 1, 'permission_id' => 4],
            ['role_id' => 1, 'permission_id' => 3],
            ['role_id' => 1, 'permission_id' => 2],
            ['role_id' => 1, 'permission_id' => 1],
            ['role_id' => 2, 'permission_id' => 1],
        ];

        foreach ($pivotValues as $pairs) {
            RolePermission::firstOrCreate($pairs);
        }
    }
}
