<?php

namespace App\Enums;

/**
 * L'Enum dei tipi di file memorizzabili
 */
enum FileRoleEnum : string
{
    /** Film */
    case Film = 'film';
    /** Episodio */
    case Episode = 'episode';
    /** Cover */
    case Cover = 'cover';
    /** Copertina */
    case Thumbnail = 'thumbnail';
    /** Video */
    case Video = 'video';
    /** Immagine */
    case Image = 'image';
    /** Altro */
    case Other = 'other';

    /**
     * Ritorna il tipo di file inserito
     */
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

    /**
     * Il tipo di default
     */
    public static function default() : FileRoleEnum
    {
        return self::Other;
    }
}
