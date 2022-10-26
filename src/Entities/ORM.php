<?php

namespace LaravelOrm\Entities;

use Symfony\Component\Yaml\Yaml;

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
                        $columns[] = $prop['field'];
                    } else {
                        if ($prop['relationType'] != 'many_to_one' && $prop['relationType'] != 'many_to_many') {
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
                        $columns[] = $item['table'] . '.' . $prop['field'];
                    } else {
                        if ($prop['relationType'] != 'many_to_one' && $prop['relationType'] != 'many_to_many') {
                            $columns[] = $item['table'] . '.' . $prop['foreignKey'];
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
    public static function getSelectColumnsAs(string $entityName)
    {
        $parse = self::parse();
        $columns = [];
        foreach ($parse as $key => $item) {
            if ($entityName == $key) {
                foreach ($item['props'] as $propKey => $prop) {
                    if (!$prop['isEntity']) {
                        $columns[$prop['field']] = $item['table'] . '.' . $prop['field'];
                    } else {
                        if ($prop['relationType'] != 'many_to_one' && $prop['relationType'] != 'many_to_many') {
                            $columns[$prop['field']] = $item['table'] . '.' . $prop['foreignKey'];
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
        $config = config()->get('common-config');
        if($config){
            $dir = ['entity']['mapping']['app'];
            $cdir = scandir($dir);
            foreach ($cdir as $key => $value) {
                if (!in_array($value, array(".", ".."))) {
                    $pathFile = $dir . DIRECTORY_SEPARATOR . $value;
                    if (!is_dir($dir . DIRECTORY_SEPARATOR . $value)) {
                        $extension = pathinfo($pathFile, PATHINFO_EXTENSION);
                        if ($extension == 'yml') {
                            $fileRead = Yaml::parseFile($pathFile);
                            foreach ($fileRead as $key => $allProps) {
                                $result[$key] = $allProps;
                            }
                        }
                    }
                }
            }

            $dir = base_path() . '/vendor/andikaryanto11/laravelcommon/src/App/Entities/Mapping';
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
        return [];
    }
}
