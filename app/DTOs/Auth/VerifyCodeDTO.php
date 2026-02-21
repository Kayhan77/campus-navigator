<?php

namespace App\DTOs\Auth;

class VerifyCodeDTO
{
    public function __construct(
        public string $email,
        public string $code,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'],
            code: $data['code'],
        );
    }
}
