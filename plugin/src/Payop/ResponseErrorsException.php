<?php

namespace Payop;

/**
 * Class ResponseException
 * @package Payop
 */
class ResponseErrorsException extends ResponseException
{
    /**
     * @var array
     */
    private $errors;

    /**
     * @param array $errors
     */
    public function __construct(array $errors)
    {
        $this->errors = $errors;
        parent::__construct('', 0, null);
    }

    /**
     * @return array
     */
    public function getErrors() : array
    {
        return $this->errors;
    }
}