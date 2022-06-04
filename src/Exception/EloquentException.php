<?php

namespace LaravelOrm\Exception;

use Exception;

/**
 * Model Exceptions.
 */

class EloquentException
{
    public static function forNoPrimaryKey(string $eloquentName)
    {
        return new Exception("No Primary Key Is Set:" . $eloquentName);
    }

    public static function forNoTableName(string $eloquentName)
    {
        return new Exception("No Table Name Is Set" . $eloquentName);
    }
}
