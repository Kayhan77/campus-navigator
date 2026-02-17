<?php

namespace App\DTOs\Auth;

class SendResetLinkDTO
{
    public string $email;

    public function __construct(array $data)
    {
        $this->email = $data['email'];
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
        ];
    }
}
