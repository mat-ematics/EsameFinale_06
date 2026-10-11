<?php

namespace App\Enums;

enum MediaTypeEnum : string
{
    case Film = 'film';
    case TvSeries = 'tv_series';
    case Episode = 'episode';
}
