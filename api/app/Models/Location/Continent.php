<?php

namespace App\Models\Location;

use Illuminate\Database\Eloquent\Model;

class Continent extends Model
{
    protected $table = 'continents';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'code',
    ];

    public static function getList() 
    {
        return [
            'EU' => ['code' => 'EU', 'id' => 1, 'name' => 'Europe'],
            'AS' => ['code' => 'AS', 'id' => 2, 'name' => 'Asia'],
            'AF' => ['code' => 'AF', 'id' => 3, 'name' => 'Africa'],
            'NA' => ['code' => 'NA', 'id' => 4, 'name' => 'North America'],
            'SA' => ['code' => 'SA', 'id' => 5, 'name' => 'South America'],
            'OC' => ['code' => 'OC', 'id' => 6, 'name' => 'Oceania'],
            'AN' => ['code' => 'AC', 'id' => 7, 'name' => 'Antarctica'],
        ];
    }

    /* --------- RELAZIONI ---------*/

    public function countries()
    {
        return $this->hasMany(Country::class);
    }
}
