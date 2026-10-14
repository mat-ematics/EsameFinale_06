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

    public static function getAllConfigs() :mixed {
        return static::all();
    }

    public static function getConfig(string $key) :mixed {
        return static::where('key', $key)->value('value');
    }

    public static function getModelByConfig(string $key): ?self {
        return static::where('key', $key)->first();
    }
}
