<?php

namespace App\Models\Global;

use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    protected $table = 'configs';

    protected $fillable = [
        'key',
        'value',
    ];

    public static function getConfig(string $key) :mixed {
        return static::where('key', $key)->value('value');
    }
}
