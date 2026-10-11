<?php

namespace Database\Seeders;

use App\Models\Media\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Category::firstOrCreate(['name'=> 'scifi', 'label' => 'Sci-Fi', 'description' => null]);
        Category::firstOrCreate(['name'=> 'family', 'label' => 'Family', 'description' => 'For all the Family!']);
        Category::firstOrCreate(['name'=> 'comedy', 'label' => 'Comedy', 'description' => null]);
    }
}
