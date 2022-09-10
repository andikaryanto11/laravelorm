<?php

namespace LaravelOrm\Queries;

use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use LaravelOrm\Entities\EntityList;
use LaravelOrm\Entities\ORM;
use LaravelOrm\Interfaces\IEntity;
use LaravelOrm\Repository\Repository;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Illuminate\Database\Query\Processors\Processor;
use LaravelOrm\Exception\DatabaseException;

abstract class Query extends Builder
{
    /**
     * Create a new query builder instance.
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @param  \Illuminate\Database\Query\Grammars\Grammar|null  $grammar
     * @param  \Illuminate\Database\Query\Processors\Processor|null  $processor
     * @return void
     */
    public function __construct(
        ConnectionInterface $connection,
        Processor $processor = null
    ) {
        $grammar = $connection->query()->getGrammar();
        parent::__construct($connection, $grammar, $processor);
        $columns =  ORM::getSelectColumnsAs($this->identity());
        $table = $this->getIdentityTable();
        $this->from($table)->select($columns);
    }

    /**
     * Undocumented function
     *
     * @return string
     */
    abstract public function identity();

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
        $results = $this->get();
        $repository = new Repository($this->identity());
        $entities = $repository->toEntities($results);
        return $entities;
    }

    /**
     * Undocumented function
     *
     * @throws DatabaseException
     * @return mixed
     */
    public function getFirstOrError()
    {
        $iterator = $this->getIterator();
        if ($iterator->count() > 0) {
            return $iterator->first();
        }
        throw new DatabaseException('No Data Found in database');
    }

    /**
     * Undocumented function
     *
     * @return mixed|null
     */
    public function getFirst()
    {
        $iterator = $this->getIterator();
        if ($iterator->count() > 0) {
            return $iterator->first();
        }
        return null;
    }
}
