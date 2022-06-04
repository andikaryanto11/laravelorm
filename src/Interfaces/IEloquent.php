<?php

namespace LaravelOrm\Interfaces;

use LaravelOrm\Exception\DatabaseException;

interface IEloquent
{
    /**
     * Check if intance has changed value from orginal daata
     * @return bool
     */
    public function isDirty();

    /**
     * @param array $filter
     * @return bool
     *
     * check if data exist
     */
    public function isDataExist(array $filter);

    /**
     * @param array $columnName
     * @return array Of specific column data
     *
     * get all column data
     */
    public function chunk($columnName);

    /**
     * @param array $filter
     * @return array
     *
     * get all data result from table
     */
    public function fetch(array $filter = [], $returnEntity = true, $columns = []);

    /**
     * will be executed before save function
     */
    public function beforeSave();

     /**
     * @return bool
     * insert new data to table if $Id is empty or null other wise update the data
     * @param bool $isAutoIncrement your primary key of table
     */

    public function save($isAutoIncrement = true);

    /**
     * Delete data where primary key in object is not null, throw while primary key null
     * @throws DatabaseException
     */
    public function delete();

    /**
     * @param string $relatedEloquent Relates Table \App\Eloquent\YourClass
     * @param string $foreignKey key name of this Eloquent
     * @return Eloquent Object or null
     *
     * Get parent related table data
     */
    public function hasOne(string $relatedEloquent, string $foreignKey, $params = []);

     /**
     * @param string $relatedEloquent Relates Table \App\Eloquent\YourClass
     * @param string $foreignKey key name of this Eloquent
     * @return Eloquent Object or New Object
     *
     * Get parent related table data
     */
    public function hasOneOrNew(string $relatedEloquent, string $foreignKey, $params = []);

     /**
     * @param string $relatedEloquent Relates Table \App\Eloquent\YourClass
     * @param string $foreignKey key name of this Eloquent
     * @param array $params
     * @return Eloquent Object or Error
     *
     * Get parent related table data
     */
    public function hasOneOrFail(string $relatedEloquent, string $foreignKey, $params = []);

     /**
     * Reverse of has one
     * @param string $relatedEloquent Relates Table \App\Eloquent\YourClass
     * @param string $foreignKey key name of this Eloquent
     * @param array $params
     * @return null
     */
    public function belongsTo(string $relatedEloquent, string $foreignKey, $params = []);

    /**
     * Reverse of has one
     * @param string $relatedEloquent Relates Table \App\Eloquent\YourClass
     * @param string $foreignKey key name of this Eloquent
     * @param array $params
     * @return Eloquen
     * @throws DatabaseException
     */
    public function belongsToOrFail(string $relatedEloquent, string $foreignKey, $params = []);

    /**
     * @param string $relatedEloquent Relates Table \App\Eloquent\YourClass
     * @param string $foreignKey key name of related Eloquent
     * @param string $params param to filter data
     * @return Eloquent array Object or null
     *
     * Get child related table data
     */
    public function hasMany(string $relatedEloquent, string $foreignKey, $params = []);

    /**
     * @param string $relatedEloquent Relates Table \App\Eloquent\YourClass
     * @param string $foreignKey key name of related Eloquent
     * @param string $params param to filter data
     * @return Eloquent array Object or null
     *
     * Get child related table data
     */
    public function hasFirst(string $relatedEloquent, string $foreignKey, $params = []);

    /**
     * @param string $relatedEloquent Relates Table \App\Eloquent\YourClass
     * @param string $foreignKey key name of related Eloquent
     * @param string $params param to filter data
     * @return Eloquent array Object or null
     *
     * Get child related table data
     */
    public function hasFirstOrNew(string $relatedEloquent, string $foreignKey, $params = []);

     /**
     * @param string $relatedEloquent Related Table \App\Eloquent\YourClass
     * @param string $foreignKey key name of related Eloquent
     * @param string $params param to filter data
     * @return Eloquent array Object or Error
     *
     * Get child related table data
     */
    public function hasManyOrFail(string $relatedEloquent, string $foreignKey, $params = array());

    /**
     * Return data before it's modified
     * @return static
     */
    public function getOriginalData();

    /**
     * Validate an Eloquent object
     */
    public function validate();
}
