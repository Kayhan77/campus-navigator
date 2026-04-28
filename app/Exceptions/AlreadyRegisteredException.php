<?php

namespace App\Exceptions;

class AlreadyRegisteredException extends ApiException
{
    public function __construct(string $message = 'You are already registered for this event.', ?array $errors = null)
    {
        parent::__construct($message, 409, $errors);
    }
}
