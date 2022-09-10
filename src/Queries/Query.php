<?php

namespace LaravelOrm\Queries;

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

    public function getFirst()
    {
        $iterator = $this->getIterator();
        return $iterator->first();
    }
}
