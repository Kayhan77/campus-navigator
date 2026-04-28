<?php

namespace App\Exceptions;

class DuplicateActionException extends ApiException
{
    public function __construct(string $message = 'This action has already been completed.', ?array $errors = null)
    {
        parent::__construct($message, 409, $errors);
    }
}
