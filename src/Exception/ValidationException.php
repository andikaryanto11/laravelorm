<?php

namespace LaravelOrm\Exception;

use Exception;

class ValidationException extends Exception
{
    /**
     * Undocumented variable
     *
     * @var array
     */
    protected array $errors = [];

    /**
     * Undocumented function
     *
     * @param string $message
     * @param array $errors
     */
    public function __construct(string $message, array $errors = [])
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    /**
     * Get all errors
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
