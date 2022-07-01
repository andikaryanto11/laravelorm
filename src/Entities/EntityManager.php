<?php

namespace LaravelOrm\Entities;

use LaravelOrm\Interfaces\IEntity;
use Illuminate\Database\Query\Builder;
use CodeIgniter\Database\BaseConnection;
use DateTime;
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


    /**
     * @var array $reservedField
     */
    protected array $reservedField = [
        'created_at',
        'updated_at'
    ];

    public function __construct()
    {

    }

    /**
     * Set Entity to persist
     *
     * @param IEntity $entity
     * @return EntityManager
     */
    private function setEntity(IEntity $entity)
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
        $this->db->transStart();
    }

    /**
     * Rollback transactional database
     *
     * @return void
     */
    public function rollback()
    {
        $this->db->rollback();
    }

    /**
     * Commit transactional database
     *
     * @return void
     */
    public function commit()
    {
        $this->db->commit();
    }

    /**
     * Persist data to storage
     *
     * @return bool
     */
    public function persist(IEntity $entity)
    {
        $this->setEntity($entity);
        $primaryKey = 'get' . $this->primaryKey;
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
        $entityAsArray = [];
        $props = $this->entity->getProps();
        foreach ($props as $key => $prop) {
            $getFunction = 'get' . ucfirst($key);
            $primaryKey = 'get' . ucfirst($this->primaryKey);
            $field = $prop['field'];
            if (!$prop['isEntity']) {
                if (strtolower($prop['type']) != 'datetime') {
                    $entityAsArray[$field] = $this->entity->$getFunction();
                } else {
                    if (in_array($field, $this->reservedField)) {
                        $setDate = 'set' .  $key;
                        $date = new DateTime();
                        if (empty($this->entity->$primaryKey()) && $field == 'created_at') {
                            $this->entity->$setDate($date);
                            $entityAsArray[$field] = $date->format('Y-m-d h:i:s');
                        }

                        if (!empty($this->entity->$primaryKey()) && $field == 'updated_at') {
                            $this->entity->$setDate($date);
                            $entityAsArray[$field] = $date->format('Y-m-d h:i:s');
                        }
                    } else {
                        $entityAsArray[$field] = $this->entity->$getFunction()->format('Y-m-d h:i:s');
                    }
                }
            } else {
                if (isset($prop['foreignKey'])) {
                    $relatedEntity = ORM::getProps($prop['type']);
                    $relatedPrimaryKey = $relatedEntity['primaryKey'];
                    $getPrimaryKey = 'get' . $relatedPrimaryKey;
                    $entityAsArray[$prop['foreignKey']] = $this->entity->$getFunction()->$getPrimaryKey();
                }
            }
        }
        return $entityAsArray;
    }
}
