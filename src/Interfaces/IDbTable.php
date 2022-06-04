<?php

namespace LaravelOrm\Interfaces;

interface IDbTable
{
    /**
     * get table name
     * @return string
     */
    public function getTableName();


    /**
     * get table name
     * @return string
     */
    public function getPrimaryKey();
}
