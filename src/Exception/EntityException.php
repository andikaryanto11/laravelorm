<?php

namespace LaravelOrm\Exception;

use Exception;

class EntityException extends Exception
{
    protected $message = null;
    public function __construct($message)
    {
        parent::__construct($message);
        $this->message = $message;
    }
}
