<?php

namespace LaravelOrm\Entities;

/**
 * This class is used to cache N+1 query eager load loop
 */
class EntityLooper
{
    /**
     *
     * @var array
     */
    private static array $instance = [];

    /**
     *
     * @var array
     */
    private array $items = [];

    /**
     *
     * @var EntityList
     */
    private ?EntityList $entityList = null;

    /**
     *
     * @var boolean
     */
    private bool $isLastIndex = false;

    private function __construct()
    {
    }

    /**
     *
     * @return EntityLooper
     */
    public static function getInstance($key)
    {
        if (!isset(static::$instance[$key])) {
            static::$instance[$key] = new static();
        }
        return static::$instance[$key];
    }

    /**
     * Clean the item that had been collected
     *
     * @return void
     */
    public function clean()
    {
        $this->isLastIndex = false;
        $this->entityList = null;
        $this->items = [];
        return $this;
    }

    /**
     * check if current loop has data
     * @return boolean
     */
    public function hasEntityList()
    {
        return !empty($this->entityList);
    }

    /**
     * @param array $items
     * @return EntityLooper
     */
    public function setItems(array $items)
    {
        $this->items = $items;
        return $this;
    }


    /**
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param EntityList $entityList
     * @return EntityLooper
     */
    public function setEntityList(EntityList $entityList)
    {
        $this->entityList = $entityList;
        return $this;
    }


    /**
     * @return EntityList
     */
    public function getEntityList()
    {
        return $this->entityList;
    }

    /**
     *
     * @return boolean
     */
    public function isLastIndex()
    {
        return $this->isLastIndex;
    }

    /**
     *
     * @param boolean $isLastIndex
     * @return EntityLooper
     */
    public function setIsLastIndex(bool $isLastIndex)
    {
        $this->isLastIndex = $isLastIndex;
        return $this;
    }
}
