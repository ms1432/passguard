<?php

declare(strict_types=1);

namespace App\Password\Rules;

final class ContainsDigitRule implements PasswordRule
{
    public function name(): string
    {
        return 'digit';
    }

    public function message(): string
    {
        return 'Hasło musi zawierać cyfrę';
    }

    public function isSatisfiedBy(string $password): bool
    {
        return preg_match('/\p{Nd}/u', $password) === 1;
    }
}
