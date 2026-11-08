<?php

declare(strict_types=1);

namespace App\Password\Rules;

final class ContainsLowercaseRule implements PasswordRule
{
    public function name(): string
    {
        return 'lowercase';
    }

    public function message(): string
    {
        return 'Hasło musi zawierać małą literę';
    }

    public function isSatisfiedBy(string $password): bool
    {
        return preg_match('/\p{Ll}/u', $password) === 1;
    }
}
