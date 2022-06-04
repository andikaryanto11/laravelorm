<?php

namespace LaravelOrm\Libraries;

use LaravelOrm\Exception\ListException;
use LaravelOrm\Interfaces\IList;
use ArrayIterator;
use Traversable;

class Lists implements IList
{
    /**
     *
     * @var array
     */
    protected $items = [];

    /**
     *
     * @param [type] $items
     */
    public function __construct($items)
    {
        $this->items = $items;
    }


    public function add($item)
    {
    }

    /**
     * Filter item wit criteria
     * @param Closure $callback
     * @return Lists
     */
    public function filter($callback)
    {
        $newdata = [];
        foreach ($this->items as $item) {
            if ($callback($item)) {
                $newdata[] = $item;
            }
        }
        $this->items = $newdata;
        return $this;
    }

    /**
     * Check if items empty
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Get data from item with from range
     * @param int $number
     * @return array
     */
    public function take($number): array
    {
        if ($number <= 0) {
            throw new ListException("Number must be greater than 0 (zero)");
        }

        if (count($this->items) < $number) {
            return  $this->items;
        } else {
            return array_slice($this->items, 0, $number);
        }
    }

    /**
     * Get index of item
     * @param Closure $callback
     * @return int
     */
    public function index($callback): int
    {
        $i = 0;
        foreach ($this->items as $item) {
            if ($callback($item)) {
                return $i;
            }
            $i++;
        }
        return null;
    }

    /**
     * Get first element data
     * @return
     */
    public function first()
    {
        if (empty($this->items)) {
            throw new ListException("Item empty");
        }

        return $this->items[0];
    }

    /**
     * Get all items
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Get last element data
     */
    public function last()
    {
        if (empty($this->items)) {
            throw new ListException("Item empty");
        }

        return end($this->items);
    }

    /**
     * will jsonize this class as this array items
     *
     * @return void
     */
    public function jsonSerialize()
    {
        return $this->items;
    }

    /**
     * get size of list
     * @return int
     */
    public function getSize()
    {
        return count($this->items);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }
}
