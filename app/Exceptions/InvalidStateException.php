<?php

namespace App\Exceptions;

class InvalidStateException extends ApiException
{
    public function __construct(string $message = 'This resource is in an invalid state for this operation.', ?array $errors = null)
    {
        parent::__construct($message, 422, $errors);
    }
}
