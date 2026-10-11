<?php

namespace App\Traits;

trait StaticPropertiesTrait
{
    public static function getTableName() : string
    {
        return with(new static)->getTable();
    }
}
