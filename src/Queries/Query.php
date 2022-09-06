<?php

namespace LaravelOrm\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use LaravelOrm\Entities\EntityList;
use LaravelOrm\Entities\ORM;
use LaravelOrm\Interfaces\IEntity;
use LaravelOrm\Repository\Repository;

abstract class Query extends Builder
{
    /**
     * Undocumented function
     *
     * @return string
     */
    public abstract function identity();

    public function getIdentityTable()
    {
        $props = ORM::getProps($this->identity());
        return $props['table'];
    }

    /**
     * Filter By Id
     *
     * @param [type] $id
     * @return static 
     * 
     */
    public function whereId($id): static
    {
        $this->where($this->getIdentityTable() . '.id', '=', $id);
        return $this;
    }

    /**
     * Get EntityList instance
     *
     * @return EntityList
     */
    public function getIterator()
    {
        $table = $this->getIdentityTable();
        $columns =  ORM::getSelectColumns($this->identity());

        $results = $this->from($table)->select($columns)->get();

        $repository = new Repository($this->identity());
        $entities = $repository->toEntities($results);
        return $entities;
    }

    public function getFirst()
    {
        $iterator = $this->getIterator();
        return $iterator->first();
    }
}
