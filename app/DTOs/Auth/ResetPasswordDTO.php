<?php

namespace App\DTOs\Auth;

class ResetPasswordDTO
{
    public string $email;
    public string $password;
    public string $token;

    public function __construct(array $data)
    {
        $this->email = $data['email'];
        $this->password = $data['password'];
        $this->token = $data['token'];
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
            'token' => $this->token,
        ];
    }
}
