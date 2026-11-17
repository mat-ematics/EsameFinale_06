<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DevMediaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (!app()->environment('local')) return;

        $now = now();
        //present categories
        $categories = DB::table('categories')->orderBy('id')->pluck('id')->all();
        if (!$categories) {
            $this->command?->warn("NO categories found: create some first");
            return;
        }

        $films = [
            ['Nathan', 'Nathan discovers the world together with the audience he takes along on a dazzling adventure. Life.', '01:07:00', 'Sean Decolavic', 'Mike Dafoe', 2001],
            ['Nathan Returns', 'The sequel: Nathan comes home and finds the world changed.', '01:35:00', 'Sean Decolavic', 'Mike Dafoe', 2004],
            ['Space Adventure', 'A crew crosses the galaxy. Nathan is the ship cat.', '02:05:00', 'Ana Ribeiro', 'Lena Vogel, Tom Hale', 2012],
            ['Dark House', 'Something is wrong with the new house.', '01:42:00', 'Paul Venn', 'Nora Quill', 2019],
        ];


        foreach ($films as $i => [$name, $description, $length, $directors, $actors, $year]) {
            DB::table('films')->updateOrInsert(
                ['name' => $name],
                compact('description', 'length', 'directors', 'actors', 'year') + ['created_at' => $now, 'updated_at' => $now]
            );

            $id = DB::table('films')->where('name', $name)->value('id');
            DB::table('category_film')->insertOrIgnore([
                'category_id' => $categories[$i % count($categories)],
                'film_id'     => $id,
            ]);
        }

        $series = [
            ['Long Night', 'A detective series set in a city that never sleeps.', 'Ana Ribeiro', 'Nora Quill', 2015, 2019],
            ['Galaxy Station', 'Life on a remote space station. Nathan runs the canteen.', 'Paul Venn', 'Tom Hale', 2020, 2023],
        ];

        foreach ($series as $i => [$name, $description, $directors, $actors, $start, $end]) {
            DB::table('tv_series')->updateOrInsert(['name' => $name], [
                'description'   => $description,
                'directors'     => $directors,
                'actors'        => $actors,
                'start_year'    => $start,
                'end_year'      => $end,
                'season_count'  => 2,
                'episode_count' => 4,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);

            $id = Db::table('tv_series')->where('name', $name)->value('id');
            DB::table('category_tv_series')->insertOrIgnore([
                'category_id' => $categories[$i % count($categories)],
                'tv_series_id' => $id,
            ]);

            foreach ([1, 2] as $season) {
                foreach ([1, 2] as $number) {
                    DB::table('episodes')->updateOrInsert(
                        ['tv_series_id' => $id, 'season_number' => $season, 'episode_number' => $number],
                        [
                            'name'        => $season === 1 && $number === 1 ? 'The Missing Key' : "Episode $number",
                            'description' => null,
                            'length'      => '00:42:00',
                            'year'        => $start + $season - 1,
                            'created_at'  => $now,
                            'updated_at'  => $now,
                        ]
                    );
                }
            }
        }
    }
}
