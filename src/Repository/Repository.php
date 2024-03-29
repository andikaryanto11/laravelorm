<?php

namespace LaravelOrm\Repository;

use LaravelOrm\Entities\EntityList;
use LaravelOrm\Entities\ORM;
use LaravelOrm\Exception\EntityException;
use LaravelOrm\Interfaces\IRepository;
use Illuminate\Database\Query\Builder;
use DateTime;
use Illuminate\Support\Facades\DB;
use LaravelOrm\Exception\DatabaseException;
use LaravelOrm\Interfaces\IEntity;

class Repository implements IRepository
{
    /**
     *
     *
     * @var Builder
     */
    protected $builder;

    /**
     *
     * @var string
     */
    protected string $entityClass;

    /**
     *
     * @var array
     */
    protected array $selectColumns;

    /**
     *
     * @var array
     */
    protected array $props;


    /**
     *
     * @param string $entityClass
     */
    public function __construct(
        string $entityClass
    ) {
        $this->entityClass = $entityClass;
        $this->props = ORM::getProps($this->entityClass);
        $this->builder = DB::table($this->props['table']);
        $this->selectColumns = ORM::getSelectColumns($this->entityClass);
    }

    public function getProps()
    {
        return $this->props;
    }

    /**
     * Create new instance of class
     *
     * @return mixed
     */
    public function newEntity()
    {
        $newEntity = new $this->entityClass();
        $primaryKey = 'set' . $this->props['primaryKey'];
        $newEntity->$primaryKey(0);
        return $newEntity;
    }

    /**
     *
     * @inheritDoc
     */
    public function find($id)
    {
        $param = [
            'where' => [
               [ $this->props['primaryKey'] , '=',  $id ]
            ]
        ];

        $result = $this->fetch($param);
        if (count($result) > 0) {
            return $result[0];
        }
        return null;
    }

    /**
     *
     * @inheritDoc
     */
    public function findOrNew($id)
    {
        $result = $this->find($id);
        if (empty($result)) {
            return $this->newEntity();
        }
        return $result;
    }

    /**
     *
     * @inheritDoc
     */
    public function findOrFail($id)
    {
        $result = $this->find($id);
        if (empty($result)) {
            throw new EntityException('Data with id ' . $id . ' not found');
        }
        return $result;
    }

    /**
     *
     * @inheritDoc
     */
    public function findOne($filter = [])
    {
        $result = $this->fetch($filter);
        if (count($result) > 0) {
            return $result[0];
        }
        return null;
    }

    /**
     *
     * @param int|string $id
     * @return mixed
     */
    public function findOneOrFail($filter = [])
    {
        $result = $this->findOne($filter);
        if (empty($result)) {
            throw new EntityException('Data not found');
        }
        return $result;
    }

    /**
     *
     * @param int|string $id
     * @return mixed
     */
    public function findOneOrNew($filter = [])
    {
        $result = $this->findOne($filter);
        if (empty($result)) {
            return $this->newEntity();
        }
        return $result[0];
    }

    /**
     * Will fetch array of entity.
     * Deprecated, use collect instead to better performance.
     * ::collect will lazy load related entity, and eager load of list entity
     *
     * @param array $filter
     * @param array $columns
     * @return array
     *
     * @deprecated <1.0.0
     */
    public function findAll(array $filter = [], $columns = [])
    {
        return $this->fetch($filter, $columns);
    }

    /**
     * Will fetch array of stdClass.
     *
     * @param array $filter
     * @param array $columns
     * @return array
     *
     */
    public function all(array $filter = [], $columns = [])
    {
        return $this->fetch($filter, $columns, true);
    }

