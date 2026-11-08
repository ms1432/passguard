<?php

declare(strict_types=1);

namespace App\Password\Rules;

final class ContainsUppercaseRule implements PasswordRule
{
    public function name(): string
    {
        return 'uppercase';
    }

    public function message(): string
    {
        return 'Hasło musi zawierać wielką literę';
    }

    public function isSatisfiedBy(string $password): bool
    {
        return preg_match('/\p{Lu}/u', $password) === 1;
    }
}
