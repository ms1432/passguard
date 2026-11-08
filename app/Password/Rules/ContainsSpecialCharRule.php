<?php

declare(strict_types=1);

namespace App\Password\Rules;

final class ContainsSpecialCharRule implements PasswordRule
{
    public function name(): string
    {
        return 'special_char';
    }

    public function message(): string
    {
        return 'Hasło musi zawierać znak specjalny';
    }

    public function isSatisfiedBy(string $password): bool
    {
        return preg_match('/[^\p{L}\p{N}\s]/u', $password) === 1;
    }
}
