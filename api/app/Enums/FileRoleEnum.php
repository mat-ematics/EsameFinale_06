<?php

namespace App\Enums;

enum FileRoleEnum : string
{
    case Film = 'film';
    case Episode = 'episode';
    case Cover = 'cover';
    case Thumbnail = 'thumbnail';
    case Video = 'video';
    case Image = 'image';
    case Other = 'other';

    public function storageDirectory()
    {
        return match ($this) {
            self::Film => 'films',
            self::Episode => 'episodes',
            self::Cover => 'covers',
            self::Thumbnail => 'thumbnails',
            self::Video => 'videos',
            self::Image => 'images',
            self::Other => 'others',
        };
    }

    public static function default() : FileRoleEnum
    {
        return self::Other;
    }
}
