<?php

namespace LaravelOrm\Exception;

use Exception;

class CastException extends Exception
{
    protected $message = "";

    public function __construct($message)
    {
        $this->message = $message;
    }
}
