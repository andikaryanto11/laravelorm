<?php

namespace LaravelOrm\Interfaces;

use AndikAryanto11\Libraries\Lists;
use LaravelOrm\Entities\EntityList;
use LaravelOrm\Exception\DatabaseException;
use LaravelOrm\Exception\EntityException;
use LaravelOrm\Libraries\Datatables;
use LaravelOrm\Libraries\EloquentDatatables;
use LaravelOrm\Libraries\EloquentList;

interface IRepository
{
    /**
     * Find data by id
     *
     * @param integer|string $id
     */
    public function find($id);

    /**
     * Find data by id or new entity instance
     *
     * @param int|string $id
     * @return IEntity
     */
    public function findOrNew($id);

    /**
     * Find data by id or throw error
     *
     * @param int|string $id
     * @return IEntity
     * @throws EntityException
     */
    public function findOrFail($id);

    /**
     * Find data by filter value
     *
     * @param  ?array $filter
     * @return ?IEntity|null
     */
    public function findOne($filter = []);

    /**
     * Find data by filter value or throw error
     *
     * @param  ?array $filter
     * @return IEntity|null
     * @throws DatabaseException
     */
    public function findOneOrFail($filter = []);

    /**
     * Find data by filter value or new entity
     *
     * @param  ?array $filter
     */
    public function findOneOrNew($filter = []);

    /**
     * Colect data by filter
     *
     * @param  ?array $filter
     * @return  ?IEntity[]|null
     */
    public function findAll(array $filter = [], $columns = []);

    /**
     * Colect data by filter
     *
     * @param  ?array $filter
     * @return  EntityList
     */
    public function collect($filter = []);

    /**
     * Find data by filter value
     *
     * @param ?array $filter
     * @return int
     */
    public function count($filter = []);

    /**
     * Get datatables server side  results array
     *
     * @param array $filter
     * @param boolean $returnEntity set to true array data will contain entity of class which call this function
     * @param boolean $useIndex set to false if datatables in front end use column name
     * @return Datatables
     */
    public function datatables($filter = [], $returnEntity = true, $useIndex = true);
}
