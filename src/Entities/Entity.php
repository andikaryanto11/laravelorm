<?php

namespace LaravelOrm\Entities;

use DateTime;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use LaravelOrm\Exception\DatabaseException;
use LaravelOrm\Exception\ValidationException;
use LaravelOrm\Interfaces\IEntity;
use LaravelOrm\Repository\Repository;
use Mockery\Generator\StringManipulation\Pass\Pass;
use ReflectionClass;

class Entity implements IEntity
{

    /**
     * @var array $reservedField
     */
    protected array $reservedField = [
        'created_at',
        'created_by',
        'updated_at',
        'updated_by'
    ];

    /**
     * Rules
     *
     * @var array
     */
    protected array $rules = [];


    /**
     *
     * @var array
     */
    public array $constraints = [];

    /**
     *
     * @var array
     */
    protected array $props;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->props = ORM::getProps(get_class($this));
    }

    /**
     * @inheritDoc
     */
    public function getPrimaryKeyName()
    {
        return $this->props['primaryKey'];
    }

    /**
     * @inheritDoc
     */
    public function getProps()
    {
        return $this->props['props'];
    }

    /**
     * @inheritDoc
     */
    public function getTableName()
    {
        return $this->props['table'];
    }

    /**
     * Undocumented function
     *
     * @param array $data
     * @return void
     */
    public function hydrate($data)
    {
        foreach ($this->getProps() as $key => $value) {
            if (key_exists($value['field'], $data) && !in_array($value['field'], $this->reservedField)) {
                $fn = 'set' . ucfirst($key);
                $newValue = $data[$value['field']];
                $this->$fn($newValue);
            }
        }
        return $this;
    }

    /**
     * Create array object to persist
     *
     * @return array
     */
    public function toArray()
    {
        $entityAsArray = [];
        $props = $this->getProps();

        if (!empty($this->rules)) {
            $this->rules = [];
        }

        foreach ($props as $key => $prop) {
            $this->addRules($prop);
            $getFunction = 'get' . ucfirst($key);
            $primaryKey = 'get' . ucfirst($this->getPrimaryKeyName());
            $field = $prop['field'];
            if (!$prop['isEntity']) {
                if (strtolower($prop['type']) != 'datetime') {
                    $entityAsArray[$field] = $this->$getFunction();
                } else {
                    if (in_array($field, $this->reservedField)) {
                        $setDate = 'set' .  $key;
                        $date = new DateTime();
                        if (empty($this->$primaryKey()) && $field == 'created_at') {
                            $this->$setDate($date);
                            $entityAsArray[$field] = $date->format('Y-m-d h:i:s');
                        }

                        if (!empty($this->$primaryKey()) && $field == 'updated_at') {
                            $this->$setDate($date);
                            $entityAsArray[$field] = $date->format('Y-m-d h:i:s');
                        }
                    } else {
                        if(!empty($this->$getFunction())){
                            $entityAsArray[$field] = $this->$getFunction()->format('Y-m-d h:i:s');
                        }
                    }
                }
            } else {
                if (isset($prop['foreignKey'])) {   
                    $relatedEntity = ORM::getProps($prop['type']);
                    $relatedPrimaryKey = $relatedEntity['primaryKey'];
                    $getPrimaryKey = 'get' . ucfirst($relatedPrimaryKey);
                    if (!is_null($this->$getFunction())) {
                        $entityAsArray[$prop['foreignKey']] = $this->$getFunction()->$getPrimaryKey();
                    }
                }
            }
        }
        return $entityAsArray;
    }

    /**
     * Executed before persistence occur
     *
     * @return void
     */
    protected function beforePersist()
    {
    }

    /**
     * Validate the entity
     *
     * @return void
     */
    public function validate()
    {
        $validator = Validator::make($this->toArray(), $this->rules);
        if ($validator->fails()) {
            throw new ValidationException($validator->errors()->all()[0]);
        }
    }

    /**
     * add rule
     *
     * @param array $prop
     * @return void
     */
    private function addRules($prop)
    {
        if (isset($prop['rule'])) {
            $rules = explode('|', $prop['rule']);
            $fieldRules = [];
            foreach($rules as $rule){
                if($rule == 'password'){
                    $fieldRules[] = Password::min(8)->numbers()->mixedCase()->symbols();
                } else {
                    $fieldRules[] = $rule;
                }
            }
            $this->rules[$prop['field']] = $fieldRules;
        }
        return $this;
    }

    public function __call($name, $arguments)
    {
        $method = substr($name, 0, 3);
        if ($method == 'get') {
            $currentClass = get_class($this);
            $reflect = (new ReflectionClass($this))->getMethod($name)->getReturnType();
            $returnType = $reflect->getName();

            $arrayType = explode("\\", $returnType);

            $arrClass = explode('\\', $currentClass);

            $field = substr($name, 3);
            $variableName = lcfirst($field);
            if (count($arrayType) > 1) {
                $classIndex = count($arrayType) - 1;
                if ($arrayType[$classIndex] == 'EntityList') {
                    $dataExist = call_user_func_array([$this, $name], $arguments);

                    if (!empty($dataExist)) {
                        return $dataExist;
                    }

                    $currentProps = ORM::getProps($currentClass);
                    $selectedProp = $currentProps['props'][$variableName];
                    $primarykey = 'get' . $currentProps['primaryKey'];
                    $list = null;

                    if ($selectedProp['relationType'] == 'many_to_many') {
                        $mapping = $selectedProp['mapping'];
                        $mappingEntity = $mapping['type'];
                        $param = [
                            'where' => [
                                [$mapping['foreignKey'], '=', $this->$primarykey()]
                            ]
                        ];
                        $mappingList = (new Repository($mappingEntity))->collect($param);

                        if (count($mappingList->getAssociatedKey()) > 0) {
                            $relatedEntity = $selectedProp['type'];
                            $relatedProps = ORM::getProps($relatedEntity);

                            $mainKey = $mapping['mainKey'];
                            $param = [
                                'whereIn' => [
                                    $relatedProps['primaryKey'] => $mappingList->getAssociatedKey()[$mainKey]
                                ]
                            ];
                            $list = (new Repository($relatedEntity))->collect($param);
                        }
                    } else {
                        $relatedEntity = $selectedProp['type'];

                        $relatedProps = ORM::getProps($relatedEntity);

                        $currentClassIndex = count($arrClass) - 1;
                        $relatedKey = lcfirst($arrClass[$currentClassIndex]);
                        $foreignKey = $relatedProps['props'][$relatedKey]['foreignKey'];

                        $param = [
                            'where' => [
                                [$foreignKey, '=',  $this->$primarykey()]
                            ]
                        ];
                        $list = (new Repository($relatedEntity))->collect($param);
                    }

                    if (!is_null($list)) {
                        $setFn = 'set' . $field;
                        call_user_func_array([$this, $setFn], [$list]);
                    }
                } else {
                    $dataExist = call_user_func_array([$this, $name], $arguments);

                    if (!empty($dataExist)) {
                        return $dataExist;
                    }

                    $currentProps = ORM::getProps($currentClass);
                    $relatedEntity = $currentProps['props'][$variableName]['type'];
                    $foreignKey = $currentProps['props'][$variableName]['foreignKey'];

                    $listOf = get_class($this);
                    $looper = EntityLooper::getInstance($listOf);

                    // which mean this call comes from loop EntityList
                    if ($looper->hasEntityList()) {
                        $entitylist = $looper->getEntityList();
                        $primaryKey = '';
                        $relatedClass = ORM::getProps($relatedEntity);
                        $primaryKey = $relatedClass['primaryKey'];
                        if (empty($looper->getItems()) && count($entitylist->getAssociatedKey()) > 0) {
                            $param = [
                                'whereIn' => [
                                    $primaryKey => $entitylist->getAssociatedKey()[$foreignKey]
                                ]
                            ];

                            $entities = (new Repository($relatedEntity))->collect($param)->getItems();
                            $items = [];
                            foreach ($entities as $entity) {
                                $getFn = 'get' . $primaryKey;
                                $pkValue = $entity->$getFn();
                                $items[$pkValue] = $entity;
                            }
                            $looper->setItems($items);
                        }

                        $result = null;
                        $itemOfLooper = $looper->getItems();
                        if (count($itemOfLooper) > 0) {
                            if (!empty($this->constraints)) {
                                $keyValue = $this->constraints[$foreignKey];
                                if (isset($itemOfLooper[$keyValue])) {
                                    $result = $itemOfLooper[$keyValue];
                                }
                            }
                        }

                        if ($looper->isLastIndex()) {
                            $looper->clean();
                        }

                        if (!is_null($result)) {
                            $setFn = 'set' . $field;
                            call_user_func_array([$this, $setFn], [$result]);
                        }
                    } else {
                        $key = isset($this->constraints[$foreignKey]) ? $this->constraints[$foreignKey] : null;
                        if (!empty($key)) {
                            $instance = (new Repository($relatedEntity))->find($key);

                            $setFn = 'set' . $field;
                            call_user_func_array([$this, $setFn], [$instance]);
                        }
                    }
                }
            }
            return call_user_func_array([$this, $name], []);
        } else {
            return call_user_func_array([$this, $name], $arguments);
        }
    }
}
