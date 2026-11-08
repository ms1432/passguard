<?php

declare(strict_types=1);

namespace App\Password\Rules;

final class MinLengthRule implements PasswordRule
{
    private const int MINIMUM_LENGTH = 8;

    public function name(): string
    {
        return 'min_length';
    }

    public function message(): string
    {
        return 'Hasło musi mieć co najmniej 8 znaków';
    }

    public function isSatisfiedBy(string $password): bool
    {
        return mb_strlen($password) >= self::MINIMUM_LENGTH;
    }
}
