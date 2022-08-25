<?php

namespace LaravelOrm\Entities;

use LaravelOrm\Interfaces\IEntity;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class EntityManager
{
    /**
     *
     * @var Builder
     */
    protected Builder $builder;

    /**
     * @var IEntity $entity
     */
    protected IEntity $entity;

    /**
     * @var array $columns
     */
    protected array $columns;

    /**
     * @var array $props
     */
    protected array $props;

    /**
     * @var string $primaryKey
     */
    protected string $primaryKey;

    public function __construct()
    {

    }

    /**
     * Set Entity to persist
     *
     * @param mixed $entity
     * @return EntityManager
     */
    private function setEntity($entity)
    {
        $this->entity = $entity;
        $this->primaryKey = $this->entity->getPrimaryKeyName();
        $this->builder = DB::table($this->entity->getTableName());
        return $this;
    }

    /**
     * Start transactional database
     *
     * @return void
     */
    public function beginTransaction()
    {
       DB::beginTransaction();
    }

    /**
     * Rollback transactional database
     *
     * @return void
     */
    public function rollback()
    {
        DB::rollBack();
    }

    /**
     * Commit transactional database
     *
     * @return void
     */
    public function commit()
    {
        DB::commit();
    }

    /**
     * Persist data to storage
     *
     * @return bool
     */
    public function persist($entity)
    {
        $entity->beforePersist();
        $this->setEntity($entity);
        $primaryKey = 'get' . ucfirst($this->primaryKey);
        $primaryValue = $this->entity->$primaryKey();
        if (empty($primaryValue) || is_null($primaryValue)) {
            return $this->insert();
        } else {
            return $this->update();
        }
        return false;
    }

    /**
     * Update data
     * @return bool
     */
    private function update()
    {
        $data = $this->createArray();
        $getPrimaryKey = "get" . ucfirst($this->primaryKey);
        $this->builder->where($this->primaryKey, $this->entity->{$getPrimaryKey}());
        if ($this->builder->update($data)) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     * insert new data to table
     */
    private function insert()
    {

        $data = $this->createArray();
        $id = $this->builder->insertGetId($data);
        if ($id > 0) {
            $primaryKey = "set" . ucfirst($this->primaryKey);
            $this->entity->$primaryKey($id);
            return true;
        }

        return false;
    }

    /**
     * Remove data from table
     *
     * @param IEntity $entity
     * @return bool
     */
    public function remove(IEntity $entity)
    {

        $this->setEntity($entity);
        $getPrimaryKey = "get" . $this->primaryKey;
        $this->builder->where($this->primaryKey, $this->entity->{$getPrimaryKey}());
        if (!$this->builder->delete()) {
            return false;
        }

        $setPrimaryKey = "set" . $this->primaryKey;
        $this->entity->{$setPrimaryKey}(0);
        return true;
    }

    /**
     * Create array object to persist
     *
     * @return array
     */
    private function createArray()
    {
        return $this->entity->toArray();
    }
}
