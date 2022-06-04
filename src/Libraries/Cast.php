<?php

namespace LaravelOrm\Libraries;

class Cast
{
    public static function casting($type, &$value)
    {
        if (is_null($value)) {
             return;
        }

         $newType = explode(":", $type);
        switch ($newType[0]) {
            case "double":
                $value = (double)$value;
                return;
            case "boolean":
                 $value = (bool)$value;
                return;
            case "decimal":
                if (count($newType) == 2) {
                     $value = number_format($value, $newType[1], ",", ".");
                }
                return;
            case "float":
                 $value = (float)$value;
                return;
            case "integer":
                 $value = (int)$value;
                return;
            case "string":
                 $value = (string)$value;
                return;
            case "datetime":
                if (count($newType) == 2) {
                     $value = date_format(date_create($value), $newType[1]);
                }
                return;
            default:
                 $value = (string)$value;
                return;
        }
    }
}
