<?php

namespace LaravelOrm\Entities;

use Symfony\Component\Yaml\Yaml;
use Config\Entity;

class ORM
{
    /**
     * Get props
     * @param string$entityName
     * @return array
     */
    public static function getProps(string $entityName)
    {
        $parse = self::parse();
        foreach ($parse as $key => $item) {
            if ($entityName == $key) {
                return $item;
            }
        }
    }


    /**
     * Get columns only
     * @param string $entityName
     * @return array
     */
    public static function getColumns(string $entityName)
    {
        $parse = self::parse();
        $columns = [];
        foreach ($parse as $key => $item) {
            if ($entityName == $key) {
                foreach ($item['props'] as $propKey => $prop) {
                    if (!$prop['isEntity']) {
                        $columns[] = $propKey;
                    } else {
                        if ($prop['relationType'] != 'many_to_one') {
                            $columns[] = $prop['foreignKey'];
                        }
                    }
                }
                return $columns;
            }
        }
    }


    /**
     * Get columns appended with table name
     * @param string $entityName
     * @return array
     */
    public static function getSelectColumns(string $entityName)
    {
        $parse = self::parse();
        $columns = [];
        foreach ($parse as $key => $item) {
            if ($entityName == $key) {
                foreach ($item['props'] as $propKey => $prop) {
                    if (!$prop['isEntity']) {
                        $columns[] = $item['table'] . '.' . $propKey;
                    } else {
                        if ($prop['relationType'] != 'many_to_one') {
                            $columns[] = $item['table'] . '.' . $prop['foreignKey'];
                        }
                    }
                }
                return $columns;
            }
        }
    }

    /**
     * Undocumented function
     *
     * @return array
     */
    public static function parse()
    {
        $result = array();
        $dir = Entity::register();
        $cdir = scandir($dir);
        foreach ($cdir as $key => $value) {
            if (!in_array($value, array(".", ".."))) {
                if (!is_dir($dir . DIRECTORY_SEPARATOR . $value)) {
                    $fileRead = Yaml::parseFile($dir . DIRECTORY_SEPARATOR . $value);
                    foreach ($fileRead as $key => $allProps) {
                        $result[$key] = $allProps;
                    }
                }
            }
        }
        return $result;
    }
}
