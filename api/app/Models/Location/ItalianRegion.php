<?php

namespace App\Models\Location;

use Illuminate\Database\Eloquent\Model;

class ItalianRegion extends Model
{
    protected $table = 'italian_regions';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'code',
        'capital_municipality_id',
    ];

    /* --------- RELAZIONI ---------*/

    public function provinces()
    {
        return $this->hasMany(ItalianProvince::class);
    }

    /* --------- METODI ---------*/

    public static function getList() 
    {
        return [
            'Abruzzo' => ['code' => 'ABR', 'id' => 1, 'name' => 'Abruzzo', 'capital' => 'Pescara'],
            'Basilicata' => ['code' => 'BAS', 'id' => 2, 'name' => 'Basilicata', 'capital' => 'Potenza'],
            'Calabria' => ['code' => 'CAL', 'id' => 3, 'name' => 'Calabria', 'capital' => 'Reggio Calabria'],
            'Campania' => ['code' => 'CAM', 'id' => 4, 'name' => 'Campania', 'capital' => 'Napoli'],
            'Emilia-Romagna' => ['code' => 'EMR', 'id' => 5, 'name' => 'Emilia-Romagna', 'capital' => 'Bologna'],
            'Friuli-Venezia Giulia' => ['code' => 'FVG', 'id' => 6, 'name' => 'Friuli-Venezia Giulia', 'capital' => 'Trieste'],
            'Lazio' => ['code' => 'LAZ', 'id' => 7, 'name' => 'Lazio', 'capital' => 'Roma'],
            'Liguria' => ['code' => 'LIG', 'id' => 8, 'name' => 'Liguria', 'capital' => 'Genova'],
            'Lombardia' => ['code' => 'LOM', 'id' => 9, 'name' => 'Lombardia', 'capital' => 'Milano'],
            'Marche' => ['code' => 'MAR', 'id' => 10, 'name' => 'Marche', 'capital' => 'Ancona'],
            'Molise' => ['code' => 'MOL', 'id' => 11, 'name' => 'Molise', 'capital' => 'Campobasso'],
            'Piemonte' => ['code' => 'PMN', 'id' => 12, 'name' => 'Piemonte', 'capital' => 'Torino'],
            'Puglia' => ['code' => 'PUG', 'id' => 13, 'name' => 'Puglia', 'capital' => 'Bari'],
            'Sardegna' => ['code' => 'SAR', 'id' => 14, 'name' => 'Sardegna', 'capital' => 'Cagliari'],
            'Sicilia' => ['code' => 'SIC', 'id' => 15, 'name' => 'Sicilia', 'capital' => 'Palermo'],
            'Toscana' => ['code' => 'TOS', 'id' => 16, 'name' => 'Toscana', 'capital' => 'Firenze'],
            'Trentino-Alto Adige' => ['code' => 'TAA', 'id' => 17, 'name' => 'Trentino-Alto Adige', 'capital' => 'Trento'],
            'Umbria' => ['code' => 'UMB', 'id' => 18, 'name' => 'Umbria', 'capital' => 'Perugia'],
            "Valle d'Aosta" => ['code' => 'VDA', 'id' => 19, 'name' => "Valle d'Aosta", 'capital' => 'Aosta'],
            'Veneto' => ['code' => 'VEN', 'id' => 20, 'name' => 'Veneto', 'capital' => 'Venezia'],
        ];
    }
}
