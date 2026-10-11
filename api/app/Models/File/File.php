<?php

namespace App\Models\File;

use App\Helpers\AppHelpers;
use App\Models\Media\Episode;
use App\Models\Media\Film;
use App\Models\Media\TvSeries;
use App\Traits\StaticPropertiesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class File extends Model
{
    use SoftDeletes, StaticPropertiesTrait;

    private const MORPH_NAME = 'fileable';
    private const PUBLIC_DIRECTORY_PATH = 'storage/app/public/';

    protected $table = 'files';

    protected $fillable = [
        'filename',
        'label',
        'visibility',
        'path',
        'size',
        'mime_type',
        'extension',
        'hash',
    ];

    protected $casts = [
        'visibility' => \App\Enums\FileVisibilityEnum::class,
        'role' => \App\Enums\FileRoleEnum::class,
    ];

    /* --------- RELAZIONI ---------*/

    public function films() : MorphToMany
    {
        return $this->morphedByMany(Film::class, self::MORPH_NAME)->using(Fileable::class);
    }

    public function tvSeries() : MorphToMany
    {
        return $this->morphedByMany(TvSeries::class, $this->morphName)->using(Fileable::class);
    }

    public function episodes() : MorphToMany
    {
        return $this->morphedByMany(Episode::class, $this->morphName)->using(Fileable::class);
    }

    /* --------- METODI ---------*/

    public function getFullPath() : string
    {
        return static::mergePathFilename($this->path, $this->filename);
    }

    public function generateHash() : string
    {
        $path = $this->getFullPath();
        return AppHelpers::fileHash($path);
    }

    public function getUrl() : string
    {
        $path = $this->getFullPath();
        $url = 'storage/' . preg_replace('/.*\/public\//', '', $path);
        return $url;
    }

    /* --------- METODI STATICI ---------*/

    public static function mergePathFilename(string $path, string $filename) : string
    {
        return rtrim($path, '/') . '/' . ltrim($filename, '/');
    }

    public static function mergePaths(string $precedingPath, string $succeedingPath, bool $addEndingSeparator = false) : string
    {
        $separator = $addEndingSeparator ? '/' : '';
        return rtrim($precedingPath, '/') . '/' . $succeedingPath . $separator;
    }

    public static function getMorphName() : string
    {
        return static::MORPH_NAME;
    }

    public static function getPublicDirectoryPath()
    {
        return self::PUBLIC_DIRECTORY_PATH;
    }
}