    /**
     * set filter to query builder
     *
     * @param array $filter
     */
    private function setFilters($filter = [])
    {

        if (!empty($filter)) {
            $join = (isset($filter['join']) ? $filter['join'] : false);
            $where = (isset($filter['where']) ? $filter['where'] : false);
            $wherein = (isset($filter['whereIn']) ? $filter['whereIn'] : false);
            $orwherein = (isset($filter['orWhereIn']) ? $filter['orWhereIn'] : false);
            $orwhere = (isset($filter['orWhere']) ? $filter['orWhere'] : false);
            $wherenotin = (isset($filter['whereNotIn']) ? $filter['whereNotIn'] : false);
            $like = (isset($filter['like']) ? $filter['like'] : false);
            $orlike = (isset($filter['orLike']) ? $filter['orLike'] : false);
            $notlike = (isset($filter['notLike']) ? $filter['notLike'] : false);
            $ornotlike = (isset($filter['orNotLike']) ? $filter['orNotLike'] : false);
            $order = (isset($filter['order']) ? $filter['order'] : false);
            $limit = (isset($filter['limit']) ? $filter['limit'] : false);
            $group = (isset($filter['group']) ? $filter['group'] : false);

            if ($join) {
                foreach ($join as $key => $vv) {
                    foreach ($vv as $v) {
                        $type = "inner";
                        if (isset($v['type'])) {
                            $type = $v['type'];
                        }

                        if (strtoupper($type) == 'INNER') {
                            $this->builder->join($key, ...$v['key']);
                        }
                        if (strtoupper($type) == 'LEFT') {
                            $this->builder->leftJoin($key, ...$v['key']);
                        }
                        if (strtoupper($type) == 'RIGHT') {
                            $this->builder->rightJoin($key, ...$v['key']);
                        }
                    }
                }
            }
            if ($where) {
                $this->builder->where($where);
            }

            if ($orwhere) {
                $this->builder->orWhere($orwhere);
            }

            if ($wherein) {
                foreach ($wherein as $key => $v) {
                    if (!empty($v)) {
                        $this->builder->whereIn($key, $v);
                    }
                }
            }

            if ($orwherein) {
                foreach ($orwherein as $key => $v) {
                    if (!empty($v)) {
                        $this->builder->orWhereIn($key, $v);
                    }
                }
            }

            if ($wherenotin) {
                foreach ($wherenotin as $key => $v) {
                    if (!empty($v)) {
                        $this->builder->whereNotIn($key, $v);
                    }
                }
            }


            if ($group) {
                $this->builder->groupStart();
                foreach ($group as $key => $v) {
                    if ($key == 'orLike') {
                        foreach ($v as $orLikeKey => $orLikeValue) {
                            $this->builder->orLike($orLikeKey, $orLikeValue);
                        }
                    }
                    if ($key == 'and') {
                        foreach ($v as $andKey => $andValue) {
                            $this->builder->where([$andKey => $andValue]);
                        }
                    }
                }
                $this->builder->groupEnd();
            }

            if ($order) {
                foreach ($order as $key => $v) {
                    if (!empty($v)) {
                        $this->builder->orderBy($key, $v);
                    }
                }
            }

            if ($limit) {
                $this->builder->limit($limit['size'])->offset(($limit['page'] - 1) *  $limit['size']);
            }
        }
    }

    /**
     * Undocumented function
     *
     * @param array $filter
     * @param array $columns
     * @return array
     */
    private function fetch(array $filter = [], $columns = [], $stdClass = false, &$associated = [])
    {

        $this->setFilters($filter);

        $results = null;

        if (empty($columns)) {
            $selectColumns = $this->selectColumns;
            $results =  $this->builder->select($selectColumns)->get();
        } else {
            $selectColumns = implode(",", $columns);
            $results = $this->builder->select($selectColumns)->get();
        }

        $finalResults = null;
        if (!$stdClass) {
            $finalResults = $this->setToEntity($results, $associated);
        } else {
            $finalResults = $results;
        }
        return $finalResults;
    }

    /**
     * convert all result to intended entity
     *
     * @param stdClass[] $results
     * @return array;
     */
    private function setToEntity($results, &$associated = [])
    {
        $objects = [];
        foreach ($results as $key => $result) {
            $obj = new $this->entityClass();

            foreach ($this->props['props'] as $key => $value) {
                // if (!is_null($result->$key)) {
                $method = 'set' . ucfirst($key);
                $column = $value['field'];
                if (!$value['isEntity']) {
                    if (!is_null($result->$column)) {
                        if ($value['type'] != 'DateTime') {
                            if ($value['type'] == 'boolean') {
                                $obj->$method((bool)$result->$column);
                            } else {
                                $obj->$method($result->$column);
                            }
                        } else {
                            $newDate = new DateTime($result->$column);
                            $obj->$method($newDate);
                        }
                    }
                } else {
                    if (isset($value['foreignKey']) && $value['relationType'] != 'many_to_many') {
                        $foreignKey = $value['foreignKey'];
                        if (!is_null($result->$foreignKey)) {
                            $associated[$value['foreignKey']][] = $result->$foreignKey;
                            $obj->constraints[$value['foreignKey']] = $result->$foreignKey;
                        }
                    }
                }
            }
            $objects[] = $obj;
        }

        $newAssociated = [];
        foreach ($associated as $key => $value) {
            $newAssociated[$key] = array_unique($associated[$key]);
        }

        $associated = $newAssociated;

        return $objects;
    }

    /**
     * @inheritDoc
     */
    public function collect($filter = [], $columns = [])
    {
        $associated = [];
        $result = $this->fetch($filter, $columns, false, $associated);
        $entityList = new EntityList($result);
        $entityList->setListOf($this->entityClass);
        $entityList->setAssociatedKey($associated);
        return $entityList;
    }

    /**
     * Undocumented function
     *
     * @param $results - results of query builder
     * @return void
     */
    public function toEntities($results)
    {
        $associated = [];
        $entities = $this->setToEntity($results, $associated);
        $entityList = new EntityList($entities);
        $entityList->setListOf($this->entityClass);
        $entityList->setAssociatedKey($associated);
        return $entityList;
    }

    /**
     * @inheritDoc
     */
    public function count($filter = [])
    {
        $result = $this->all($filter);
        return empty($result) ? 0 : count($result);
    }

    /**
     * @inheritDoc
     */
    public function datatables($filter = [], $returnEntity = true, $useIndex = true)
    {
        $datatables = new RepositoryDatatables($filter, $returnEntity, $useIndex, $this);
        return $datatables;
    }

    public function validateEntity(IEntity $entity)
    {
        $entity->validate();
    }
}
