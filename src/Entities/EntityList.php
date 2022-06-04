<?php

namespace LaravelOrm\Entities;

use LaravelOrm\Exception\ListException;
use LaravelOrm\Libraries\Lists;
use Iterator;
use PhpParser\Node\Expr\FuncCall;

class EntityList extends Lists implements Iterator
{
    /**
     *
     * @var string
     */
    protected $eloquentclass = "";

    /**
     *
     * @var array
     */
    protected array $associatedKey = [];

    /**
     *
     * @var string
     */
    protected $listOf = '';

    /**
     *
     * @param array $items
     */
    public function __construct($items = [])
    {
        parent::__construct($items);
    }

    /**
     * Set this list of and entity type
     *
     * @param string $listOf
     * @return EntityList
     */
    public function setListOf(string $listOf)
    {
        $this->listOf = $listOf;
        return $this;
    }

    /**
     *
     * @return string
     */
    public function getListOf()
    {
        return $this->listOf;
    }

    /**
     *
     * @param array $associatedKey
     * @return EntityList
     */
    public function setAssociatedKey($associatedKey)
    {
        $this->associatedKey = $associatedKey;
        return $this;
    }

    /**
     *
     * @return array
     */
    public function getAssociatedKey()
    {
        return $this->associatedKey;
    }

    /**
     * Add eloquent obeject
     * @return void
     */
    public function add($item)
    {

        // if ($this->eloquentclass != get_class($item)) {
        //     $classname = $this->eloquentclass;
        //     $ginevclassname = get_class($item);
        //     throw new ListException("Cannot add item, expected $classname, $ginevclassname given");
        // }

        $this->items[] = $item;
    }

    /**
     * Get data value form column name
     */

    public function chunk(string $columnName)
    {
        $data = [];
        foreach ($this->items as $item) {
            if (!property_exists($item, $columnName)) {
                throw new ListException("Column '$columnName' is not found");
            }
            $data[] = $item->{"get$columnName"}();
        }
        return $data;
    }

    /**
     * Get data value form column name
     */

    public function chunkUnique(string $columnName)
    {
        $data = [];
        foreach ($this->items as $item) {
            if (!property_exists($item, $columnName)) {
                throw new ListException("Column '$columnName' is not found");
            }
            if (!in_array($item->{"get$columnName"}(), $data)) {
                $data[] = $item->{"get$columnName"}();
            }
        }
        return $data;
    }

    /**
     * loop Items and return each
     * @param funtion fn($item)
     */
    public function each($callback)
    {
        foreach ($this->items as $item) {
            $callback($item);
        }
    }

    /**
     * Get eloquent unsaved data means Id of eloquent is null
     *
     */
    public function unSaved()
    {
        return $this->filter(function ($item) {
            return empty($item->{'get' . $item->getPrimaryKeyName()}());
        });
    }

    /**
     * Get eloquent saved data means Id of eloquent is not null
     *
     */
    public function saved()
    {
        return $this->filter(function ($item) {
            return !empty($item->{'get' . $item->getPrimaryKeyName()}());
        });
    }

    /**
     * Sum value of field
     * @param string $columnName
     *
     */
    public function sum($columnName)
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->{"get$columnName"}();
        }
        return $total;
    }

    /**
     * Average value of field
     * @param string $columnName
     *
     */
    public function avg($columnName)
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->{"get$columnName"}();
        }
        return $total / count($this->items);
    }

    /**
     * Minimal value of field, if $return set 'object' then object model will be returned otherwise value of field
     * @param string $columnName
     * @param string $return 'object' / 'field'
     */
    public function min($columnName, $return = "object")
    {
        $min = 0;
        $data = null;
        foreach ($this->items as $item) {
            if (is_null($data)) {
                $data = $item;
                $min = $item->{"get$columnName"}();
                continue;
            }

            if ($item->{"get$columnName"}() < $min) {
                $data = $item;
                $min = $item->{"get$columnName"}();
            }
        }
        return $return == "object" ? $data : $min;
    }

     /**
     * Maximal value of field, if $return set 'object' then object model will be returned otherwise value of field
     * @param string $columnName
     */
    public function max($columnName, $return = "object")
    {
        $max = 0;
        $data = null;
        foreach ($this->items as $item) {
            if (is_null($data)) {
                $data = $item;
                $max = $item->{"get$columnName"}();
                continue;
            }

            if ($item->{"get$columnName"}() > $max) {
                $data = $item;
                $max = $item->{"get$columnName"}();
            }
        }
        return $return == "object" ? $data : $max;
    }

    /**
     * Get only unique data, data with no duplicate / same Id
     * @return EloquestList
     */
    public function unique()
    {
        $keys = [];
        $data = [];
        foreach ($this->items as $item) {
            if (!in_array($item->{'get' . $item->getPrimaryKeyName()}(), $keys)) {
                $keys[] = $item->{'get' . $item->getPrimaryKeyName()};
                $data[] = $item;
            } else {
                $index = 0;
                foreach ($keys as $key) {
                    if ($key == $item->{'get' . $item->getPrimaryKeyName()}()) {
                    }
                        break;
                    $index++;
                }
                array_splice($keys, $index, 1);
                array_splice($data, $index, 1);
            }
        }
        $this->items = $data;
        return $this;
    }

    //+++++++++ Iterator ++++++++++=

    /**
     * @inheritDoc
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * @inheritDoc
     */
    public function current()
    {

        $looper = EntityLooper::getInstance($this->getListOf());
        if (!$looper->hasEntityList()) {
            $looper->setEntityList($this);
        }

        $isLastIndex = $this->position == count($this->items) - 1;
        $looper->setIsLastIndex($isLastIndex);
        return $this->items[$this->position];
    }

    /**
     * @inheritDoc
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * @inheritDoc
     */
    public function next(): void
    {
        ++$this->position;
    }

    /**
     * @inheritDoc
     */
    public function valid(): bool
    {
        return isset($this->items[$this->position]);
    }
}
