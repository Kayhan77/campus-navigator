<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidEmailDomain implements ValidationRule
{
    /**
     * Known disposable/temporary email domains.
     */
    private const BLOCKED_DOMAINS = [
        'mailinator.com',
        '10minutemail.com',
        'temp-mail.org',
        'guerrillamail.com',
        'yopmail.com',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The email domain is not allowed.');

            return;
        }

        $email = strtolower(trim($value));
        $atPos = strrpos($email, '@');

        if ($atPos === false) {
            $fail('The email domain is not allowed.');

            return;
        }

        $domain = substr($email, $atPos + 1);

        if ($domain === '' || in_array($domain, self::BLOCKED_DOMAINS, true)) {
            $fail('The email domain is not allowed.');

            return;
        }

        // Optional hardening: ensure the domain can receive email.
        if (function_exists('checkdnsrr') && ! checkdnsrr($domain, 'MX')) {
            $fail('The email domain is not allowed.');
        }
    }
}
