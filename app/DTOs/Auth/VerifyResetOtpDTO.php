<?php

namespace App\DTOs\Auth;

class VerifyResetOtpDTO
{
    public function __construct(
        public readonly string $email,
        public readonly string $otp,
        public readonly string $password,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            email:    $data['email'],
            otp:      $data['otp'],
            password: $data['password'],
        );
    }
}
