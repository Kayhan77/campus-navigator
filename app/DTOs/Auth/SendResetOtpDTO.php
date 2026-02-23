<?php

namespace App\DTOs\Auth;

class SendResetOtpDTO
{
    public function __construct(
        public readonly string $email,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'],
        );
    }
}
