<?php

namespace App\Enums;

enum FileVisibilityEnum : string
{
    case Public = 'public';
    case Private = 'private';

    public static function default() : FileVisibilityEnum
    {
        return self::Public;
    }
}