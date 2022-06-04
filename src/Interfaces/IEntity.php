<?php

namespace LaravelOrm\Interfaces;

interface IEntity
{
    /**
     * Get primary key of an entity
     *
     * @return string
     */
    public function getPrimaryKeyName();

    /**
     * Get table name of an entity
     *
     * @return string
     */
    public function getTableName();

    /**
     * Get all field Props
     *
     * @return array
     */
    public function getProps();
}
