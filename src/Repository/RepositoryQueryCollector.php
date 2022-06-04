<?php

namespace LaravelOrm\Repository;

use LaravelOrm\Entity\ORM;
use LaravelOrm\Interfaces\IEntity;

class RepositoryQueryCollector
{
    /**
     * @var array
     */
    protected array $queries = [];

    protected static $instance = null;

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function addQueryAndResult(string $key, $result)
    {
        $this->queries[$key] = $result;
    }

    public function getQueries()
    {
        return $this->queries;
    }

    public function getQuery($key)
    {
        return isset($this->queries[$key]) ? $this->queries[$key] : null;
    }

    public function clean()
    {
        $this->queries = [];
    }
}
