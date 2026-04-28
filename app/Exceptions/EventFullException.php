<?php

namespace App\Exceptions;

class EventFullException extends ApiException
{
    public function __construct(string $message = 'This event has reached maximum capacity.', ?array $errors = null)
    {
        parent::__construct($message, 409, $errors);
    }
}
