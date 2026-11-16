<?php

namespace App\Enums;

/**
 * Enum del tipo di media
 */
enum MediaTypeEnum : string
{
    /** Film */
    case Film = 'film';
    /** Serie TV */
    case TvSeries = 'tv_series';
    /** Episodio */
    case Episode = 'episode';
}
