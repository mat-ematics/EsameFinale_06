<?php

namespace App\Enums;

/**
 * L'Enum di visibilità File 
 */
enum FileVisibilityEnum : string
{
    /** Pubblica */
    case Public = 'public';
    /* Privata */
    case Private = 'private';

    /**
     * Il valore di default
     */
    public static function default() : FileVisibilityEnum
    {
        return self::Public;
    }
}